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

namespace App\Helpers;

use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ConfigHelper
{
    /**
     * Get configuration value with priority: database -> config(.env) -> default
     *
     * Database values (set via admin UI) take priority over config/.env defaults.
     * This ensures admin-set values are always respected in the CMS.
     *
     * @param  string  $configKey  Config key (e.g., 'session.driver', 'app.name', 'mail.host')
     * @param  string  $dbKey  Database key for model (e.g., 'session_driver', 'app_name', 'mail_host')
     * @param  mixed  $default  Default value if neither database nor config has the value
     * @param  string  $type  Return type: 'string', 'bool', 'int', 'float'
     * @param  string  $model  Model class to use: 'SecuritySetting', 'SiteSetting'
     * @return mixed
     */
    public static function get(string $configKey, string $dbKey, $default, string $type = 'string', string $model = 'SecuritySetting')
    {
        // Priority 1: database (admin UI setting)
        $dbValue = self::getFromDatabase($dbKey, $model);
        if ($dbValue !== null) {
            return self::castValue($dbValue, $type);
        }

        // Priority 2: config(.env)
        $configValue = config($configKey);
        if ($configValue !== null) {
            return self::castValue($configValue, $type);
        }

        // Priority 3: default
        return self::castValue($default, $type);
    }

    /**
     * Get value from database using specified model
     *
     * @return mixed|null
     */
    private static function getFromDatabase(string $key, string $model = 'SecuritySetting')
    {
        // Returns null before installation or on database connection error.
        // config('app.installed') is consulted first because env() returns
        // null after Laravel's config cache is built.
        if (! file_exists(base_path('.env')) || ! (config('app.installed', false) ?: env('INSTALLED', false))) {
            return;
        }

        try {
            switch ($model) {
                case 'SiteSetting':
                    if (Schema::hasTable('site_settings')) {
                        return SiteSetting::get($key, null);
                    }
                    break;
                case 'SecuritySetting':
                default:
                    // After multisite consolidation, security settings are also integrated into global_settings
                    if (Schema::hasTable('global_settings')) {
                        return SecuritySetting::get($key, null);
                    }
                    break;
            }
        } catch (\Throwable $e) {
            // If there's any database error (e.g., during installation), return null
        }
    }

    /**
     * Set value to database using specified model
     */
    private static function setToDatabase(string $key, string $value, string $model = 'SecuritySetting'): void
    {
        try {
            switch ($model) {
                case 'SiteSetting':
                    if (Schema::hasTable('site_settings')) {
                        SiteSetting::setValue($key, $value);
                    }
                    break;
                case 'SecuritySetting':
                default:
                    if (Schema::hasTable('global_settings')) {
                        SecuritySetting::set($key, $value);
                    }
                    break;
            }
        } catch (\Throwable $e) {
            Log::error("Database write failed for {$model}::{$key}: ".$e->getMessage());
        }
    }

    /**
     * Cast value to specified type
     *
     * @param  mixed  $value
     * @return mixed
     */
    private static function castValue($value, string $type)
    {
        switch ($type) {
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'string':
            default:
                return (string) $value;
        }
    }

    // ===== Session Configuration Methods =====

    /**
     * Get effective session lifetime for the current user context
     *
     * All guards share the same session_lifetime setting.
     *
     * @param  string|null  $guard  Guard name (unused, kept for API compatibility)
     * @return int Session lifetime in minutes
     */
    public static function getEffectiveSessionLifetime(?string $guard = null): int
    {
        return self::getSessionLifetime();
    }

    /**
     * Get session driver (fixed to guard-aware-database)
     *
     * @return string Session driver name
     */
    public static function getSessionDriver(): string
    {
        return 'guard-aware-database';
    }

    /**
     * Get session encryption setting
     *
     * @return bool Whether session should be encrypted
     */
    public static function getSessionEncrypt(): bool
    {
        return self::get('session.encrypt', 'session_encrypt', false, 'bool', 'SecuritySetting');
    }

    /**
     * Get session lifetime (base value without admin member override)
     *
     * @return int Session lifetime in minutes
     */
    public static function getSessionLifetime(): int
    {
        return self::get('session.lifetime', 'session_lifetime', 120, 'int', 'SecuritySetting');
    }

    /**
     * Set session encryption setting
     *
     * @param  bool  $encrypt  Whether session should be encrypted
     */
    public static function setSessionEncrypt(bool $encrypt): void
    {
        self::setToDatabase('session_encrypt', $encrypt ? '1' : '0', 'SecuritySetting');
    }

    /**
     * Set session lifetime
     *
     * @param  int  $lifetime  Session lifetime in minutes
     */
    public static function setSessionLifetime(int $lifetime): void
    {
        self::setToDatabase('session_lifetime', (string) $lifetime, 'SecuritySetting');
    }

    // ===== App Configuration Methods =====

    /**
     * Get application name
     */
    public static function getAppName(): string
    {
        return self::get('app.name', 'app_name', 'Dixlase', 'string', 'SiteSetting');
    }

    /**
     * Get application locale
     */
    public static function getAppLocale(): string
    {
        return self::get('app.locale', 'locale', 'en', 'string', 'SiteSetting');
    }

    /**
     * Get the display timezone
     *
     * Storage and calculations always use UTC (config('app.timezone')); this method is
     * Used when converting to local time in Blade or notification emails. Value is site_settings.display_timezone
     */
    public static function getDisplayTimezone(): string
    {
        return self::get('app.display_timezone', 'display_timezone', 'Asia/Tokyo', 'string', 'SiteSetting');
    }

    /**
     * Get maintenance mode status
     */
    public static function getMaintenanceMode(): bool
    {
        return self::get('app.maintenance_mode', 'maintenance_mode', false, 'bool', 'SiteSetting');
    }

    /**
     * Get maintenance message
     */
    public static function getMaintenanceMessage(): string
    {
        return self::get('app.maintenance_message', 'maintenance_message', 'The site is currently under maintenance. Please try again shortly.', 'string', 'SiteSetting');
    }

    /**
     * Get notification enabled status
     */
    public static function getNotificationEnabled(): bool
    {
        return self::get('app.notification_enabled', 'notification_enabled', false, 'bool', 'SiteSetting');
    }

    /**
     * Get notification email
     */
    public static function getNotificationEmail(): string
    {
        return SiteSetting::get('system_admin_email', '');
    }

    // ===== Mail Configuration Methods =====

    /**
     * Get mail mailer
     */
    public static function getMailMailer(): string
    {
        return self::get('mail.default', 'mail_mailer', 'smtp', 'string', 'SiteSetting');
    }

    /**
     * Get mail host
     */
    public static function getMailHost(): string
    {
        return self::get('mail.mailers.smtp.host', 'mail_host', 'smtp.example.com', 'string', 'SiteSetting');
    }

    /**
     * Get mail port
     */
    public static function getMailPort(): int
    {
        return self::get('mail.mailers.smtp.port', 'mail_port', 587, 'int', 'SiteSetting');
    }

    /**
     * Get mail username
     */
    public static function getMailUsername(): string
    {
        return self::get('mail.mailers.smtp.username', 'mail_username', '', 'string', 'SiteSetting');
    }

    /**
     * Get mail password
     */
    public static function getMailPassword(): string
    {
        return self::get('mail.mailers.smtp.password', 'mail_password', '', 'string', 'SiteSetting');
    }

    /**
     * Get mail encryption
     */
    public static function getMailEncryption(): string
    {
        return self::get('mail.mailers.smtp.encryption', 'mail_encryption', 'tls', 'string', 'SiteSetting');
    }

    /**
     * Get mail from address
     */
    public static function getMailFromAddress(): string
    {
        return self::get('mail.from.address', 'mail_from_address', 'no-reply@example.com', 'string', 'SiteSetting');
    }

    // ===== Session Configuration Application =====

    /**
     * Apply session configuration dynamically
     * This method can be called from middleware or service providers
     *
     * @param  string|null  $guard  Guard name
     */
    public static function applySessionConfig(?string $guard = null): void
    {
        try {
            // Update session configuration at runtime
            config([
                'session.lifetime' => self::getEffectiveSessionLifetime($guard),
                'session.driver' => self::getSessionDriver(),
                'session.encrypt' => self::getSessionEncrypt(),
            ]);
        } catch (\Exception $e) {
            // If there's any error (e.g., during installation or DB issues), use defaults

            // Set safe defaults
            config([
                'session.lifetime' => config('session.lifetime', 120),
                'session.driver' => 'file', // Use file driver as fallback
                'session.encrypt' => false,
            ]);
        }
    }

    /**
     * Get session configuration summary for debugging
     *
     * @param  string|null  $guard  Guard name
     * @return array Configuration summary
     */
    public static function getSessionConfigSummary(?string $guard = null): array
    {
        return [
            'guard' => $guard,
            'effective_lifetime' => self::getEffectiveSessionLifetime($guard),
            'effective_driver' => self::getSessionDriver(),
            'effective_encrypt' => self::getSessionEncrypt(),
            'session_lifetime' => self::getSessionLifetime(),
            'config_default_lifetime' => config('session.lifetime', 120),
        ];
    }
}
