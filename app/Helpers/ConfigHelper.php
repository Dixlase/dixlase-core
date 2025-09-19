<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Helpers;

use App\Models\SecuritySetting;
use App\Models\MemberSetting;
use App\Models\BaseSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ConfigHelper
{
    /**
     * Get configuration value with priority: config(.env) -> database -> default
     * 
     * @param string $configKey Config key (e.g., 'session.driver', 'app.name', 'mail.host')
     * @param string $dbKey Database key for model (e.g., 'session_driver', 'app_name', 'mail_host')
     * @param mixed $default Default value if neither config nor database has the value
     * @param string $type Return type: 'string', 'bool', 'int', 'float'
     * @param string $model Model class to use: 'SecuritySetting', 'BaseSetting', 'MemberSetting'
     * @return mixed
     */
    public static function get(string $configKey, string $dbKey, $default, string $type = 'string', string $model = 'SecuritySetting')
    {
        // Priority 1: config(.env)
        $configValue = config($configKey);
        if ($configValue !== null) {
            return self::castValue($configValue, $type);
        }

        // Priority 2: database
        $dbValue = self::getFromDatabase($dbKey, $model);
        if ($dbValue !== null) {
            return self::castValue($dbValue, $type);
        }

        // Priority 3: default
        return self::castValue($default, $type);
    }

    /**
     * Get value from database using specified model
     * 
     * @param string $key
     * @param string $model
     * @return mixed|null
     */
    private static function getFromDatabase(string $key, string $model)
    {
        try {
            switch ($model) {
                case 'BaseSetting':
                    if (Schema::hasTable('base_settings')) {
                        return BaseSetting::get($key, null);
                    }
                    break;
                case 'MemberSetting':
                    if (Schema::hasTable('members_settings')) {
                        return MemberSetting::getValue($key, null);
                    }
                    break;
                case 'SecuritySetting':
                default:
                    if (Schema::hasTable('security_settings')) {
                        return SecuritySetting::get($key, null);
                    }
                    break;
            }
        } catch (\Exception $e) {
            // If there's any database error (e.g., during installation), return null
            Log::debug("Database access failed for {$model}::{$key} during installation: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Cast value to specified type
     * 
     * @param mixed $value
     * @param string $type
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
     * @param string|null $guard Guard name (e.g., 'member' for admin)
     * @return int Session lifetime in minutes
     */
    public static function getEffectiveSessionLifetime(?string $guard = null): int
    {
        // Determine the guard if not provided
        if ($guard === null) {
            $guard = Auth::getDefaultDriver();
        }

        // For admin members, check if custom session lifetime is enabled
        if ($guard === 'member') {
            try {
                // Check if the members_settings table exists before querying
                if (Schema::hasTable('members_settings')) {
                    $membersSessionEnabled = (bool) MemberSetting::getValue('members_session_lifetime_enabled', false);
                    
                    if ($membersSessionEnabled) {
                        $membersSessionLifetime = (int) MemberSetting::getValue('members_session_lifetime', 120);
                        return $membersSessionLifetime;
                    }
                }
            } catch (\Exception $e) {
                // If there's any database error (e.g., during installation), fall back to security settings
                Log::debug('MemberSetting access failed during installation: ' . $e->getMessage());
            }
        }

        // For user management plugins or other contexts, add similar logic here
        // Example:
        // if ($guard === 'user') {
        //     $userSessionEnabled = (bool) UserSetting::getValue('user_session_lifetime_enabled', false);
        //     if ($userSessionEnabled) {
        //         return (int) UserSetting::getValue('user_session_lifetime', 120);
        //     }
        // }

        // Fall back to base session lifetime
        return self::getSessionLifetime();
    }

    /**
     * Get session driver
     * 
     * @return string Session driver name
     */
    public static function getSessionDriver(): string
    {
        return self::get('session.driver', 'session_driver', 'file', 'string', 'SecuritySetting');
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

    // ===== App Configuration Methods =====

    /**
     * Get application name
     * 
     * @return string
     */
    public static function getAppName(): string
    {
        return self::get('app.name', 'app_name', 'MySoftware', 'string', 'BaseSetting');
    }

    /**
     * Get application locale
     * 
     * @return string
     */
    public static function getAppLocale(): string
    {
        return self::get('app.locale', 'locale', 'ja', 'string', 'BaseSetting');
    }

    /**
     * Get application timezone
     * 
     * @return string
     */
    public static function getAppTimezone(): string
    {
        return self::get('app.timezone', 'timezone', 'Asia/Tokyo', 'string', 'BaseSetting');
    }

    /**
     * Get maintenance mode status
     * 
     * @return bool
     */
    public static function getMaintenanceMode(): bool
    {
        return self::get('app.maintenance_mode', 'maintenance_mode', false, 'bool', 'BaseSetting');
    }

    /**
     * Get maintenance message
     * 
     * @return string
     */
    public static function getMaintenanceMessage(): string
    {
        return self::get('app.maintenance_message', 'maintenance_message', '現在メンテナンス中です。しばらくお待ちください。', 'string', 'BaseSetting');
    }

    /**
     * Get notification enabled status
     * 
     * @return bool
     */
    public static function getNotificationEnabled(): bool
    {
        return self::get('app.notification_enabled', 'notification_enabled', false, 'bool', 'BaseSetting');
    }

    /**
     * Get notification email
     * 
     * @return string
     */
    public static function getNotificationEmail(): string
    {
        return self::get('app.notification_email', 'notification_email', '', 'string', 'BaseSetting');
    }

    // ===== Mail Configuration Methods =====

    /**
     * Get mail mailer
     * 
     * @return string
     */
    public static function getMailMailer(): string
    {
        return self::get('mail.default', 'mail_mailer', 'smtp', 'string', 'BaseSetting');
    }

    /**
     * Get mail host
     * 
     * @return string
     */
    public static function getMailHost(): string
    {
        return self::get('mail.mailers.smtp.host', 'mail_host', 'smtp.example.com', 'string', 'BaseSetting');
    }

    /**
     * Get mail port
     * 
     * @return int
     */
    public static function getMailPort(): int
    {
        return self::get('mail.mailers.smtp.port', 'mail_port', 587, 'int', 'BaseSetting');
    }

    /**
     * Get mail username
     * 
     * @return string
     */
    public static function getMailUsername(): string
    {
        return self::get('mail.mailers.smtp.username', 'mail_username', '', 'string', 'BaseSetting');
    }

    /**
     * Get mail password
     * 
     * @return string
     */
    public static function getMailPassword(): string
    {
        return self::get('mail.mailers.smtp.password', 'mail_password', '', 'string', 'BaseSetting');
    }

    /**
     * Get mail encryption
     * 
     * @return string
     */
    public static function getMailEncryption(): string
    {
        return self::get('mail.mailers.smtp.encryption', 'mail_encryption', 'tls', 'string', 'BaseSetting');
    }

    /**
     * Get mail from address
     * 
     * @return string
     */
    public static function getMailFromAddress(): string
    {
        return self::get('mail.from.address', 'system_email', 'no-reply@example.com', 'string', 'BaseSetting');
    }

    // ===== Session Configuration Application =====

    /**
     * Apply session configuration dynamically
     * This method can be called from middleware or service providers
     * 
     * @param string|null $guard Guard name
     * @return void
     */
    public static function applySessionConfig(?string $guard = null): void
    {
        // Update session configuration at runtime
        config([
            'session.lifetime' => self::getEffectiveSessionLifetime($guard),
            'session.driver' => self::getSessionDriver(),
            'session.encrypt' => self::getSessionEncrypt(),
        ]);
    }

    /**
     * Get session configuration summary for debugging
     * 
     * @param string|null $guard Guard name
     * @return array Configuration summary
     */
    public static function getSessionConfigSummary(?string $guard = null): array
    {
        $membersSessionEnabled = false;
        $membersSessionLifetime = null;
        
        if ($guard === 'member') {
            try {
                // Check if the members_settings table exists before querying
                if (Schema::hasTable('members_settings')) {
                    $membersSessionEnabled = (bool) MemberSetting::getValue('members_session_lifetime_enabled', false);
                    $membersSessionLifetime = (int) MemberSetting::getValue('members_session_lifetime', 120);
                }
            } catch (\Exception $e) {
                // If there's any database error (e.g., during installation), use default values
                Log::debug('MemberSetting access failed during installation: ' . $e->getMessage());
            }
        }

        return [
            'guard' => $guard,
            'effective_lifetime' => self::getEffectiveSessionLifetime($guard),
            'effective_driver' => self::getSessionDriver(),
            'effective_encrypt' => self::getSessionEncrypt(),
            'members_session_enabled' => $membersSessionEnabled,
            'members_session_lifetime' => $membersSessionLifetime,
            'security_default_lifetime' => self::getSessionLifetime(),
            'config_default_lifetime' => config('session.lifetime', 120),
        ];
    }
}
