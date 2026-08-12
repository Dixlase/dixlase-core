<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
 * Trait that provides two-factor authentication flow control functionality
 *
 * Provides high-level authentication flow features for use in controllers,
 * including displaying authentication screens, verifying authentication, and redirect processing.
 *
 * Controllers using this trait must implement the following abstract methods:
 * - getTwoFaService(): Returns an instance of the two-factor authentication service
 *
 * Other settings methods are defined in LoginTrait.
 */
trait TwoFaAuthenticationTrait
{
    use LoginTrait;

    /**
     * Display email authentication form
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

        // Check if there is an existing valid code
        $hasValidToken = MemberTwoFaToken::where('member_id', $member->id)
            ->where('expires_at', '>', now())
            ->exists();

        // Generate and send a new code only if there is no valid code
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

        // Get available authentication methods (exclude Passkey when Passkey device is not registered)
        $twoFactorHelper = app(TwoFaHelper::class);
        $enabledMethods = $twoFactorHelper->getAvailableTwoFaMethodsForMember($member);
        $availableMethods = [];
        $currentMethod = TwoFaMethod::EMAIL->value;

        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) { // Other than EMAIL
                $methodEnum = TwoFaMethod::from($method);
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodEnum->label(),
                    'url' => $this->getTwoFaMethodRoute($method),
                ];
            }
        }

        // Check if Passkey is enabled but device is not registered
        $settingModelClass = $this->getSettingModelClass();
        $globalEnabledMethods = $twoFactorHelper->getEnabledTwoFaMethods($settingModelClass);
        $passkeyGloballyEnabled = in_array(TwoFaMethod::PASSKEY->value, $globalEnabledMethods);
        $passkeyAvailableForMember = in_array(TwoFaMethod::PASSKEY->value, $enabledMethods);
        $showPasskeyDeviceWarning = $passkeyGloballyEnabled && ! $passkeyAvailableForMember;

        // Get two-factor authentication settings values (Security settings > Config)
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
     * Display Passkey authentication form
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

        // Get available authentication methods (exclude Passkey when Passkey device is not registered)
        $twoFactorHelper = app(TwoFaHelper::class);
        $enabledMethods = $twoFactorHelper->getAvailableTwoFaMethodsForMember($member);
        $availableMethods = [];
        $currentMethod = TwoFaMethod::PASSKEY->value;

        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) { // Other than PASSKEY
                $methodEnum = TwoFaMethod::from($method);
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodEnum->label(),
                    'url' => $this->getTwoFaMethodRoute($method),
                ];
            }
        }

        // Check if Passkey device is not registered
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
     * Display recovery code input screen
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

        // Lockout check
        $attemptService = app(TwoFaAttemptService::class);
        if ($attemptService->isLockedOut($member)) {
            $remainingMinutes = $attemptService->getRemainingLockoutTime($member);

            return redirect()->route($this->getLoginRoute())
                ->withErrors(['email' => __('two_fa.lockout.message', ['minutes' => $remainingMinutes])]);
        }

        // Get available authentication methods (exclude Passkey when Passkey device is not registered)
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

        // Check if Passkey is enabled but device is not registered
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
     * Get verification route
     */
    protected function getTwoFaVerifyRoute(string $method): string
    {
        $prefix = $this->getTwoFaRoutePrefix();

        return route($prefix.'.two-fa.'.$method.'.verify');
    }

    /**
     * Get resend route
     */
    protected function getTwoFaResendRoute(string $method): string
    {
        $prefix = $this->getTwoFaRoutePrefix();

        return route($prefix.'.two-fa.'.$method.'.resend');
    }

    /**
     * Get challenge route
     */
    protected function getTwoFaChallengeRoute(string $method): string
    {
        $prefix = $this->getTwoFaRoutePrefix();

        return route($prefix.'.two-fa.'.$method.'.challenge');
    }

    /**
     * Get settings model class name (implement in child class)
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * Get two-factor authentication service instance (implement in child class)
     */
    abstract protected function getTwoFaService();

    /**
     * Get route name based on authentication method
     *
     * @param  int  $method  Authentication method (TwoFaMethod enum value)
     * @return string Route name (e.g., 'admin.two-fa.email.show')
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
     * Get user from session
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
     * Check session and get user (with redirect)
     */
    protected function checkSessionAndGetUser()
    {
        $user = $this->getUserFromSession();

        if (! $user) {
            $context = $this->getContext();
            $loginRoute = AuthContextRegistryService::getRoute($context, 'login');

            // Fallback: when context is not registered
            if (! $loginRoute) {
                $loginRoute = $context === 'admin' ? 'admin.login' : 'login';
            }

            return redirect()->route($loginRoute);
        }

        return $user;
    }

