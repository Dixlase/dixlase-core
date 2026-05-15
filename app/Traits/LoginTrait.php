<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Common trait for login and authentication processing
 *
 * Provides configuration methods and functionality commonly used in login and two-factor authentication processing
 * Controllers using this trait must implement the following abstract methods:
 */
trait LoginTrait
{
    /**
     * Get login screen route name (implement in subclass)
     *
     * @return string Route name (e.g., 'admin.login', 'dixlase-users::mypage.login')
     */
    abstract protected function getLoginRoute(): string;

    /**
     * Get dashboard route name (implement in subclass)
     *
     * @return string Route name (e.g., 'admin.dashboard', 'dixlase-users::mypage.dashboard')
     */
    abstract protected function getDashboardRoute(): string;

    /**
     * Get session key prefix (implement in subclass)
     *
     * @return string Prefix (e.g., 'login', 'two_fa')
     */
    abstract protected function getSessionPrefix(): string;

    /**
     * Get user model class name (implement in subclass)
     *
     * @return string Model class name (e.g., 'App\Models\Member', 'Plugins\DixlaseUsers\App\Models\DixlaseUsersUser')
     */
    abstract protected function getUserModelClass(): string;

    /**
     * Get authentication guard name (implement in subclass)
     *
     * @return string Guard name (e.g., 'member', 'user')
     */
    abstract protected function getGuardName(): string;

    /**
     * Get context (implement in subclass)
     *
     * @return string Context ('admin' or 'user')
     */
    abstract protected function getContext(): string;

    /**
     * Get two-factor authentication route prefix
     *
     * @return string Route prefix (e.g., 'admin', 'dixlase-users::mypage')
     */
    abstract protected function getTwoFaRoutePrefix(): string;

    /**
     * Get redirect destination after logout (implement in subclass)
     *
     * @return string Route name or URL for redirect destination
     */
    abstract protected function getLogoutRedirectRoute(): string;

    /**
     * Get settings model class name (implement in child class)
     *
     * @return string Settings model class name
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * Get lockout service class name (implement in child class)
     *
     * @return string Lockout service class name
     */
    abstract protected function getLockoutServiceClass(): string;

    /**
     * Get login notification service class name (implement in child class)
     *
     * @return string Login notification service class name
     */
    abstract protected function getLoginNotificationServiceClass(): string;

    /**
     * Get login view name (implement in child class)
     *
     * @return string Login view name
     */
    abstract protected function getLoginViewName(): string;

    /**
     * Get CAPTCHA action name (implement in child class)
     *
     * @return string CAPTCHA action name
     */
    abstract protected function getCaptchaAction(): string;

    /**
     * Get whether password reset feature is enabled (implement in child class)
     *
     * @return bool Password reset feature enabled/disabled
     */
    abstract protected function isPasswordResetEnabled(): bool;

    /**
     * Whether to support login with account name (implement in child class)
     *
     * @return bool Account name login support
     */
    abstract protected function supportsAccountNameLogin(): bool;

    /**
     * Whether to support login with email address (implement in child class)
     *
     * @return bool Email address login support
     */
    abstract protected function supportsEmailLogin(): bool;

    /**
     * Whether to support login with pending_email (implement in child class)
     *
     * @return bool pending_email login support
     */
    abstract protected function supportsPendingEmailLogin(): bool;

    /**
     * Get recovery code screen route name (implement in child class)
     *
     * @return string Route name (e.g. 'admin.two-fa.recovery-code.show', 'dixlase-users::mypage.two-fa.recovery-code.show')
     */
    abstract protected function getRecoveryCodeRoute(): string;

    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        $user = \Illuminate\Support\Facades\Auth::guard($this->getGuardName())->user();
        if ($user) {
            return redirect()->route($this->getDashboardRoute());
        }

        $viewParams = [];

        // Get password reset feature enabled/disabled settings
        $passwordResetEnabled = $this->isPasswordResetEnabled();
        $canSendMail = \App\Services\MailServerValidatorService::canSendMail();

        // Enable password reset only if mail server is configured and tested
        $viewParams['canResetPassword'] = $passwordResetEnabled && $canSendMail;

