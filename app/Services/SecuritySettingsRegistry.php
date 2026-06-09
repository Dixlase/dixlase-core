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

declare(strict_types=1);

namespace App\Services;

use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Unified security settings registry
 *
 * Service for centralized management of scattered security settings
 * Organizes settings by category and provides a consistent API
 */
class SecuritySettingsRegistry
{
    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'security_settings:';

    /**
     * Cache TTL (seconds)
     */
    protected const CACHE_TTL = 300;

    /**
     * Settings categories
     */
    public const CATEGORY_AUTH = 'auth';

    public const CATEGORY_LOGIN = 'login';

    public const CATEGORY_SESSION = 'session';

    public const CATEGORY_CAPTCHA = 'captcha';

    public const CATEGORY_IP = 'ip';

    public const CATEGORY_CSP = 'csp';

    public const CATEGORY_EXTENSION = 'extension';

    public const CATEGORY_NOTIFICATION = 'notification';

    public const CATEGORY_API = 'api';

    public const CATEGORY_LOCKDOWN = 'lockdown';

    /**
     * Settings definitions
     * [key => [category, source, type, default, description]]
     */
    protected static array $definitions = [];

    /**
     * Initialization flag
     */
    protected static bool $initialized = false;

    /**
     * Initialize settings definitions
     */
    protected static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$definitions = [
            // =========================================================================
            // Authentication settings (auth)
            // =========================================================================
            'two_fa_enabled' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.two_factor_auth_toggle'),
            ],
            'two_fa_mode' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'string',
                'default' => 'optional',
                'description' => __('services/security_settings_registry.two_factor_auth_mode'),
            ],
            'password_min_length' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'int',
                'default' => 8,
                'description' => __('services/security_settings_registry.password_min_length'),
            ],
            'password_require_mixed_case' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.password_require_mixed_case'),
            ],
            'password_require_numbers' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.password_require_numbers'),
            ],
            'password_require_symbols' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.password_require_symbols'),
            ],
            'password_check_pwned' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.password_breach_check_enabled'),
            ],

            // =========================================================================
            // Login settings (login)
            // =========================================================================
            'login_max_attempts' => [
                'category' => self::CATEGORY_LOGIN,
                'source' => 'members_settings',
                'type' => 'int',
                'default' => 5,
                'description' => __('services/security_settings_registry.login_max_attempts'),
            ],
            'login_lockout_duration' => [
                'category' => self::CATEGORY_LOGIN,
                'source' => 'members_settings',
                'type' => 'int',
                'default' => 15,
                'description' => __('services/security_settings_registry.lockout_duration_minutes'),
            ],
            'login_notification_enabled' => [
                'category' => self::CATEGORY_LOGIN,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.login_notification_enabled'),
            ],
            'lockout_notification_enabled' => [
                'category' => self::CATEGORY_LOGIN,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.lockout_notification_enabled'),
            ],

            // =========================================================================
            // Session settings (session)
            // =========================================================================
            'session_driver' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'file',
                'description' => __('services/security_settings_registry.session_driver'),
            ],
            'session_lifetime' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 120,
                'description' => __('services/security_settings_registry.session_lifetime_minutes'),
            ],
            'session_encrypt' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.session_encryption'),
            ],
            'members_session_lifetime_enabled' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.member_session_lifetime_enabled'),
            ],
            'members_session_lifetime' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'members_settings',
                'type' => 'int',
                'default' => 120,
                'description' => __('services/security_settings_registry.member_session_lifetime_minutes'),
            ],

            // =========================================================================
            // CAPTCHA settings (captcha)
            // =========================================================================
            'captcha_enabled' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.captcha_toggle'),
            ],
            'captcha_driver' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'google',
                'description' => __('services/security_settings_registry.captcha_driver'),
            ],
            'captcha_site_key' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => __('services/security_settings_registry.captcha_site_key'),
            ],
            'captcha_secret_key' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => __('services/security_settings_registry.captcha_secret_key'),
                'sensitive' => true,
            ],
            'captcha_google_version' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'v3',
                'description' => __('services/security_settings_registry.recaptcha_version'),
            ],
            'captcha_google_min_score' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'float',
                'default' => 0.5,
                'description' => __('services/security_settings_registry.recaptcha_min_score'),
            ],

            // =========================================================================
            // IP restriction settings (ip)
            // =========================================================================
            'enable_allowed_admin_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.admin_ip_allowlist_enabled'),
            ],
            'allowed_admin_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => __('services/security_settings_registry.admin_ip_allowlist'),
            ],
            'enable_blocked_admin_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.admin_ip_blocklist_enabled'),
            ],
            'blocked_admin_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => __('services/security_settings_registry.admin_ip_blocklist'),
            ],
            'enable_allowed_front_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.front_ip_allowlist_enabled'),
            ],
            'allowed_front_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => __('services/security_settings_registry.front_ip_allowlist'),
            ],
            'enable_blocked_front_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.front_ip_blocklist_enabled'),
            ],
            'blocked_front_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => __('services/security_settings_registry.front_ip_blocklist'),
            ],

            // =========================================================================
            // CSP settings (csp)
            // =========================================================================
            'csp_enabled' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.csp_toggle'),
            ],
            'csp_mode' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 1,
                'description' => __('services/security_settings_registry.csp_mode'),
            ],
            'csp_log_violations' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.csp_log_violations'),
            ],
            'csp_trusted_domains' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => __('services/security_settings_registry.trusted_domains'),
            ],
            'csp_denied_domains' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => __('services/security_settings_registry.denied_domains'),
            ],
            'csp_blocklist_check_enabled' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.csp_blocklist_detection_enabled'),
            ],
            'csp_blocklist_action' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 0,
                'description' => __('services/security_settings_registry.csp_blocklist_action'),
            ],

            // =========================================================================
            // Extension security settings (extension)
            // =========================================================================
            'extension_security_preset' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'balanced',
                'description' => __('services/security_settings_registry.extension_security_preset'),
            ],
            'extension_require_signature' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.signature_required'),
            ],
            'extension_require_permission_definition' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => __('services/security_settings_registry.permission_definition_required'),
            ],
            'extension_allow_undefined_permissions' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.allow_undefined_permissions'),
            ],
            'extension_plugin_max_health_level' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 2,
                'description' => __('services/security_settings_registry.plugin_max_health_level'),
            ],
            'extension_theme_max_health_level' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 3,
                'description' => __('services/security_settings_registry.theme_max_health_level'),
            ],
            'extension_audit_max_age_days' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 30,
                'description' => __('services/security_settings_registry.audit_scan_expiration_days'),
            ],
            'extension_allow_logic_themes' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.allow_themes_with_logic'),
            ],
            'extension_permission_mismatch_action' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'warn',
                'description' => __('services/security_settings_registry.permission_mismatch_behavior'),
            ],
            'extension_notify_on_install' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.notify_on_install'),
            ],
            'extension_notify_on_uninstall' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.notify_on_uninstall'),
            ],
            'extension_log_operations' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.log_extension_operations'),
            ],

            // =========================================================================
            // Notification settings (notification)
            // =========================================================================
            'notification_enabled' => [
                'category' => self::CATEGORY_NOTIFICATION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.system_notifications_enabled'),
            ],
            'notification_log_levels' => [
                'category' => self::CATEGORY_NOTIFICATION,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '8,7,6,5',
                'description' => __('services/security_settings_registry.notification_log_levels'),
            ],

            // =========================================================================
            // API settings (api)
            // =========================================================================
            'api_rate_limit_enabled' => [
                'category' => self::CATEGORY_API,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.api_rate_limit_enabled'),
            ],
            'api_rate_limit_per_minute' => [
                'category' => self::CATEGORY_API,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 60,
                'description' => __('services/security_settings_registry.api_requests_per_minute'),
            ],
            'api_signature_required' => [
                'category' => self::CATEGORY_API,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => __('services/security_settings_registry.api_signature_required'),
            ],
            'api_timestamp_tolerance' => [
                'category' => self::CATEGORY_API,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 300,
                'description' => __('services/security_settings_registry.api_timestamp_tolerance_seconds'),
            ],
        ];

        self::$initialized = true;
    }

    /**
     * Get settings value
     *
     * @param  string  $key  Settings key
     * @param  mixed  $default  Default value (if null, use the default from the definition)
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::initialize();

        // Get from cache
        $cacheKey = self::CACHE_PREFIX.$key;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Get definition
        $definition = self::$definitions[$key] ?? null;
        if (! $definition) {
            return $default;
        }

        // Determine default value
        $defaultValue = $default ?? $definition['default'];

        // Get from database
        $value = self::getFromSource($key, $definition['source'], $defaultValue);

        // Type conversion
        $value = self::castValue($value, $definition['type']);

        // Save to cache
        Cache::put($cacheKey, $value, self::CACHE_TTL);

        return $value;
    }

    /**
     * Set settings value
     *
     * @param  string  $key  Settings key
     * @param  mixed  $value  Value
     */
    public static function set(string $key, $value): bool
    {
        self::initialize();

        $definition = self::$definitions[$key] ?? null;
        if (! $definition) {
            return false;
        }

        // Type conversion
        $value = self::castValue($value, $definition['type']);

        // Save to database
        $result = self::setToSource($key, $value, $definition['source']);

        // Clear cache
        self::clearCache($key);

        return $result;
    }

    /**
     * Bulk set multiple settings values
     *
     * @param  array  $settings  [key => value]
     * @return int Number of settings set
     */
    public static function setMultiple(array $settings): int
    {
        $count = 0;
        foreach ($settings as $key => $value) {
            if (self::set($key, $value)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get settings by category
     *
     * @param  string  $category  Category
     */
    public static function getByCategory(string $category): array
    {
        self::initialize();

        $settings = [];
        foreach (self::$definitions as $key => $definition) {
            if ($definition['category'] === $category) {
                $settings[$key] = self::get($key);
            }
        }

        return $settings;
    }

    /**
     * Get all settings
     *
     * @param  bool  $includeSensitive  Whether to include sensitive information
     */
    public static function getAll(bool $includeSensitive = false): array
    {
        self::initialize();

        $settings = [];
        foreach (self::$definitions as $key => $definition) {
            if (! $includeSensitive && ($definition['sensitive'] ?? false)) {
                continue;
            }
            $settings[$key] = self::get($key);
        }

        return $settings;
    }

    /**
     * Get all settings grouped by category
     *
     * @param  bool  $includeSensitive  Whether to include sensitive information
     */
    public static function getAllGrouped(bool $includeSensitive = false): array
    {
        self::initialize();

        $grouped = [];
        foreach (self::$definitions as $key => $definition) {
            if (! $includeSensitive && ($definition['sensitive'] ?? false)) {
                continue;
            }
            $category = $definition['category'];
            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$key] = [
                'value' => self::get($key),
                'type' => $definition['type'],
                'default' => $definition['default'],
                'description' => $definition['description'],
            ];
        }

        return $grouped;
    }

    /**
     * Get settings definition
     *
     * @param  string|null  $key  Specific key (null for all)
     */
    public static function getDefinition(?string $key = null): ?array
    {
        self::initialize();

        if ($key === null) {
            return self::$definitions;
        }

        return self::$definitions[$key] ?? null;
    }

    /**
     * Check if settings exist
     */
    public static function has(string $key): bool
    {
        self::initialize();

        return isset(self::$definitions[$key]);
    }

    /**
     * Clear cache
     *
     * @param  string|null  $key  Specific key (null for all)
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget(self::CACHE_PREFIX.$key);

            return;
        }

        self::initialize();
        foreach (array_keys(self::$definitions) as $k) {
            Cache::forget(self::CACHE_PREFIX.$k);
        }
    }

    /**
     * Get available category list
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_AUTH => __('admin/settings/security/common.categories.auth'),
            self::CATEGORY_LOGIN => __('admin/settings/security/common.categories.login'),
            self::CATEGORY_SESSION => __('admin/settings/security/common.categories.session'),
            self::CATEGORY_CAPTCHA => __('admin/settings/security/common.categories.captcha'),
            self::CATEGORY_IP => __('admin/settings/security/common.categories.ip'),
            self::CATEGORY_CSP => __('admin/settings/security/common.categories.csp'),
            self::CATEGORY_EXTENSION => __('admin/settings/security/common.categories.extension'),
            self::CATEGORY_NOTIFICATION => __('admin/settings/security/common.categories.notification'),
            self::CATEGORY_API => __('admin/settings/security/common.categories.api'),
            self::CATEGORY_LOCKDOWN => __('admin/settings/security/common.categories.lockdown'),
        ];
    }

    /**
     * Get value from data source
     */
    protected static function getFromSource(string $key, string $source, $default)
    {
        try {
            switch ($source) {
                case 'security_settings':
                    if (Schema::hasTable('global_settings')) {
                        return SecuritySetting::get($key, $default);
                    }
                    break;
                case 'site_settings':
                    if (Schema::hasTable('site_settings')) {
                        return SiteSetting::get($key, $default);
                    }
                    break;
            }
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Set value to data source
     */
    protected static function setToSource(string $key, $value, string $source): bool
    {
        try {
            // Convert value to string
            $stringValue = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

            switch ($source) {
                case 'security_settings':
                    if (Schema::hasTable('global_settings')) {
                        SecuritySetting::set($key, $stringValue);

                        return true;
                    }
                    break;
                case 'site_settings':
                    if (Schema::hasTable('site_settings')) {
                        SiteSetting::setValue($key, $stringValue);

                        return true;
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::error("SecuritySettingsRegistry: Failed to set {$key} to {$source}: ".$e->getMessage());
        }

        return false;
    }

    /**
     * Cast value to type
     */
    protected static function castValue($value, string $type)
    {
        switch ($type) {
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
                if (is_array($value)) {
                    return $value;
                }

                return $value ? explode(',', $value) : [];
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Export settings (for backup)
     *
     * @param  bool  $includeSensitive  Whether to include sensitive information
     */
    public static function export(bool $includeSensitive = false): array
    {
        return [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'settings' => self::getAll($includeSensitive),
        ];
    }

    /**
     * Import settings (for restore)
     *
     * @param  array  $data  Exported data
     * @return int Number of imported items
     */
    public static function import(array $data): int
    {
        if (! isset($data['settings']) || ! is_array($data['settings'])) {
            return 0;
        }

        return self::setMultiple($data['settings']);
    }
}
