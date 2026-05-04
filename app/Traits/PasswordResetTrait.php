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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

use App\Services\PasswordService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Trait that provides common logic for password reset
 *
 * This Trait provides common logic for member and user password reset processing.
 *
 * Controllers using this must implement the following abstract methods:
 * - getSettingsGetter(): Returns a closure for retrieving settings
 * - getPasswordResetBroker(): Returns the name of the password broker
 * - getUserModelClass(): Returns the class name of the user model
 * - getForgotPasswordViewName(): Returns the view name for the password reset link request screen
 * - getResetPasswordViewName(): Returns the view name for the password reset screen
 * - getPasswordResetRoute(): Returns the route name for password reset processing
 * - getLoginRoute(): Returns the route name for the login screen
 * - getCaptchaAction(): Returns the CAPTCHA action name
 */
trait PasswordResetTrait
{
    /**
     * Get the closure for retrieving settings
     *
     * @return callable function($key, $default)
     */
    abstract protected function getSettingsGetter(): callable;

    /**
     * Get the name of the password broker
     *
     * @return string 'members' or 'users'
     */
    abstract protected function getPasswordResetBroker(): string;

    /**
     * Get the class name of the user model
     */
    abstract protected function getUserModelClass(): string;

    /**
     * Get the view name for the password reset link request screen
     */
    abstract protected function getForgotPasswordViewName(): string;

    /**
     * Get the view name for the password reset screen
     */
    abstract protected function getResetPasswordViewName(): string;

    /**
     * Get the route name for password reset processing
     */
    abstract protected function getPasswordResetRoute(): string;

    /**
     * Get the route name for the login screen
     */
    abstract protected function getLoginRoute(): string;

    /**
     * Get CAPTCHA action name
     */
    abstract protected function getCaptchaAction(): string;

    /**
     * Get password settings
     *
     * @param  callable  $settingsGetter  Closure for retrieving settings function($key, $default)
     * @return array Array of password settings
     */
    protected function getPasswordSettings(callable $settingsGetter): array
    {
        return [
            'min_length' => (int) $settingsGetter('password_min_length', 8),
            'require_uppercase' => (bool) $settingsGetter('password_require_uppercase', false),
            'require_number' => (bool) $settingsGetter('password_require_number', false),
            'require_symbol' => (bool) $settingsGetter('password_require_symbol', false),
            'check_pwned' => (bool) $settingsGetter('password_check_pwned', false),
        ];
    }

    /**
     * Check if password reset feature is enabled
     *
     * @param  callable  $settingsGetter  Closure for retrieving settings
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validatePasswordResetAvailability(callable $settingsGetter): void
    {
        PasswordService::abortIfPasswordResetUnavailable($settingsGetter);
    }

    /**
     * Get validation rules for password reset
     *
     * @param  array  $passwordSettings  Settings retrieved by getPasswordSettings()
     * @return array Validation rules
     */
    protected function getPasswordResetValidationRules(array $passwordSettings): array
    {
        return PasswordService::getPasswordResetValidationRules(
            $passwordSettings['min_length'],
            $passwordSettings['require_uppercase'],
            $passwordSettings['require_number'],
            $passwordSettings['require_symbol'],
            $passwordSettings['check_pwned']
        );
    }

    /**
     * Get validation rules for sending password reset link
     *
     * @return array Validation rules
     */
    protected function getPasswordResetLinkValidationRules(): array
    {
        return PasswordService::getPasswordResetLinkValidationRules();
    }

    /**
     * Execute password reset process
     *
     * @param  object  $user  User or member model
     * @param  string  $newPassword  New password
     */
    protected function performPasswordReset(object $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
    }

    /**
     * Check if email is verified (if necessary)
     *
     * @param  object|null  $user  User or member model
     * @return bool True if email verification is required and not verified
     */
    protected function requiresEmailVerification(?object $user): bool
    {
        if (! $user) {
            return false;
        }

        // If hasVerifiedEmail method exists and email is not verified
        if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            return true;
        }

        return false;
    }

    /**
     * Display password reset link request screen (common process)
     */
    protected function showForgotPasswordForm(): \Illuminate\View\View
    {
        $settingsGetter = $this->getSettingsGetter();
        $this->validatePasswordResetAvailability($settingsGetter);

        // Get CAPTCHA settings
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $captchaWidget = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);

        return view($this->getForgotPasswordViewName(), array_merge(
            $this->getForgotPasswordViewData(),
            [
                'captchaEnabled' => $captchaEnabled,
                'captchaWidget' => $captchaWidget,
                'loginRoute' => route($this->getLoginRoute()),
            ]
        ));
    }

    /**
     * Get additional data for password reset link request screen
     */
    protected function getForgotPasswordViewData(): array
    {
        // Defaults to empty array, can be overridden in each controller
        return [];
    }

    /**
     * Send password reset link (common process)
     */
    protected function sendPasswordResetLink(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $settingsGetter = $this->getSettingsGetter();
        $this->validatePasswordResetAvailability($settingsGetter);

        // CAPTCHA verification
        $captchaAction = $this->getCaptchaAction();
        $captchaResult = \App\Helpers\CaptchaHelper::verify($request, $captchaAction);

        if ($captchaResult && ! $captchaResult->isValid()) {
            return back()->withErrors([
                'captcha' => $captchaResult->getErrorMessage(),
            ])->withInput($request->only('email'));
        }

        // Get validation rules
        $request->validate($this->getPasswordResetLinkValidationRules());

        // Check user corresponding to email address
        $userModelClass = $this->getUserModelClass();
        $user = $userModelClass::where('email', $request->email)->first();

        // Error if user exists and email verification is not completed
        if ($this->requiresEmailVerification($user)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.email_not_verified')]);
        }

        // Send password reset link
        $status = \Illuminate\Support\Facades\Password::broker($this->getPasswordResetBroker())->sendResetLink(
            $request->only('email')
        );

        return $status == \Illuminate\Support\Facades\Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }

    /**
     * Display password reset screen (common process)
     */
    protected function showResetPasswordForm(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        $settingsGetter = $this->getSettingsGetter();
        $this->validatePasswordResetAvailability($settingsGetter);

        // Get password settings
        $passwordSettings = $this->getPasswordSettings($settingsGetter);

        return view($this->getResetPasswordViewName(), array_merge(
            $this->getResetPasswordViewData($request),
            [
                'passwordMinLength' => $passwordSettings['min_length'],
                'passwordRequireUppercase' => $passwordSettings['require_uppercase'],
                'passwordRequireNumber' => $passwordSettings['require_number'],
                'passwordRequireSymbol' => $passwordSettings['require_symbol'],
            ]
        ));
    }

    /**
     * Get additional data for password reset screen
     */
    protected function getResetPasswordViewData(\Illuminate\Http\Request $request): array
    {
        // Defaults to empty array, can be overridden in each controller
        return [];
    }

    /**
     * Password reset process (common process)
     */
    protected function resetPassword(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $settingsGetter = $this->getSettingsGetter();

        // Get password settings and validate
        $passwordSettings = $this->getPasswordSettings($settingsGetter);
        $request->validate($this->getPasswordResetValidationRules($passwordSettings));

        // Execute password reset
        $status = \Illuminate\Support\Facades\Password::broker($this->getPasswordResetBroker())->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $this->performPasswordReset($user, $request->password);
            }
        );

        // Redirect to login screen on successful password reset
        return $status == \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? redirect()->route($this->getLoginRoute())->with('success', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
