<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

declare(strict_types=1);

namespace App\Settings;

use App\Enums\SettingScope;
use App\Services\Site\SettingDefinition;
use App\Services\Site\SettingDefinitionRegistry;

/**
 * Registers all security-policy setting keys.
 *
 * Every key here is Global scope: security policy applies network-wide.
 * Site-specific overrides (e.g. allowing different IP rules per site)
 * may be revisited in a future phase by promoting selected keys to
 * Overridable.
 */
class SecuritySettingDefinitions
{
    public static function register(SettingDefinitionRegistry $registry): void
    {
        foreach (self::definitions() as $name => $meta) {
            $registry->register(new SettingDefinition(
                name: $name,
                scope: SettingScope::Global,
                default: $meta['default'] ?? null,
                type: $meta['type'] ?? 'string',
            ));
        }
    }

    /**
     * @return array<string, array{default?: mixed, type?: string}>
     */
    private static function definitions(): array
    {
        return [
            // System notifications
            'notification_enabled' => ['default' => true, 'type' => 'bool'],
            'notification_log_levels' => ['default' => '', 'type' => 'string'],

            // Password policy
            'password_min_length' => ['default' => 8, 'type' => 'int'],
            'password_require_uppercase' => ['default' => true, 'type' => 'bool'],
            'password_require_lowercase' => ['default' => false, 'type' => 'bool'],
            'password_require_number' => ['default' => true, 'type' => 'bool'],
            'password_require_symbol' => ['default' => true, 'type' => 'bool'],
            'password_reset_enabled' => ['default' => false, 'type' => 'bool'],
            'pwned_password_check_enabled' => ['default' => true, 'type' => 'bool'],
            'password_check_pwned' => ['default' => true, 'type' => 'bool'],

            // Password policy defaults (used by reset-to-default UI)
            'password_min_length_default' => ['default' => 8, 'type' => 'int'],
            'password_require_uppercase_default' => ['default' => true, 'type' => 'bool'],
            'password_require_number_default' => ['default' => true, 'type' => 'bool'],
            'password_require_symbol_default' => ['default' => true, 'type' => 'bool'],
            'password_reset_enabled_default' => ['default' => false, 'type' => 'bool'],

            // Login identifier mode (0=email, 1=email or account, 2=account name only)
            'login_identifier_mode' => ['default' => 1, 'type' => 'int'],

            // Login attempt rate limiting
            'login_attempt_limit_enabled' => ['default' => true, 'type' => 'bool'],
            'login_attempt_max_attempts' => ['default' => 5, 'type' => 'int'],
            'login_attempt_max_attempts_ip' => ['default' => 10, 'type' => 'int'],
            'login_attempt_time_window' => ['default' => 15, 'type' => 'int'],
            'login_attempt_lockout_duration' => ['default' => 30, 'type' => 'int'],
            'login_attempt_lockout_notification_enabled' => ['default' => true, 'type' => 'bool'],
            'login_notification_mode' => ['type' => 'string'],
            'login_notification_send_to_system' => ['default' => true, 'type' => 'bool'],
            'login_notification_system_email' => ['default' => '', 'type' => 'string'],

            // Login attempt defaults (used by reset-to-default UI)
            'login_attempt_limit_enabled_default' => ['default' => true, 'type' => 'bool'],
            'login_attempt_max_attempts_default' => ['default' => 5, 'type' => 'int'],
            'login_attempt_max_attempts_ip_default' => ['default' => 10, 'type' => 'int'],
            'login_attempt_time_window_default' => ['default' => 15, 'type' => 'int'],
            'login_attempt_lockout_duration_default' => ['default' => 30, 'type' => 'int'],
            'login_attempt_lockout_notification_enabled_default' => ['default' => true, 'type' => 'bool'],

            // Two-factor authentication
            'two_fa_mode' => ['type' => 'string'],
            'two_fa_passkey_enabled' => ['default' => '0', 'type' => 'string'],
            'two_fa_passkey_mode' => ['type' => 'string'],
            'default_two_fa_method' => ['default' => 0, 'type' => 'int'],
            'force_two_fa' => ['default' => false, 'type' => 'bool'],
            'two_fa_passkey_max_devices' => ['default' => 5, 'type' => 'int'],
            'two_fa_expire_minutes' => ['default' => 10, 'type' => 'int'],
            'two_fa_resend_interval_seconds' => ['default' => 60, 'type' => 'int'],
            'two_fa_max_attempts' => ['default' => 5, 'type' => 'int'],
            'two_fa_attempt_window' => ['default' => 15, 'type' => 'int'],
            'two_fa_lockout_duration' => ['default' => 30, 'type' => 'int'],
            'two_fa_lockout_notification_enabled' => ['default' => true, 'type' => 'bool'],
            'two_fa_recovery_codes_count' => ['default' => 10, 'type' => 'int'],
            'two_fa_recovery_code_regenerate_interval' => ['default' => 90, 'type' => 'int'],
            'two_fa_verification_timeout' => ['default' => 600, 'type' => 'int'],

            // CAPTCHA
            'captcha_enabled' => ['default' => false, 'type' => 'bool'],
            'captcha_site_key' => ['default' => '', 'type' => 'string'],
            'captcha_secret_key' => ['default' => '', 'type' => 'string'],
            'captcha_driver' => ['type' => 'string'],
            'captcha_authentication_result' => ['type' => 'string'],
            'captcha_auto_failover_enabled' => ['default' => false, 'type' => 'bool'],
            'captcha_google_version' => ['type' => 'string'],
            'captcha_google_min_score' => ['default' => '0.5', 'type' => 'string'],
            'captcha_google_project_id' => ['default' => '', 'type' => 'string'],
            'captcha_google_site_key' => ['default' => '', 'type' => 'string'],
            'captcha_google_secret_key' => ['default' => '', 'type' => 'string'],
            'captcha_google_enabled' => ['default' => false, 'type' => 'bool'],
            'captcha_google_verified' => ['default' => false, 'type' => 'bool'],
            'captcha_google_enterprise_site_key' => ['default' => '', 'type' => 'string'],
            'captcha_google_enterprise_secret_key' => ['default' => '', 'type' => 'string'],
            'captcha_google_enterprise_project_id' => ['default' => '', 'type' => 'string'],
            'captcha_google_enterprise_min_score' => ['default' => '0.5', 'type' => 'string'],
            'captcha_google_enterprise_enabled' => ['default' => false, 'type' => 'bool'],
            'captcha_google_enterprise_verified' => ['default' => false, 'type' => 'bool'],
            'captcha_turnstile_site_key' => ['default' => '', 'type' => 'string'],
            'captcha_turnstile_secret_key' => ['default' => '', 'type' => 'string'],
            'captcha_turnstile_enabled' => ['default' => false, 'type' => 'bool'],
            'captcha_turnstile_verified' => ['default' => false, 'type' => 'bool'],

            // Front-end IP filter
            'enable_allowed_front_ips' => ['default' => false, 'type' => 'bool'],
            'allowed_front_ips' => ['default' => '', 'type' => 'string'],
            'enable_blocked_front_ips' => ['default' => false, 'type' => 'bool'],
            'blocked_front_ips' => ['default' => '', 'type' => 'string'],

            // Admin IP filter
            'enable_allowed_admin_ips' => ['default' => false, 'type' => 'bool'],
            'allowed_admin_ips' => ['default' => '', 'type' => 'string'],
            'enable_blocked_admin_ips' => ['default' => false, 'type' => 'bool'],
            'blocked_admin_ips' => ['default' => '', 'type' => 'string'],

            // CAPTCHA failover priority
            'captcha_failover_priority' => ['default' => '', 'type' => 'string'],

            // Session
            'session_driver' => ['type' => 'string'],
            'session_encrypt' => ['default' => true, 'type' => 'bool'],
            'session_lifetime' => ['default' => 120, 'type' => 'int'],

            // Session defaults (used by reset-to-default UI)
            'session_encrypt_default' => ['default' => true, 'type' => 'bool'],
            'session_lifetime_default' => ['default' => 120, 'type' => 'int'],

            // Extension (plugin/theme) install policy
            'extension_security_preset' => ['type' => 'string'],
            // No registry default: the effective value is derived from the
            // active security preset (see ExtensionEnableActionResolver),
            // mirroring extension_security_preset / *_max_health_level.
            'extension_require_signature' => ['type' => 'bool'],
            'extension_require_permission_definition' => ['default' => false, 'type' => 'bool'],
            'extension_allow_undefined_permissions' => ['default' => false, 'type' => 'bool'],
            'extension_plugin_max_health_level' => ['type' => 'string'],
            'extension_theme_max_health_level' => ['type' => 'string'],
            'extension_allow_logic_themes' => ['default' => false, 'type' => 'bool'],
            'extension_permission_mismatch_action' => ['type' => 'string'],
            'extension_notify_on_install' => ['default' => true, 'type' => 'bool'],
            'extension_notify_on_uninstall' => ['default' => true, 'type' => 'bool'],
            'extension_notify_on_enable' => ['default' => true, 'type' => 'bool'],
            'extension_notify_on_disable' => ['default' => true, 'type' => 'bool'],
            'extension_notify_on_unhealthy' => ['default' => true, 'type' => 'bool'],
            'extension_log_operations' => ['default' => true, 'type' => 'bool'],
            'extension_audit_max_age_days' => ['default' => 30, 'type' => 'int'],
            'extension_auto_scan_after_update' => ['default' => true, 'type' => 'bool'],
            'extension_source_type' => ['default' => 'github', 'type' => 'string'],
            'extension_update_check_interval' => ['default' => 86400, 'type' => 'int'],

            // Content Security Policy
            'csp_enabled' => ['default' => true, 'type' => 'bool'],
            'csp_mode' => ['type' => 'string'],
            'csp_admin_mode' => ['type' => 'string'],
            'csp_log_violations' => ['default' => true, 'type' => 'bool'],
            'csp_trusted_domains' => ['default' => '', 'type' => 'string'],
            'csp_denied_domains' => ['default' => '', 'type' => 'string'],
            'csp_custom_directives' => ['default' => '', 'type' => 'string'],
            'csp_custom_directives_mode' => ['type' => 'string'],
            'csp_blocklist_check_enabled' => ['default' => true, 'type' => 'bool'],
            'csp_blocklist_action' => ['type' => 'string'],
            'csp_blocklist_enabled_categories' => ['default' => '', 'type' => 'string'],
            'csp_exclude_dev_tools' => ['default' => true, 'type' => 'bool'],
        ];
    }
}