        // Get CAPTCHA settings
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $viewParams['captchaEnabled'] = $captchaEnabled;
        $viewParams['captchaDriver'] = \App\Helpers\CaptchaHelper::getDriver();
        $viewParams['captchaWidget'] = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);

        // Determine passkey authentication button display
        // Always display in step 1 (controlled by hasPasskey after identifier check)
        // Display in step 2 based on two-factor authentication and passkey settings
        $settingModelClass = $this->getSettingModelClass();
        $viewParams['passkeyEnabled'] = $this->shouldShowPasskeyButton($settingModelClass);

        // Back link URL (fallback to / if welcome route is undefined)
        $viewParams['backUrl'] = \Illuminate\Support\Facades\Route::has('welcome') ? route('welcome') : url('/');

        return view($this->getLoginViewName(), $viewParams);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $lockoutService = app($this->getLockoutServiceClass());
        $login = $request->login;

        // Determine if input value is email address or account name
        $isEmail = str_contains($login, '@');

        // CAPTCHA verification (skip if already verified in identifier check)
        $captchaVerifiedKey = 'captcha_verified_'.$login;
        $captchaVerifiedTime = session()->get($captchaVerifiedKey);
        $captchaVerified = $captchaVerifiedTime && (time() - $captchaVerifiedTime) < 300; // Within 5 minutes

        if (! $captchaVerified) {
            $captchaAction = $this->getCaptchaAction();
            $captchaResult = \App\Helpers\CaptchaHelper::verify($request, $captchaAction);

            if ($captchaResult && ! $captchaResult->isValid()) {
                return back()->withErrors([
                    'captcha' => $captchaResult->getErrorMessage(),
                ])->withInput($request->except('password'));
            }
        }

        // Clear CAPTCHA verified flag
        session()->forget($captchaVerifiedKey);

        // Check lockout status
        if ($lockoutService->isLockedOut($login)) {
            $remainingMinutes = $lockoutService->getLockoutRemainingMinutes($login);

            return back()->withErrors([
                'login' => __('auth.lockout', ['minutes' => $remainingMinutes]),
            ]);
        }

        // Also check IP address-based lockout
        if ($lockoutService->isIpLockedOut($request->ip())) {
            return back()->withErrors([
                'login' => __('auth.ip_lockout'),
            ]);
        }

        // Search for user
        $user = $this->findUserByLogin($login, $isEmail);

        // If user is not found or password is incorrect
        if (! $user || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            // Record failed login
            $failureReason = \App\Models\MemberLoginAttempt::FAILURE_INVALID_PASSWORD;
            $lockoutInfo = $lockoutService->handleFailedLogin($request, $login, $failureReason);

            $errorMessage = __('auth.failed');

            // Check IP-based lockout
            if ($lockoutInfo['is_ip_locked_out']) {
                $lockoutDuration = $lockoutInfo['settings']['lockout_duration'] ?? 30;
                $errorMessage = __('auth.lockout', ['minutes' => $lockoutDuration]);
            } elseif ($lockoutInfo['is_locked_out']) {
                $errorMessage = __('auth.lockout', ['minutes' => $lockoutInfo['lockout_minutes']]);
            } elseif ($lockoutInfo['remaining_attempts'] > 0) {
                $errorMessage = __('auth.failed_with_attempts', ['attempts' => $lockoutInfo['remaining_attempts']]);
            }

            // Save error message as session flash message
            return back()
                ->with('error', $errorMessage)
                ->withInput($request->except('password'));
        }

        // Get email address for lockout
        $email = $user->email;

        // 2FA determination
        $twoFactor = app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => $this->getSettingModelClass(),
            'context' => $this->getContext(),
        ]);

        // Skip 2FA if mail server testing is not completed
        $mailServerTested = \App\Services\MailServerValidatorService::isMailServerTested();

        if ($twoFactor->has($user) && $mailServerTested) {
            // 2FA lockout check
            $lockoutStatus = $twoFactor->checkLockout($user);

            if ($lockoutStatus['locked_out']) {
                return back()->withErrors([
                    'email' => __('auth.two_fa_locked_out', [
                        'minutes' => $lockoutStatus['remaining_minutes'],
                    ]),
                ]);
            }

            session([
                $this->getSessionPrefix().'.id' => $user->getAuthIdentifier(),
                $this->getSessionPrefix().'.remember' => $request->boolean('remember'),
                $this->getSessionPrefix().'.auth_method' => 'password', // Record password authentication
            ]);

            // Get valid authentication method
            $effectiveMethod = $twoFactor->getEffectiveAuthMethod($user);

            // Generate code only for email authentication
            if ($effectiveMethod === \App\Enums\TwoFaMethod::EMAIL->value) {
                $twoFactor->generate($user);
                // Set email sent flag in session (prevent duplicate sending)
                $request->session()->put($this->getSessionPrefix().'.email_sent', true);
            }

            // Redirect to appropriate route based on default authentication method
            $redirectRoute = \App\Helpers\TwoFaHelper::getTwoFaMethodRoute($this->getTwoFaRoutePrefix(), $effectiveMethod);

            return redirect()->route($redirectRoute);
        } else {
            // Record successful login (clear failure records)
            $lockoutService->handleSuccessfulLogin($email, $request);

            // Record and notify login environment
            app($this->getLoginNotificationServiceClass())->handle($user, $request);

            // Login immediately if 2FA is not required
            $guardName = $this->getGuardName();
            $remember = $request->boolean('remember');

            Auth::guard($guardName)->login($user, $remember);
            $request->session()->regenerate();

            // Check email verification token after login
            $this->processEmailVerificationIfPending($user, $request);

            return redirect()->route($this->getDashboardRoute());
        }
    }

    /**
     * Find user from login input
     *
     * @param  string  $login  Login input value
     * @param  bool  $isEmail  Whether it is an email address
     * @return mixed User model or null
     */
    protected function findUserByLogin(string $login, bool $isEmail)
    {
        $userModelClass = $this->getUserModelClass();

        if ($isEmail) {
            // Null if email address login is disabled
            if (! $this->supportsEmailLogin()) {
                return;
            }

            // Search by email address
            $query = $userModelClass::where('email', $login);

            // If pending_email is also supported
            if ($this->supportsPendingEmailLogin()) {
                $query->orWhere('pending_email', $login);
            }

            return $query->first();
        } else {
            // If login by account name is supported
            if ($this->supportsAccountNameLogin()) {
                return $userModelClass::where('account_name', $login)->first();
            }

            // Null if account name login is not supported
            return;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Auth::guard($this->getGuardName())->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirectRoute = $this->getLogoutRedirectRoute();

        // Determine if route name or URL
        if (str_starts_with($redirectRoute, '/') || str_starts_with($redirectRoute, 'http')) {
            return redirect($redirectRoute);
        }

        return to_route($redirectRoute);
    }

    /**
     * Check whether to display the passkey button
     *
     * Priority:
     * 1. Do not display if mail server is not configured
     * 2. Do not display if two-factor authentication or passkey is disabled in global settings
     * 3. Otherwise display in step 1 (controlled by hasPasskey after identifier check)
     */
    protected function shouldShowPasskeyButton(string $settingModelClass): bool
    {
        // Check mail server settings (highest priority)
        $twoFaHelper = app(\App\Helpers\TwoFaHelper::class);
        if (! $twoFaHelper->isMailConfigured()) {
            return false;
        }

        // Get passkey mode (0=disabled, 1=enabled, 2=follow profile)
        $twoFaPasskeyMode = (int) $settingModelClass::getValue('two_fa_passkey_mode', '2');

        // Do not display if passkey is disabled
        if ($twoFaPasskeyMode === 0) {
            return false;
        }

        // Get two-factor authentication mode (0=disabled, 1=different device/IP, 2=always enabled, 3=follow profile settings)
        $twoFaMode = (int) $settingModelClass::getValue('two_fa_mode', '0');

        // Do not display if two-factor authentication is disabled
        if ($twoFaMode === 0) {
            return false;
        }

        // Display if two-factor authentication and passkey are enabled
        // Display in step 1 and control by hasPasskey after identifier check
        return true;
    }
}
