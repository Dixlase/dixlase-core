<?php

namespace App\Traits\TwoFa;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Member;
use App\Models\MemberSetting;
use App\Models\MemberTwoFaToken;
use App\Enums\TwoFaMethod;
use App\Helpers\TwoFaHelper;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaAttemptService;

/**
 * 二段階認証のフロー制御機能を提供するトレイト
 * 
 * 認証画面の表示、認証検証、リダイレクト処理など、
 * コントローラーで使用する高レベルの認証フロー機能を提供します。
 * 
 * 使用するコントローラーは以下の抽象メソッドを実装する必要があります：
 * - getSettingModelClass(): 設定モデルクラス名を返す
 * - getLoginRoute(): ログイン画面のルート名を返す
 * - getDashboardRoute(): ダッシュボードのルート名を返す
 * - getSessionPrefix(): セッションキーのプレフィックスを返す
 * - getTwoFaService(): 二段階認証サービスのインスタンスを返す
 * - getUserModelClass(): ユーザーモデルクラス名を返す
 * - getGuardName(): 認証ガード名を返す
 * - getContext(): コンテキスト（'admin' or 'user'）を返す
 */
trait TwoFaAuthenticationTrait
{
    /**
     * メール認証フォームを表示
     */
    protected function showEmailForm(Request $request)
    {
        $sessionKey = $this->getSessionPrefix() . '.id';
        
        if (!session()->has($sessionKey)) {
            return redirect()->route($this->getLoginRoute());
        }

        $memberId = session($sessionKey);
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route($this->getLoginRoute());
        }

        // 既存の有効なコードがあるかチェック
        $hasValidToken = MemberTwoFaToken::where('member_id', $member->id)
            ->where('expires_at', '>', now())
            ->exists();

        // 有効なコードがない場合のみ新規生成・送信
        if (!$hasValidToken) {
            Log::info('[Email Challenge] メール認証画面表示 - コード生成開始', [
                'member_id' => $member->id,
                'email' => $member->email,
            ]);
            
            $twoFactor = $this->getTwoFaService();
            $twoFactor->generate($member, TwoFaMethod::EMAIL->value);
            
            Log::info('[Email Challenge] コード生成・メール送信完了');
        } else {
            Log::info('[Email Challenge] 既存の有効なコードを再利用', [
                'member_id' => $member->id,
            ]);
        }