    /**
     * Login process after successful authentication
     */
    protected function completeAuthentication($user, Request $request)
    {
        $sessionPrefix = $this->getSessionPrefix();
        $remember = session($sessionPrefix.'.remember', false);
        $guardName = $this->getGuardName();
        $dashboardRoute = $this->getDashboardRoute();

        // Session cleanup
        session()->forget([
            $sessionPrefix.'.id',
            $sessionPrefix.'.remember',
            $sessionPrefix.'.email_sent',
        ]);

        // Send login notification (before login)
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

        // Login first (same order as AdminLoginController)
        Auth::guard($guardName)->login($user, $remember);

        // Regenerate session after login (same as AdminLoginController)
        $request->session()->regenerate(true);

        return redirect()->route($dashboardRoute);
    }

    /**
     * Common process for generating passkey challenge
     */
    protected function generatePasskeyChallenge($user): array
    {
        $twoFaPasskeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);

        // Check if passkey authentication is available
        if (! $twoFaPasskeyService->isAvailable()) {
            throw new \Exception(__('auth.passkey_https_required'));
        }

        // Check if user has registered a passkey
        $credentials = $twoFaPasskeyService->getCredentials($user);
        if ($credentials->isEmpty()) {
            throw new \Exception(__('auth.passkey_not_registered'));
        }

        // Generate authentication challenge
        return $twoFaPasskeyService->generateAuthenticationChallenge($user);
    }

    /**
     * Common process for passkey verification
     */
    protected function verifyPasskeyCredential($user, array $credential): bool
    {
        $twoFaPasskeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);

        return $twoFaPasskeyService->verifyAssertion($user, $credential);
    }

    /**
     * Common process for recovery code verification.
     *
     * Routed through TwoFaService::validateRecoveryCode() rather than calling
     * TwoFaRecoveryCodeService::validate() directly. The direct call skipped
     * TwoFaAttemptService::recordAttempt(), so failed recovery-code entries
     * were never counted. verifyRecoveryCode() checks the lockout before
     * getting here and its comment says the intent is to keep this from
     * becoming an un-throttled brute-force channel -- but with nothing
     * recording the failures, the counter never advanced and the lockout could
     * not fire no matter how many attempts were made.
     *
     * The email and passkey paths already went through TwoFaService, which
     * records on both success and failure. This was the one method that did
     * not, and it is the method guarding the fallback credential.
     */
    protected function verifyRecoveryCodeValue($user, string $code): bool
    {
        $twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => $this->getSettingModelClass(),
            'context' => $this->getContext(),
        ]);

        return $twoFaService->validateRecoveryCode($user, $code);
    }

    /**
     * Get two-factor authentication settings value
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
     * Common process for email verification code validation
     */
    protected function verifyEmailCode($user, string $code): bool
    {
        $twoFa = $this->getTwoFaService();

        return $twoFa->validate($user, $code, \App\Enums\TwoFaMethod::EMAIL->value);
    }

    /**
     * Common process for email verification code resend
     */
    protected function resendEmailCode($user): void
    {
        $twoFa = $this->getTwoFaService();
        $twoFa->generate($user, \App\Enums\TwoFaMethod::EMAIL->value);

        // Clear the email sent flag in session (allow resend on next showEmailChallenge)
        $sessionKey = $this->getSessionPrefix().'.email_sent';
        session()->forget($sessionKey);
    }

    /**
     * Get available authentication methods
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

                // Generate route name
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
     * Display email authentication challenge screen
     */
    public function showEmailChallenge(Request $request)
    {
        $user = $this->checkSessionAndGetUser();
        if ($user instanceof \Illuminate\Http\RedirectResponse) {
            return $user;
        }

        // Send email only if email sent flag is not in session
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

        // Get recovery code route
        $context = $this->getContext();
        $recoveryCodeRoute = $this->getRecoveryCodeRoute();

        // Get CAPTCHA settings
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
     * Verify email authentication code
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

        // Check lockout status
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
     * Resend email authentication code
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
     * Display recovery code authentication challenge screen
     */
    public function showRecoveryCodeChallenge(Request $request)
    {
        $user = $this->checkSessionAndGetUser();
        if ($user instanceof \Illuminate\Http\RedirectResponse) {
            return $user;
        }

        $availableMethods = $this->getAvailableMethods();
        $context = $this->getContext();

        // Get CAPTCHA settings
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
     * Verify recovery code
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

        // Check lockout status (parity with verifyEmail): recovery-code entry
        // must be throttled by the same 2FA lockout so it cannot be used as an
        // un-throttled brute-force channel.
        $twoFa = $this->getTwoFaService();
        $lockoutStatus = $twoFa->checkLockout($user);

        if ($lockoutStatus['locked_out']) {
            return back()->withErrors([
                'recovery_code' => __('two_fa.lockout.message', [
                    'minutes' => $lockoutStatus['remaining_minutes'],
                ]),
            ]);
        }

        if (! $this->verifyRecoveryCodeValue($user, $request->recovery_code)) {
            return back()->withErrors([
                'recovery_code' => __('two_fa.recovery_code.invalid'),
            ]);
        }

        return $this->completeAuthentication($user, $request);
    }
}
