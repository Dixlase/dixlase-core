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

namespace App\Helpers;

use App\Models\SecuritySetting;
use App\Services\CaptchaBypassService;
use App\Services\CaptchaFailoverService;
use App\Services\CaptchaTestService;
use Illuminate\Support\Facades\Log;

class CaptchaHelper
{
    /**
     * Bulk retrieve CAPTCHA settings
     */
    public static function getSettings(): array
    {
        $driver = SecuritySetting::get('captcha_driver', 'google');

        // Get keys by provider
        $siteKey = '';
        $secretKey = '';

        switch ($driver) {
            case 'google':
                $siteKey = SecuritySetting::get('captcha_google_site_key', '');
                $secretKey = SecuritySetting::get('captcha_google_secret_key', '');
                break;
            case 'google_enterprise':
                $siteKey = SecuritySetting::get('captcha_google_enterprise_site_key', '');
                $secretKey = SecuritySetting::get('captcha_google_enterprise_secret_key', '');
                break;
            case 'turnstile':
                $siteKey = SecuritySetting::get('captcha_turnstile_site_key', '');
                $secretKey = SecuritySetting::get('captcha_turnstile_secret_key', '');
                break;
        }

        return [
            'enabled' => filter_var(SecuritySetting::get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'driver' => $driver,
            'site_key' => $siteKey,
            'secret_key' => $secretKey,
            'google_version' => SecuritySetting::get('captcha_google_version', 'v3'),
            'google_min_score' => (float) SecuritySetting::get('captcha_google_min_score', 0.5),
            'google_project_id' => SecuritySetting::get('captcha_google_project_id', ''),
            'authentication_result' => filter_var(SecuritySetting::get('captcha_authentication_result', false), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Check if CAPTCHA should be displayed on the specified form
     */
    public static function shouldShowCaptcha(?string $formName = null): bool
    {
        // Do not display CAPTCHA if emergency bypass is active
        $scope = self::getBypassScopeForForm($formName);
        $bypassActive = CaptchaBypassService::shouldSkipCaptcha($scope);

        $settings = self::getSettings();

        // Form-specific settings check
        $formCaptchaEnabled = $formName ? self::isEnabledForForm($formName) : true;

        if ($bypassActive) {
            return false;
        }

        // Basic CAPTCHA validity check
        if (! $settings['enabled']) {
            return false;
        }

        if (! $formCaptchaEnabled) {
            return false;
        }

        // Check authentication test results
        if (! $settings['authentication_result']) {
            return false;
        }

        return true;
    }

    /**
     * Get bypass scope from form name
     */
    protected static function getBypassScopeForForm(?string $formName): string
    {
        return match ($formName) {
            'admin_login' => 'admin_login',
            default => 'all',
        };
    }

    /**
     * Check if emergency bypass is active
     */
    public static function isBypassActive(?string $scope = null): bool
    {
        return CaptchaBypassService::isActive($scope);
    }

    /**
     * Check if CAPTCHA is enabled (basic settings only)
     */
    public static function isEnabled(): bool
    {
        $settings = self::getSettings();

        return $settings['enabled'] &&
               ! empty($settings['site_key']) &&
               ! empty($settings['secret_key']) &&
               $settings['authentication_result'];
    }

    /**
     * Get current CAPTCHA driver
     * Return temporary active provider if in failover
     */
    public static function getDriver(): string
    {
        return CaptchaFailoverService::getActiveProvider();
    }

    /**
     * Get site key
     * Return active provider's key if in failover
     */
    public static function getSiteKey(): string
    {
        $activeProvider = CaptchaFailoverService::getActiveProvider();
        $config = CaptchaFailoverService::getProviderConfig($activeProvider);

        // Use provider-specific key if available
        if (! empty($config['site_key'])) {
            return $config['site_key'];
        }

        // Fallback: unified key
        return SecuritySetting::get('captcha_site_key', '');
    }

    /**
     * Get secret key
     * Return active provider's key if in failover
     */
    public static function getSecretKey(): string
    {
        $activeProvider = CaptchaFailoverService::getActiveProvider();
        $config = CaptchaFailoverService::getProviderConfig($activeProvider);

        // Use provider-specific key if available
        if (! empty($config['secret_key'])) {
            return $config['secret_key'];
        }

        // Fallback: unified key
        return SecuritySetting::get('captcha_secret_key', '');
    }

    /**
     * Get Google reCAPTCHA version
     */
    public static function getGoogleVersion(): string
    {
        return SecuritySetting::get('captcha_google_version', 'v3');
    }

    /**
     * Get Google reCAPTCHA minimum score
     */
    public static function getGoogleMinScore(): float
    {
        return (float) SecuritySetting::get('captcha_google_min_score', 0.5);
    }

    /**
     * Get Google reCAPTCHA Enterprise project ID
     */
    public static function getGoogleProjectId(): string
    {
        return SecuritySetting::get('captcha_google_project_id', '');
    }

    /**
     * Get CAPTCHA verification test result
     */
    public static function getTestResult(): bool
    {
        $captchaTestService = app(CaptchaTestService::class);

        return $captchaTestService->getTestResult();
    }

    /**
     * Check if CAPTCHA is enabled for the specified form
     *
     * For admin_login and admin_password_reset, load from SecuritySetting.
     * For user_login, user_register, and user_password_reset, load from
     * the active users plugin's user-setting model. Other forms are
     * managed independently by the plugin side.
     */
    public static function isEnabledForForm(string $formName): bool
    {
        // Check the form's enabled state using CaptchaService
        $captchaService = app(\App\Services\CaptchaService::class);

        return $captchaService->isEnabled($formName);
    }

    /**
     * Kept for backward compatibility with older versions (deprecated)
     *
     * @deprecated Use isEnabledForForm() instead
     */
    protected static function isEnabledForFormLegacy(string $formName): bool
    {
        // Admin panel form
        if (in_array($formName, ['admin_login', 'admin_password_reset'])) {
            $settingKey = match ($formName) {
                'admin_login' => 'captcha_admin_login_enabled',
                'admin_password_reset' => 'captcha_password_reset_enabled',
            };

            return filter_var(
                SecuritySetting::get($settingKey, false),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        // User plugin form
        if (in_array($formName, ['user_login', 'user_register', 'user_password_reset'])) {
            // Check if the legacy users-plugin setting model is present
            if (class_exists('\Plugins\DixlaseUsers\App\Models\DixlaseUsersUserSetting')) {
                $settingKey = match ($formName) {
                    'user_login' => 'captcha_login_enabled',
                    'user_register' => 'captcha_register_enabled',
                    'user_password_reset' => 'captcha_password_reset_enabled',
                };

                return filter_var(
                    \Plugins\DixlaseUsers\App\Models\DixlaseUsersUserSetting::getValue($settingKey, false),
                    FILTER_VALIDATE_BOOLEAN
                );
            }
        }

        // Other forms are managed independently by the plugin side, so return false here
        // Plugins should determine CAPTCHA enabled/disabled from their own settings table
        return false;
    }

    /**
     * Generic CAPTCHA enabled check (specify settings model class and key)
     *
     * @param  string  $formName  Form name (for bypass scope determination)
     * @param  string  $settingModelClass  Settings model class name
     * @param  string  $settingKey  Settings key name
     * @param  mixed  $defaultValue  Default value
     */
    public static function isEnabledForFormWithModel(
        string $formName,
        string $settingModelClass,
        string $settingKey,
        $defaultValue = false
    ): bool {
        // Check basic CAPTCHA settings
        if (! self::isEnabled()) {
            return false;
        }

        // Disable CAPTCHA if emergency bypass is active
        $scope = self::getBypassScopeForForm($formName);
        if (CaptchaBypassService::shouldSkipCaptcha($scope)) {
            return false;
        }

        // Check authentication test results
        $settings = self::getSettings();
        if (! $settings['authentication_result']) {
            return false;
        }

        // Get value from settings model
        if (class_exists($settingModelClass)) {
            if (method_exists($settingModelClass, 'getValue')) {
                $value = $settingModelClass::getValue($settingKey, $defaultValue);
            } elseif (method_exists($settingModelClass, 'get')) {
                $value = $settingModelClass::get($settingKey, $defaultValue);
            } else {
                return false;
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }

    /**
     * Generate CAPTCHA widget
     *
     * @param  string  $action  CAPTCHA action name
     * @return string|null Widget HTML (null if CAPTCHA is disabled)
     */
    public static function renderWidget(string $action): ?string
    {
        try {
            $captchaDriverInstance = app(\App\Captcha\CaptchaDriver::class);

            return $captchaDriverInstance->renderWidget(['action' => $action]);
        } catch (\Exception $e) {
            Log::error('Failed to render CAPTCHA widget', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verify CAPTCHA
     *
     * @param  \Illuminate\Http\Request  $request  Request
     * @param  string  $action  CAPTCHA action name
     * @return \App\Captcha\CaptchaResult|null Verification result (null if CAPTCHA is disabled)
     */
    public static function verify(\Illuminate\Http\Request $request, string $action): ?\App\Captcha\CaptchaResult
    {
        // Return null if CAPTCHA is disabled (skip verification)
        if (! self::shouldShowCaptcha($action)) {
            return null;
        }

        try {
            $captchaDriverInstance = app(\App\Captcha\CaptchaDriver::class);

            return $captchaDriverInstance->verify($request);
        } catch (\Exception $e) {
            Log::error('Failed to verify CAPTCHA', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            // Treat errors as failures
            return new \App\Captcha\CaptchaResult(
                false,
                __('auth.captcha_verification_failed')
            );
        }
    }

    /**
     * Check if CAPTCHA is enabled for the specified form (specify settings retrieval function)
     *
     * @param  string  $formName  Form name
     * @param  callable  $settingGetter  Settings retrieval function
     */
    public static function isEnabledForFormWithSettings(string $formName, callable $settingGetter): bool
    {
        // Check basic CAPTCHA settings
        if (! self::isEnabled()) {
            return false;
        }

        // Disable CAPTCHA if emergency bypass is active
        $scope = self::getBypassScopeForForm($formName);
        if (CaptchaBypassService::shouldSkipCaptcha($scope)) {
            return false;
        }

        // Check form-specific settings
        $formEnabled = $settingGetter($formName);

        return filter_var($formEnabled, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check CAPTCHA settings validity
     */
    public static function validateSettings(): array
    {
        $settings = self::getSettings();
        $errors = [];

        if ($settings['enabled']) {
            if (empty($settings['site_key'])) {
                $errors[] = 'Site key is required when CAPTCHA is enabled';
            }

            if (empty($settings['secret_key'])) {
                $errors[] = 'Secret key is required when CAPTCHA is enabled';
            }

            if (! $settings['authentication_result']) {
                $errors[] = 'CAPTCHA authentication test must be completed';
            }

            if ($settings['driver'] === 'google_enterprise' && empty($settings['google_project_id'])) {
                $errors[] = 'Project ID is required for Google reCAPTCHA Enterprise';
            }
        }

        return $errors;
    }

    /**
     * Get CAPTCHA settings in a safe format for logging
     */
    public static function getSettingsForLogging(): array
    {
        $settings = self::getSettings();

        return [
            'enabled' => $settings['enabled'],
            'driver' => $settings['driver'],
            'site_key' => $settings['site_key'] ? substr($settings['site_key'], 0, 10).'...' : 'Not set',
            'secret_key' => $settings['secret_key'] ? substr($settings['secret_key'], 0, 10).'...' : 'Not set',
            'google_version' => $settings['google_version'],
            'google_min_score' => $settings['google_min_score'],
            'authentication_result' => $settings['authentication_result'],
        ];
    }
}
