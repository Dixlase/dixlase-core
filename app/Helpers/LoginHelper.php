<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginHelper
{
    /**
     * Authenticate user (email address and password)
     *
     * @param  string  $email  Email address
     * @param  string  $password  Password
     * @param  string  $userModel  User model class name
     * @return array ['success' => bool, 'user' => mixed|null, 'error' => string|null]
     */
    public function authenticateUser(string $email, string $password, string $userModel): array
    {
        // Search for user by email or pending_email
        $user = $userModel::where('email', $email)
            ->orWhere('pending_email', $email)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            Log::info('[Login] Authentication failed', [
                'email' => $email,
                'user_found' => (bool) $user,
            ]);

            return [
                'success' => false,
                'user' => null,
                'error' => 'invalid_credentials',
            ];
        }

        Log::info('[Login] Authentication successful', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'success' => true,
            'user' => $user,
            'error' => null,
        ];
    }

    /**
     * Check if Two-FA is required
     *
     * @param  mixed  $user  User model
     * @param  string  $twoFactorServiceClass  Two-FA service class name
     * @return array ['needs_two_fa' => bool, 'lockout_status' => array|null]
     */
    public function check2FARequired($user, string $twoFactorServiceClass): array
    {
        $twoFactorService = app($twoFactorServiceClass);

        // Skip Two-FA if mail server testing is not completed
        $mailServerTested = \App\Services\MailServerValidatorService::isMailServerTested();

        Log::info('[Login] Two-FA check', [
            'user_id' => $user->id,
            'has_two_fa' => $twoFactorService->has($user),
            'mail_server_tested' => $mailServerTested,
        ]);

        if (! $twoFactorService->has($user) || ! $mailServerTested) {
            return [
                'needs_two_fa' => false,
                'lockout_status' => null,
            ];
        }

        // Two-FA lockout check
        $lockoutStatus = $twoFactorService->checkLockout($user);

        if ($lockoutStatus['locked_out']) {
            Log::warning('[Login] User is locked out from Two-FA', [
                'user_id' => $user->id,
                'remaining_minutes' => $lockoutStatus['remaining_minutes'],
            ]);
        }

        return [
            'needs_two_fa' => true,
            'lockout_status' => $lockoutStatus,
        ];
    }

    /**
     * Prepare Two-FA session
     *
     * @param  mixed  $user  User model
     * @param  bool  $remember  Remember me
     */
    public function prepare2FASession($user, bool $remember): void
    {
        session([
            'login.id' => $user->getAuthIdentifier(),
            'login.remember' => $remember,
        ]);

        Log::info('[Login] Two-FA session prepared', [
            'user_id' => $user->id,
            'remember' => $remember,
        ]);
    }

    /**
     * Complete login (without Two-FA)
     *
     * @param  mixed  $user  User model
     * @param  bool  $remember  Remember me
     * @param  string  $guard  Guard name
     * @param  Request  $request  Request
     * @param  string  $lockoutServiceClass  Lockout service class name
     * @param  string|null  $notificationServiceClass  Notification service class name
     */
    public function completeLogin(
        $user,
        bool $remember,
        string $guard,
        Request $request,
        string $lockoutServiceClass,
        ?string $notificationServiceClass = null
    ): void {
        // Record successful login (clear failure records)
        app($lockoutServiceClass)->handleSuccessfulLogin($user->email);

        // Record and notify login environment
        if ($notificationServiceClass) {
            app($notificationServiceClass)->handle($user, $request);
        }

        // Login
        Auth::guard($guard)->login($user, $remember);
        $request->session()->regenerate();

        Log::info('[Login] Login completed', [
            'user_id' => $user->id,
            'guard' => $guard,
            'remember' => $remember,
        ]);
    }

    /**
     * Generate error message on login failure
     *
     * @param  array  $lockoutInfo  Lockout information
     * @param  string  $translationPrefix  Translation key prefix
     * @return string Error message
     */
    public function getLoginFailedMessage(array $lockoutInfo, string $translationPrefix = 'auth'): string
    {
        if ($lockoutInfo['is_locked_out']) {
            return __("{$translationPrefix}.lockout", ['minutes' => $lockoutInfo['lockout_minutes']]);
        }

        if ($lockoutInfo['remaining_attempts'] > 0) {
            return __("{$translationPrefix}.failed_with_attempts", ['attempts' => $lockoutInfo['remaining_attempts']]);
        }

        return __("{$translationPrefix}.failed");
    }

    /**
     * Generate 2FA lockout error message
     *
     * @param  array  $lockoutStatus  Lockout status
     * @param  string  $translationPrefix  Translation key prefix
     * @return string Error message
     */
    public function get2FALockoutMessage(array $lockoutStatus, string $translationPrefix = 'auth'): string
    {
        return __("{$translationPrefix}.two_fa_locked_out", [
            'minutes' => $lockoutStatus['remaining_minutes'],
        ]);
    }

    /**
     * Check if password reset is enabled
     *
     * @param  string  $settingKey  Settings key
     * @return bool Whether password reset is enabled
     */
    public function isPasswordResetEnabled(string $settingKey = 'password_reset_enabled'): bool
    {
        $enabled = (bool) \App\Models\SecuritySetting::getValue($settingKey, true);
        $mailServerReady = \App\Services\MailServerValidatorService::canSendMail();

        return $enabled && $mailServerReady;
    }

    /**
     * Check if CAPTCHA is required
     *
     * @param  string  $formKey  Form key
     * @return bool Whether CAPTCHA is required
     */
    public function isCaptchaRequired(string $formKey): bool
    {
        return \App\Helpers\CaptchaHelper::shouldShowCaptcha($formKey);
    }

    /**
     * Generate CAPTCHA widget
     *
     * @param  string  $action  Action name
     * @return string CAPTCHA widget HTML
     */
    public function generateCaptchaWidget(string $action): string
    {
        $captchaDriver = app(\App\Captcha\CaptchaDriver::class);

        return $captchaDriver->renderWidget(['action' => $action]);
    }

    /**
     * Verify CAPTCHA
     *
     * @param  Request  $request  Request
     * @return array ['valid' => bool, 'error_message' => string|null]
     */
    public function verifyCaptcha(Request $request): array
    {
        $captchaDriver = app(\App\Captcha\CaptchaDriver::class);
        $result = $captchaDriver->verify($request);

        Log::info('[Login] CAPTCHA verification', [
            'is_valid' => $result->isValid(),
            'error_message' => $result->getErrorMessage(),
            'score' => $result->getScore(),
        ]);

        return [
            'valid' => $result->isValid(),
            'error_message' => $result->getErrorMessage(),
        ];
    }

    /**
     * Clean up session
     *
     * @param  array  $keys  Session keys to clear
     */
    public function cleanupSession(array $keys = ['login.id', 'login.remember']): void
    {
        session()->forget($keys);

        Log::info('[Login] Session cleaned up', [
            'keys' => $keys,
        ]);
    }
}
