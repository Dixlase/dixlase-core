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

namespace App\Services;

use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\CspBlocklistAction;
use App\Enums\CspMode;
use App\Enums\LogLevel;
use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Service that applies auto-configuration values for Hidden/Partial items when switching to easy mode
 *
 * Auto-configuration methods for each menu can be called individually,
 * or applied in bulk using applyAll()
 */
class AdminModeAutoConfigService
{
    public function __construct(
        protected SecuritySettingRepositoryInterface $securitySettingRepository,
        protected MediaSettingRepositoryInterface $mediaSettingRepository
    ) {}

    /**
     * Apply auto-configuration values for all Hidden/Partial items in bulk
     *
     * @return array<string, bool> Application result for each menu key
     */
    public function applyAll(): array
    {
        $results = [];

        $methods = [
            'settings.security.password' => 'applyPasswordDefaults',
            'settings.security.login' => 'applyLoginDefaults',
            'settings.security.two-fa' => 'applyTwoFaDefaults',
            'settings.security.notifications' => 'applyNotificationDefaults',
            'settings.security.session' => 'applySessionDefaults',
            'settings.security.csp' => 'applyCspDefaults',
            'settings.security.environment' => 'applyEnvironmentDefaults',
            'settings.security.extensions' => 'applyExtensionsDefaults',
            'media.settings' => 'applyMediaDefaults',
        ];

        foreach ($methods as $menuKey => $method) {
            try {
                $this->{$method}();
                $results[$menuKey] = true;

                Log::channel('admin_activity')->info(__('services/admin_mode_auto_config_service.simple_mode_auto_config_applied', ['menuKey' => $menuKey]), [
                    'menu_key' => $menuKey,
                ]);
            } catch (\Exception $e) {
                $results[$menuKey] = false;

                Log::channel('admin_activity')->error(__('services/admin_mode_auto_config_service.simple_mode_auto_config_failed', ['menuKey' => $menuKey]), [
                    'menu_key' => $menuKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Apply auto-configuration values for password settings
     *
     * Target: settings.security.password (Hidden)
     * - password_min_length: 8 characters (NIST recommended minimum)
     * - password_require_uppercase: enabled (ensures complexity)
     * - password_require_number: enabled (ensures complexity)
     * - password_require_symbol: enabled (ensures complexity)
     * - password_reset_enabled: enabled (ensures convenience)
     * - pwned_password_check_enabled: enabled (prevents leaked passwords)
     */
    public function applyPasswordDefaults(): void
    {
        $this->securitySettingRepository->set('password_min_length', '8');
        $this->securitySettingRepository->set('password_require_uppercase', '1');
        $this->securitySettingRepository->set('password_require_number', '1');
        $this->securitySettingRepository->set('password_require_symbol', '1');
        $this->securitySettingRepository->set('password_reset_enabled', '1');
        $this->securitySettingRepository->set('pwned_password_check_enabled', '1');
    }

    /**
     * Apply auto-configuration values for login settings (detailed parameters for attempt limits)
     *
     * Target: settings.security.login (Partial)
     * Login notification ON/OFF/conditions can be set by user. Auto-configure the following parameters:
     * - login_attempt_limit_enabled: enabled (brute force protection)
     * - login_attempt_max_attempts: 5 times
     * - login_attempt_max_attempts_ip: 20 times (considers shared IP environments)
     * - login_attempt_time_window: 15 minutes
     * - login_attempt_lockout_duration: 30 minutes
     * - login_attempt_lockout_notification_enabled: enabled (attack detection)
     */
    public function applyLoginDefaults(): void
    {
        $this->securitySettingRepository->set('login_attempt_limit_enabled', '1');
        $this->securitySettingRepository->set('login_attempt_max_attempts', '5');
        $this->securitySettingRepository->set('login_attempt_max_attempts_ip', '20');
        $this->securitySettingRepository->set('login_attempt_time_window', '15');
        $this->securitySettingRepository->set('login_attempt_lockout_duration', '30');
        $this->securitySettingRepository->set('login_attempt_lockout_notification_enabled', '1');
    }

    /**
     * Apply auto-config values for two-factor authentication settings (detailed parameters)
     *
     * Target: settings.security.two-fa (Partial)
     * 2FA/passkey ON/OFF/conditions can be controlled by user. Auto-configure the following parameters:
     * - two_fa_passkey_max_devices: 5 devices
     * - two_fa_expire_minutes: 10 minutes
     * - two_fa_resend_interval_seconds: 60 seconds (spam prevention)
     * - two_fa_max_attempts: 5 times
     * - two_fa_attempt_window: 15 minutes
     * - two_fa_lockout_duration: 30 minutes
     * - two_fa_lockout_notification_enabled: enabled (security monitoring)
     * - two_fa_recovery_codes_count: 10 codes
     * - two_fa_recovery_code_regenerate_interval: 24 hours (= 1 day, abuse prevention)
     */
    public function applyTwoFaDefaults(): void
    {
        $this->securitySettingRepository->set('two_fa_passkey_max_devices', '5');
        $this->securitySettingRepository->set('two_fa_expire_minutes', '10');
        $this->securitySettingRepository->set('two_fa_resend_interval_seconds', '60');
        $this->securitySettingRepository->set('two_fa_max_attempts', '5');
        $this->securitySettingRepository->set('two_fa_attempt_window', '15');
        $this->securitySettingRepository->set('two_fa_lockout_duration', '30');
        $this->securitySettingRepository->set('two_fa_lockout_notification_enabled', '1');
        $this->securitySettingRepository->set('two_fa_recovery_codes_count', '10');
        $this->securitySettingRepository->set('two_fa_recovery_code_regenerate_interval', '1');
    }

    /**
     * Apply auto-config values for error notification settings
     *
     * Target: settings.security.notifications (Hidden)
     * - notification_enabled: enabled (early problem detection)
     * - notification_log_levels: Critical and above (Emergency=8, Alert=7, Critical=6)
     */
    public function applyNotificationDefaults(): void
    {
        $this->securitySettingRepository->set('notification_enabled', '1');

        $criticalAndAbove = implode(',', [
            LogLevel::Emergency->value,
            LogLevel::Alert->value,
            LogLevel::Critical->value,
        ]);
        $this->securitySettingRepository->set('notification_log_levels', $criticalAndAbove);
    }

    /**
     * Apply auto-config values for session settings
     *
     * Target: settings.security.session (Hidden)
     * - session_lifetime: 120 minutes (maintain default value)
     * - session_encrypt: false (maintain default value)
     */
    public function applySessionDefaults(): void
    {
        ConfigHelper::setSessionLifetime(120);
        ConfigHelper::setSessionEncrypt(false);
    }

    /**
     * Apply auto-configuration values for CSP settings
     *
     * Target: settings.security.csp (Hidden)
     * - csp_enabled: ON (security foundation)
     * - csp_mode: standard mode (development mode not required)
     * - csp_log_violations: ON (required for investigation)
     * - csp_exclude_dev_tools: ON (reduce noise)
     * - csp_blocklist_check_enabled: ON (security monitoring)
     * - csp_blocklist_action: warning only (avoid risk of blocking legitimate scripts)
     * - csp_blocklist_enabled_categories: all categories ON
     */
    public function applyCspDefaults(): void
    {
        $this->securitySettingRepository->set('csp_enabled', '1');
        $this->securitySettingRepository->set('csp_mode', (string) CspMode::Standard->value);
        $this->securitySettingRepository->set('csp_log_violations', '1');
        $this->securitySettingRepository->set('csp_exclude_dev_tools', '1');
        $this->securitySettingRepository->set('csp_blocklist_check_enabled', '1');
        $this->securitySettingRepository->set('csp_blocklist_action', (string) CspBlocklistAction::Warn->value);
        $this->securitySettingRepository->set('csp_blocklist_enabled_categories', 'tracking,malware,phishing,cryptominer');
    }

    /**
     * Apply auto-configuration values for environment settings
     *
     * Target: settings.security.environment (Hidden)
     * - APP_ENV: production (production environment)
     * - APP_DEBUG: false (prevent information leakage)
     */
    public function applyEnvironmentDefaults(): void
    {
        EnvHelper::update([
            'app_env' => 'production',
            'app_debug' => 'false',
        ]);
    }

    /**
     * Apply auto-configuration values for media settings
     *
     * Target: media.settings (Partial)
     * File type and size limits are user-configurable. Auto-configure the following security items:
     * - mime_validation_enabled: enabled (prevent file spoofing)
     * - svg_sanitization_enabled: enabled (prevents scripts in SVG)
     * - zip_security_enabled: enabled (detects ZIP bombs and malicious files)
     */
    public function applyMediaDefaults(): void
    {
        $this->mediaSettingRepository->set('mime_validation_enabled', '1');
        $this->mediaSettingRepository->set('svg_sanitization_enabled', '1');
        $this->mediaSettingRepository->set('zip_security_enabled', '1');
    }

    /**
     * Apply auto-configured values for extension security settings
     *
     * Target: settings.security.extensions (Partial)
     * Presets and various flags are user-configurable. Auto-configure the following parameters:
     * - extension_audit_max_age_days: 30 days (fixes audit scan deadline to 1 month)
     */
    public function applyExtensionsDefaults(): void
    {
        $this->securitySettingRepository->set('extension_audit_max_age_days', '30');
    }
}
