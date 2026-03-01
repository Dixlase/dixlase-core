<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Traits\TwoFa;

use App\Enums\TwoFaMethod;
use App\Helpers\TwoFaHelper;
use App\Models\Member;
use App\Models\MemberTwoFaToken;
use App\Services\Auth\AuthContextRegistryService;
use App\Services\TwoFa\TwoFaAttemptService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Traits\LoginTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * 二段階認証のフロー制御機能を提供するトレイト
 *
 * 認証画面の表示、認証検証、リダイレクト処理など、
 * コントローラーで使用する高レベルの認証フロー機能を提供します。
 *
 * 使用するコントローラーは以下の抽象メソッドを実装する必要があります：
 * - getTwoFaService(): 二段階認証サービスのインスタンスを返す
 *
 * その他の設定メソッドはLoginTraitで定義されています。
 */
trait TwoFaAuthenticationTrait
{
    use LoginTrait;

    /**
     * メール認証フォームを表示
     */
    protected function showEmailForm(Request $request)
    {
        $sessionKey = $this->getSessionPrefix().'.id';

        if (! session()->has($sessionKey)) {
            return redirect()->route($this->getLoginRoute());
        }

        $memberId = session($sessionKey);
        $member = Member::find($memberId);

        if (! $member) {
            return redirect()->route($this->getLoginRoute());
        }

        // 既存の有効なコードがあるかチェック
        $hasValidToken = MemberTwoFaToken::where('member_id', $member->id)
            ->where('expires_at', '>', now())
            ->exists();

        // 有効なコードがない場合のみ新規生成・送信
        if (! $hasValidToken) {
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
        $showPasskeyDeviceWarning = $passkeyGloballyEnabled && ! $passkeyAvailableForMember;

        // 二段階認証の設定値を取得（セキュリティ設定 > コンフィグ）
        $twoFaExpireMinutes = (int) \App\Models\SecuritySetting::getValue('two_fa_expire_minutes', config('two-fa.code_expiration', 5));
        $twoFaResendIntervalSeconds = (int) \App\Models\SecuritySetting::getValue('two_fa_resend_interval_seconds', config('two-fa.resend_interval', 60));

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
        $sessionKey = $this->getSessionPrefix().'.id';

        if (! session()->has($sessionKey)) {
            return redirect()->route($this->getLoginRoute());
        }

        $memberId = session($sessionKey);
        $member = Member::find($memberId);

        if (! $member) {
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
        $hasPasskeyDevices = ! $passkeyDevices->isEmpty();

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
        $sessionKey = $this->getSessionPrefix().'.id';

        if (! session()->has($sessionKey)) {
            return redirect()->route($this->getLoginRoute());
        }

        $memberId = session($sessionKey);
        $member = Member::find($memberId);

        if (! $member) {
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
        $showPasskeyDeviceWarning = $passkeyGloballyEnabled && ! $passkeyAvailableForMember;

        return view('two-fa.recovery-code-challenge', [
            'availableMethods' => $availableMethods,
            'action' => $this->getTwoFaVerifyRoute('recovery-code'),
            'loginRoute' => route($this->getLoginRoute()),
            'showPasskeyDeviceWarning' => $showPasskeyDeviceWarning,
        ]);
    }

    /**
     * 検証ルートを取得
     */
    protected function getTwoFaVerifyRoute(string $method): string
    {
        $prefix = $this->getTwoFaRoutePrefix();

        return route($prefix.'.two-fa.'.$method.'.verify');
    }

    /**
     * 再送信ルートを取得
     */
    protected function getTwoFaResendRoute(string $method): string
    {
        $prefix = $this->getTwoFaRoutePrefix();

        return route($prefix.'.two-fa.'.$method.'.resend');
    }

    /**
     * チャレンジルートを取得
     */
    protected function getTwoFaChallengeRoute(string $method): string
    {
        $prefix = $this->getTwoFaRoutePrefix();

        return route($prefix.'.two-fa.'.$method.'.challenge');
    }

    /**
     * 設定モデルクラス名を取得（継承先で実装）
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * 二段階認証サービスのインスタンスを取得（継承先で実装）
     */
    abstract protected function getTwoFaService();

    /**
     * 認証方法に応じたルート名を取得
     *
     * @param  int  $method  認証方法（TwoFaMethod enum値）
     * @return string ルート名（例: 'admin.two-fa.email.show'）
     */
    protected function getTwoFaMethodRoute(int $method): string
    {
        $prefix = $this->getTwoFaRoutePrefix();

        return match ($method) {
            \App\Enums\TwoFaMethod::EMAIL->value => "{$prefix}.two-fa.email.show",
            default => "{$prefix}.two-fa.email.show",
        };
    }

    /**
     * セッションからユーザーを取得
     */
    protected function getUserFromSession()
    {
        $sessionKey = $this->getSessionPrefix().'.id';
        $userId = session($sessionKey);

        if (! $userId) {
            return;
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

        if (! $user) {
            $context = $this->getContext();
            $loginRoute = AuthContextRegistryService::getRoute($context, 'login');

            // フォールバック: コンテキストが登録されていない場合
            if (! $loginRoute) {
                $loginRoute = $context === 'admin' ? 'admin.login' : 'login';
            }

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
        $remember = session($sessionPrefix.'.remember', false);
        $guardName = $this->getGuardName();
        $dashboardRoute = $this->getDashboardRoute();

        // セッションクリーンアップ
        session()->forget([
            $sessionPrefix.'.id',
            $sessionPrefix.'.remember',
            $sessionPrefix.'.email_sent',
        ]);

        // ログイン通知を送信（ログイン前に送信）
        if (method_exists($this, 'getLoginNotificationServiceClass')) {
            try {
                app($this->getLoginNotificationServiceClass())->handle($user, $request);
            } catch (\Exception $e) {
                Log::error('[2FA] Login notification failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 先にログイン（AdminLoginControllerと同じ順序）
        Auth::guard($guardName)->login($user, $remember);

        // ログイン後にセッションを再生成（AdminLoginControllerと同じ）
        $request->session()->regenerate(true);

        return redirect()->route($dashboardRoute);
    }

    /**
     * Passkeyチャレンジ生成の共通処理
     */
    protected function generatePasskeyChallenge($user): array
    {
        $twoFaPasskeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);

        // Passkey認証が利用可能かチェック
        if (! $twoFaPasskeyService->isAvailable()) {
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

        return $twoFa->validate($user, $code, \App\Enums\TwoFaMethod::EMAIL->value);
    }

    /**
     * メール認証コード再送信の共通処理
     */
    protected function resendEmailCode($user): void
    {
        $twoFa = $this->getTwoFaService();
        $twoFa->generate($user, \App\Enums\TwoFaMethod::EMAIL->value);

        // セッションのメール送信済みフラグをクリア（次回showEmailChallengeで再送信可能にする）
        $sessionKey = $this->getSessionPrefix().'.email_sent';
        session()->forget($sessionKey);
    }

    /**
     * 利用可能な認証方法を取得
     */
    protected function getAvailableMethods(?int $currentMethod = null): array
    {
        $twoFa = $this->getTwoFaService();
        $systemSettings = $twoFa->getSystemSettings();
        $enabledMethods = $systemSettings['enabled_methods'] ?? [\App\Enums\TwoFaMethod::EMAIL->value];

        $availableMethods = [];
        $prefix = $this->getTwoFaRoutePrefix();

        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) {
                $methodEnum = \App\Enums\TwoFaMethod::from($method);

                // ルート名を生成
                $routeName = match ($method) {
                    \App\Enums\TwoFaMethod::EMAIL->value => "{$prefix}.two-fa.email.show",
                    default => "{$prefix}.two-fa.email.show",
                };

                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodEnum->label(),
                    'url' => route($routeName),
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

        // セッションにメール送信済みフラグがない場合のみメール送信
        $sessionKey = $this->getSessionPrefix().'.email_sent';
        if (! session()->has($sessionKey)) {
            $twoFa = $this->getTwoFaService();
            try {
                $twoFa->generate($user, \App\Enums\TwoFaMethod::EMAIL->value);
                session([$sessionKey => true]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('[2FA] Failed to generate email code', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'context' => $this->getContext(),
                ]);
            }
        }

        $currentMethod = \App\Enums\TwoFaMethod::EMAIL->value;
        $availableMethods = $this->getAvailableMethods($currentMethod);
        $settings = $this->getTwoFaSettings();

        // リカバリーコードルートを取得
        $context = $this->getContext();
        $recoveryCodeRoute = $this->getRecoveryCodeRoute();

        // CAPTCHA設定を取得
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $captchaWidget = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);

        return view('two-fa.email-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
            'expireMinutes' => $settings['expireMinutes'],
            'resendIntervalSeconds' => $settings['resendIntervalSeconds'],
            'context' => $context,
            'contextValue' => $context,
            'loginRoute' => route($this->getLoginRoute()),
            'action' => route($this->getTwoFaRoutePrefix().'.two-fa.email.verify'),
            'resendAction' => route($this->getTwoFaRoutePrefix().'.two-fa.email.resend'),
            'recoveryCodeRoute' => $recoveryCodeRoute,
            'captchaEnabled' => $captchaEnabled,
            'captchaWidget' => $captchaWidget,
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

        // ロックアウト状態をチェック
        $twoFa = $this->getTwoFaService();
        $lockoutStatus = $twoFa->checkLockout($user);

        if ($lockoutStatus['locked_out']) {
            return back()->withErrors([
                'code' => __('two_fa.lockout.message', [
                    'minutes' => $lockoutStatus['remaining_minutes'],
                ]),
            ]);
        }

        if (! $this->verifyEmailCode($user, $request->code)) {
            return back()->withErrors([
                'code' => __('two_fa.email.invalid_code'),
            ]);
        }

        return $this->completeAuthentication($user, $request);
    }

    /**
     * メール認証コードを再送信
     */
    public function resendEmail(Request $request)
    {
        $user = $this->getUserFromSession();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('auth.session_expired'),
            ], 401);
        }

        try {
            $this->resendEmailCode($user);

            return response()->json([
                'success' => true,
                'message' => __('two-fa/email.resend_success'),
            ]);
        } catch (\Exception $e) {
            Log::error('[2FA] Email code resend failed', [
                'user_id' => $user->id,
                'context' => $this->getContext(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('two_fa.email.send_failed'),
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
        $context = $this->getContext();

        // CAPTCHA設定を取得
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $captchaWidget = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);

        return view('two-fa.recovery-code-challenge', [
            'availableMethods' => $availableMethods,
            'context' => $context,
            'loginRoute' => route($this->getLoginRoute()),
            'action' => route($this->getTwoFaRoutePrefix().'.two-fa.recovery-code.confirm'),
            'emailChallengeRoute' => $this->getTwoFaRoutePrefix().'.two-fa.email.show',
            'captchaEnabled' => $captchaEnabled,
            'captchaWidget' => $captchaWidget,
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

        if (! $this->verifyRecoveryCodeValue($user, $request->recovery_code)) {
            return back()->withErrors([
                'recovery_code' => __('two_fa.recovery_code.invalid'),
            ]);
        }

        return $this->completeAuthentication($user, $request);
    }
}
