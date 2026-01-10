<?php

namespace App\Traits\TwoFa;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Member;
use App\Models\MemberSetting;
use App\Models\MemberTwoFaToken;
use App\Enums\TwoFaMethod;
use App\Helpers\TwoFaHelper;
use App\Services\AdminTwoFaService;
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
    protected function showRecoveryCodeForm(Request $request)
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

        return view('two-fa.recovery_code_challenge', [
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
}