        // 利用可能な認証方法を取得（Passkeyデバイス未登録時はPasskeyを除外）
        $twoFactorHelper = app(TwoFaHelper::class);
        $enabledMethods = $twoFactorHelper->getAvailableTwoFaMethodsForMember($member);
        $availableMethods = [];
        $currentMethod = TwoFaMethod::EMAIL->value;

        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) { // EMAIL以外
                $methodEnum = TwoFaMethod::from($method);
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodEnum->label(),
                    'url' => $this->getTwoFaMethodRoute($method),
                ];
            }
        }

        // Passkeyが有効だがデバイスが未登録かチェック
        $settingModelClass = $this->getSettingModelClass();
        $globalEnabledMethods = $twoFactorHelper->getEnabledTwoFaMethods($settingModelClass);
        $passkeyGloballyEnabled = in_array(TwoFaMethod::PASSKEY->value, $globalEnabledMethods);
        $passkeyAvailableForMember = in_array(TwoFaMethod::PASSKEY->value, $enabledMethods);
        $showPasskeyDeviceWarning = $passkeyGloballyEnabled && !$passkeyAvailableForMember;

        // 二段階認証の設定値を取得（メンバー設定 > コンフィグ）
        $twoFaExpireMinutes = (int) $settingModelClass::getValue('two_fa_expire_minutes', config('two-fa.code_expiration', 5));
        $twoFaResendIntervalSeconds = (int) $settingModelClass::getValue('two_fa_resend_interval_seconds', config('two-fa.resend_interval', 60));

        return view('two-fa.email-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
            'expireMinutes' => $twoFaExpireMinutes,
            'resendIntervalSeconds' => $twoFaResendIntervalSeconds,
            'action' => $this->getTwoFaVerifyRoute('email'),
            'resendAction' => $this->getTwoFaResendRoute('email'),
            'loginRoute' => route($this->getLoginRoute()),
            'showPasskeyDeviceWarning' => $showPasskeyDeviceWarning,
        ]);
    }

    /**
     * Passkey認証フォームを表示
     */
    protected function showPasskeyForm(Request $request)
    {
        $sessionKey = $this->getSessionPrefix() . '.id';
        
        if (!session()->has($sessionKey)) {
            return redirect()->route($this->getLoginRoute());
        }

        $memberId = session($sessionKey);
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route($this->getLoginRoute());
        }

        // 利用可能な認証方法を取得（Passkeyデバイス未登録時はPasskeyを除外）
        $twoFactorHelper = app(TwoFaHelper::class);
        $enabledMethods = $twoFactorHelper->getAvailableTwoFaMethodsForMember($member);
        $availableMethods = [];
        $currentMethod = TwoFaMethod::PASSKEY->value;

        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) { // PASSKEY以外
                $methodEnum = TwoFaMethod::from($method);
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodEnum->label(),
                    'url' => $this->getTwoFaMethodRoute($method),
                ];
            }
        }

        // Passkeyデバイスが未登録かチェック
        $passkeyService = app(TwoFaPasskeyService::class);
        $passkeyDevices = $passkeyService->getDevices($member);
        $hasPasskeyDevices = !$passkeyDevices->isEmpty();

        return view('two-fa.passkey-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
            'challengeAction' => $this->getTwoFaChallengeRoute('passkey'),
            'verifyAction' => $this->getTwoFaVerifyRoute('passkey'),
            'loginRoute' => route($this->getLoginRoute()),
            'hasPasskeyDevices' => $hasPasskeyDevices,
        ]);
    }

    /**
     * 回復コード入力画面を表示
     */
    public function showRecoveryCodeForm(Request $request)
    {
        $sessionKey = $this->getSessionPrefix() . '.id';
        
        if (!session()->has($sessionKey)) {
            return redirect()->route($this->getLoginRoute());
        }

        $memberId = session($sessionKey);
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route($this->getLoginRoute());
        }

        // ロックアウトチェック
        $attemptService = app(TwoFaAttemptService::class);
        if ($attemptService->isLockedOut($member)) {
            $remainingMinutes = $attemptService->getRemainingLockoutTime($member);
            return redirect()->route($this->getLoginRoute())
                ->withErrors(['email' => __('two_fa.lockout.message', ['minutes' => $remainingMinutes])]);
        }

        // 利用可能な認証方法を取得（Passkeyデバイス未登録時はPasskeyを除外）
        $twoFactorHelper = app(TwoFaHelper::class);
        $enabledMethods = $twoFactorHelper->getAvailableTwoFaMethodsForMember($member);
        $availableMethods = [];

        foreach ($enabledMethods as $method) {
            $methodEnum = TwoFaMethod::from($method);
            $availableMethods[] = [
                'value' => $method,
                'label' => $methodEnum->label(),
                'url' => $this->getTwoFaMethodRoute($method),
            ];
        }

        // Passkeyが有効だがデバイスが未登録かチェック
        $settingModelClass = $this->getSettingModelClass();
        $globalEnabledMethods = $twoFactorHelper->getEnabledTwoFaMethods($settingModelClass);
        $passkeyGloballyEnabled = in_array(TwoFaMethod::PASSKEY->value, $globalEnabledMethods);
        $passkeyAvailableForMember = in_array(TwoFaMethod::PASSKEY->value, $enabledMethods);
        $showPasskeyDeviceWarning = $passkeyGloballyEnabled && !$passkeyAvailableForMember;

        return view('two-fa.recovery-code-challenge', [
            'availableMethods' => $availableMethods,
            'action' => $this->getTwoFaVerifyRoute('recovery-code'),
            'loginRoute' => route($this->getLoginRoute()),
            'showPasskeyDeviceWarning' => $showPasskeyDeviceWarning,
        ]);
    }

    /**
     * メール認証コードを再送信
     */
    protected function resendEmailCode(Request $request)
    {
        $sessionKey = $this->getSessionPrefix() . '.id';
        
        if (!session()->has($sessionKey)) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        $member = Member::find(session($sessionKey));

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        $twoFactor = $this->getTwoFaService();
        $twoFactor->generate($member, TwoFaMethod::EMAIL->value);

        return response()->json([
            'success' => true,
            'message' => __('two_fa.email.resend_success')
        ]);
    }

    /**
     * Passkey認証チャレンジを取得
     */
    protected function getPasskeyChallenge(Request $request)
    {
        $sessionKey = $this->getSessionPrefix() . '.id';
        
        if (!session()->has($sessionKey)) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        $memberId = session($sessionKey);
        $member = Member::find($memberId);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        try {
            $twoFaPasskeyService = new TwoFaPasskeyService();
            $challenge = $twoFaPasskeyService->generateChallenge($member);

            return response()->json([
                'success' => true,
                'challenge' => $challenge,
            ]);
        } catch (\Exception $e) {
            Log::error('[Passkey Challenge] Failed to generate challenge', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('two_fa.passkey.challenge_failed'),
            ], 500);
        }
    }

    /**
     * 認証方法に応じたルートを取得
     */
    protected function getTwoFaMethodRoute(int $method): string
    {
        $prefix = $this->getRoutePrefix();
        
        return match($method) {
            TwoFaMethod::EMAIL->value => route($prefix . '.two-fa.email.show'),
            TwoFaMethod::PASSKEY->value => route($prefix . '.two-fa.passkey.show'),
            default => route($prefix . '.two-fa.email.show'),
        };
    }

    /**
     * 認証検証ルートを取得
     */
    protected function getTwoFaVerifyRoute(string $method): string
    {
        $prefix = $this->getRoutePrefix();
        return route($prefix . '.two-fa.' . $method . '.verify');
    }

    /**
     * 再送信ルートを取得
     */
    protected function getTwoFaResendRoute(string $method): string
    {
        $prefix = $this->getRoutePrefix();
        return route($prefix . '.two-fa.' . $method . '.resend');
    }

    /**
     * チャレンジルートを取得
     */
    protected function getTwoFaChallengeRoute(string $method): string
    {
        $prefix = $this->getRoutePrefix();
        return route($prefix . '.two-fa.' . $method . '.challenge');
    }

    /**
     * ルートプレフィックスを取得（デフォルト実装）
     */
    protected function getRoutePrefix(): string
    {
        return 'admin';
    }

    /**
     * 設定モデルクラス名を取得（継承先で実装）
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * ログイン画面のルート名を取得（継承先で実装）
     */
    abstract protected function getLoginRoute(): string;

    /**
     * ダッシュボードのルート名を取得（継承先で実装）
     */
    abstract protected function getDashboardRoute(): string;

    /**
     * セッションキーのプレフィックスを取得（継承先で実装）
     */
    abstract protected function getSessionPrefix(): string;

    /**
     * 二段階認証サービスのインスタンスを取得（継承先で実装）
     */
    abstract protected function getTwoFaService();

    /**
     * ユーザーモデルクラス名を取得（継承先で実装）
     */
    abstract protected function getUserModelClass(): string;

    /**
     * 認証ガード名を取得（継承先で実装）
     */
    abstract protected function getGuardName(): string;

    /**
     * コンテキストを取得（継承先で実装）
     * @return string 'admin' or 'user'
     */
    abstract protected function getContext(): string;

    /**
     * 二段階認証ルートのプレフィックスを取得（継承先で実装）
     * @return string 例: 'admin' or 'users-plugin::mypage'
     */
    abstract protected function getTwoFaRoutePrefix(): string;

    /**
     * 設定モデルクラス名を取得（継承先で実装）
     * @return string 例: MemberSetting::class or DixlaseUsersUserSetting::class
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * ログインルート名を取得（継承先で実装）
     * @return string 例: 'admin.login' or 'users-plugin::mypage.login'
     */
    abstract protected function getLoginRoute(): string;

    /**
     * セッションからユーザーを取得
     */
    protected function getUserFromSession()
    {
        $sessionKey = $this->getSessionPrefix() . '.id';
        $userId = session($sessionKey);
        
        if (!$userId) {
            return null;
        }
        
        $modelClass = $this->getUserModelClass();
        return $modelClass::find($userId);
    }

    /**
     * セッションチェックとユーザー取得（リダイレクト付き）
     */
    protected function checkSessionAndGetUser()
    {
        $user = $this->getUserFromSession();
        
        if (!$user) {
            $loginRoute = $this->getContext() === 'admin' 
                ? 'admin.login' 
                : 'users-plugin::mypage.login';
            return redirect()->route($loginRoute);
        }
        
        return $user;
    }

    /**
     * 認証成功後のログイン処理
     */
    protected function completeAuthentication($user, Request $request)
    {
        $sessionPrefix = $this->getSessionPrefix();
        $remember = session($sessionPrefix . '.remember', false);
        
        Auth::guard($this->getGuardName())->login($user, $remember);
        
        session()->forget([
            $sessionPrefix . '.id',
            $sessionPrefix . '.remember'
        ]);
        $request->session()->regenerate();
        
        return redirect()->route($this->getDashboardRoute());
    }

    /**
     * Passkeyチャレンジ生成の共通処理
     */
    protected function generatePasskeyChallenge($user): array
    {
        $twoFaPasskeyService = new \App\Services\TwoFa\TwoFaPasskeyService();
        
        // Passkey認証が利用可能かチェック
        if (!$twoFaPasskeyService->isAvailable()) {
            throw new \Exception(__('auth.passkey_https_required'));
        }

        // ユーザーがPasskeyを登録しているかチェック
        $credentials = $twoFaPasskeyService->getCredentials($user);
        if ($credentials->isEmpty()) {
            throw new \Exception(__('auth.passkey_not_registered'));
        }

        // 認証チャレンジを生成
        return $twoFaPasskeyService->generateAuthenticationChallenge($user);
    }

    /**
     * Passkey検証の共通処理
     */
    protected function verifyPasskeyCredential($user, array $credential): bool
    {
        $twoFaPasskeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);
        return $twoFaPasskeyService->verifyAssertion($user, $credential);
    }

    /**
     * 回復コード検証の共通処理
     */
    protected function verifyRecoveryCodeValue($user, string $code): bool
    {
        $recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);
        return $recoveryCodeService->validate($user, $code);
    }

    /**
     * 認証方法に応じたルートを取得
     */
    protected function getTwoFaMethodRoute(int $method): string
    {
        $prefix = $this->getTwoFaRoutePrefix();
        
        return match($method) {
            \App\Enums\TwoFaMethod::EMAIL->value => route("{$prefix}.two-fa.email.show"),
            \App\Enums\TwoFaMethod::PASSKEY->value => route("{$prefix}.two-fa.passkey.show"),
            default => route("{$prefix}.two-fa.email.show"),
        };
    }

    /**
     * 二段階認証設定値を取得
     */
    protected function getTwoFaSettings(): array
    {
        $settingModelClass = $this->getSettingModelClass();
        
        return [
            'expireMinutes' => (int) $settingModelClass::getValue('two_fa_expire_minutes', config('two-fa.code_expiration', 5)),
            'resendIntervalSeconds' => (int) $settingModelClass::getValue('two_fa_resend_interval_seconds', config('two-fa.resend_interval', 60)),
        ];
    }

    /**
     * メール認証コード検証の共通処理
     */
    protected function verifyEmailCode($user, string $code): bool
    {
        $twoFa = $this->getTwoFaService();
        return $twoFa->validate($user, $code);
    }

    /**
     * メール認証コード再送信の共通処理
     */
    protected function resendEmailCode($user): void
    {
        $twoFa = $this->getTwoFaService();
        $twoFa->generate($user);
    }

    /**
     * 利用可能な認証方法を取得
     */
    protected function getAvailableMethods(int $currentMethod = null): array
    {
        $twoFa = $this->getTwoFaService();
        $systemSettings = $twoFa->getSystemSettings();
        $enabledMethods = $systemSettings['enabled_methods'] ?? [\App\Enums\TwoFaMethod::EMAIL->value];

        $availableMethods = [];
        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) {
                $methodEnum = \App\Enums\TwoFaMethod::from($method);
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodEnum->label(),
                    'url' => $this->getTwoFaMethodRoute($method),
                ];
            }
        }

        return $availableMethods;
    }

    /**
     * メール認証チャレンジ画面を表示
     */
    public function showEmailChallenge(Request $request)
    {
        $user = $this->checkSessionAndGetUser();
        if ($user instanceof \Illuminate\Http\RedirectResponse) {
            return $user;
        }

        $currentMethod = \App\Enums\TwoFaMethod::EMAIL->value;
        $availableMethods = $this->getAvailableMethods($currentMethod);
        $settings = $this->getTwoFaSettings();

        return view('two-fa.email-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
            'expireMinutes' => $settings['expireMinutes'],
            'resendIntervalSeconds' => $settings['resendIntervalSeconds'],
            'context' => $this->getContext(),
            'loginRoute' => route($this->getLoginRoute()),
            'action' => route($this->getTwoFaRoutePrefix() . '.two-fa.email.verify'),
            'resendAction' => route($this->getTwoFaRoutePrefix() . '.two-fa.email.resend'),
        ]);
    }

    /**
     * メール認証コードを検証
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $this->checkSessionAndGetUser();
        if ($user instanceof \Illuminate\Http\RedirectResponse) {
            return $user;
        }

        if (!$this->verifyEmailCode($user, $request->code)) {
            return back()->withErrors([
                'code' => __('two_fa.email.invalid_code')
            ]);
        }

        Log::info('[2FA] Email authentication success', [
            'user_id' => $user->id,
            'context' => $this->getContext()
        ]);

        return $this->completeAuthentication($user, $request);
    }

    /**
     * メール認証コードを再送信
     */
    public function resendEmail(Request $request)
    {
        $user = $this->getUserFromSession();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('auth.session_expired')
            ], 401);
        }

        try {
            $this->resendEmailCode($user);

            return response()->json([
                'success' => true,
                'message' => __('two_fa.email.resend_success')
            ]);
        } catch (\Exception $e) {
            Log::error('[2FA] Email code resend failed', [
                'user_id' => $user->id,
                'context' => $this->getContext(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('two_fa.email.send_failed')
            ], 500);
        }
    }

    /**
     * Passkey認証チャレンジ画面を表示
     */
    public function showPasskeyChallenge(Request $request)
    {
        $user = $this->checkSessionAndGetUser();
        if ($user instanceof \Illuminate\Http\RedirectResponse) {
            return $user;
        }

        $currentMethod = \App\Enums\TwoFaMethod::PASSKEY->value;
        $availableMethods = $this->getAvailableMethods($currentMethod);

        return view('two-fa.passkey-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
            'context' => $this->getContext(),
            'loginRoute' => route($this->getLoginRoute()),
            'challengeAction' => route($this->getTwoFaRoutePrefix() . '.two-fa.passkey.challenge'),
            'verifyAction' => route($this->getTwoFaRoutePrefix() . '.two-fa.passkey.verify'),
        ]);
    }

    /**
     * Passkey認証チャレンジを取得
     */
    public function getPasskeyChallenge(Request $request)
    {
        $user = $this->getUserFromSession();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        try {
            $challenge = $this->generatePasskeyChallenge($user);

            return response()->json([
                'success' => true,
                'challenge' => $challenge,
            ]);
        } catch (\Exception $e) {
            Log::error('[Passkey Challenge] Failed to generate challenge', [
                'user_id' => $user->id,
                'context' => $this->getContext(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('two_fa.passkey.challenge_failed'),
            ], 500);
        }
    }

    /**
     * Passkey認証を検証
     */
    public function verifyPasskey(Request $request)
    {
        $user = $this->getUserFromSession();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        $request->validate([
            'response' => 'required|array',
            'response.id' => 'required|string',
            'response.response' => 'required|array',
        ]);

        try {
            $credentialData = $request->input('response');

            if ($this->verifyPasskeyCredential($user, $credentialData)) {
                Log::info('[Passkey Auth] Authentication success', [
                    'user_id' => $user->id,
                    'context' => $this->getContext()
                ]);

                return response()->json([
                    'success' => true,
                    'redirect' => route($this->getDashboardRoute())
                ]);
            } else {
                Log::warning('[Passkey Auth] Authentication failed', [
                    'user_id' => $user->id,
                    'context' => $this->getContext(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => __('auth.passkey_verification_failed')
                ], 401);
            }
        } catch (\Exception $e) {
            Log::error('[Passkey Auth] Verification error', [
                'user_id' => $user->id,
                'context' => $this->getContext(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('auth.passkey_verification_error')
            ], 500);
        }
    }

    /**
     * 回復コード認証チャレンジ画面を表示
     */
    public function showRecoveryCodeChallenge(Request $request)
    {
        $user = $this->checkSessionAndGetUser();
        if ($user instanceof \Illuminate\Http\RedirectResponse) {
            return $user;
        }

        $availableMethods = $this->getAvailableMethods();

        return view('two-fa.recovery-code-challenge', [
            'availableMethods' => $availableMethods,
            'context' => $this->getContext(),
            'loginRoute' => route($this->getLoginRoute()),
            'action' => route($this->getTwoFaRoutePrefix() . '.two-fa.recovery-code.verify'),
        ]);
    }

    /**
     * 回復コードを検証
     */
    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'recovery_code' => 'required|string',
        ]);

        $user = $this->checkSessionAndGetUser();
        if ($user instanceof \Illuminate\Http\RedirectResponse) {
            return $user;
        }

        if (!$this->verifyRecoveryCodeValue($user, $request->recovery_code)) {
            return back()->withErrors([
                'recovery_code' => __('two_fa.recovery_code.invalid')
            ]);
        }

        Log::info('[2FA] Recovery code authentication success', [
            'user_id' => $user->id,
            'context' => $this->getContext()
        ]);

        return $this->completeAuthentication($user, $request);
    }
}
