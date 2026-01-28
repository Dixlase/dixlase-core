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
use App\Repositories\PluginSettingRepository;

/**
 * セキュリティポリシーヘルパー
 * 
 * 全体設定（セキュリティ設定）とプラグインのカスタム設定を統合管理
 * 設定取得の優先順位：プラグインカスタム設定 > 全体設定デフォルト
 */
class SecurityPolicyHelper
{
    /**
     * パスワードポリシーを取得
     * 
     * @param string $userType ユーザータイプ（'member', 'user'など）
     * @param string|null $pluginName プラグイン名（nullの場合はデフォルト設定）
     * @return array
     */
    public static function getPasswordPolicy(string $userType = 'member', ?string $pluginName = null): array
    {
        // デフォルト設定（全体設定 > セキュリティ設定）
        $defaults = [
            'min_length' => (int) SecuritySetting::get('password_min_length_default', 8),
            'require_uppercase' => (bool) SecuritySetting::get('password_require_uppercase_default', true),
            'require_number' => (bool) SecuritySetting::get('password_require_number_default', true),
            'require_symbol' => (bool) SecuritySetting::get('password_require_symbol_default', false),
            'pwned_check_enabled' => (bool) SecuritySetting::get('pwned_password_check_enabled', false),
            'reset_enabled' => (bool) SecuritySetting::get('password_reset_enabled_default', true),
        ];

        // プラグインのカスタム設定をチェック
        if ($pluginName && PluginHelper::isPluginEnabled($pluginName)) {
            $customEnabled = PluginHelper::getPluginSetting($pluginName, 'password_policy_custom_enabled', false);
            
            if ($customEnabled) {
                return [
                    'min_length' => (int) PluginHelper::getPluginSetting($pluginName, 'password_min_length', $defaults['min_length']),
                    'require_uppercase' => (bool) PluginHelper::getPluginSetting($pluginName, 'password_require_uppercase', $defaults['require_uppercase']),
                    'require_number' => (bool) PluginHelper::getPluginSetting($pluginName, 'password_require_number', $defaults['require_number']),
                    'require_symbol' => (bool) PluginHelper::getPluginSetting($pluginName, 'password_require_symbol', $defaults['require_symbol']),
                    'pwned_check_enabled' => $defaults['pwned_check_enabled'], // Pwned Passwordチェックは共通
                    'reset_enabled' => (bool) PluginHelper::getPluginSetting($pluginName, 'password_reset_enabled', $defaults['reset_enabled']),
                    'custom_enabled' => true,
                ];
            }
        }

        $defaults['custom_enabled'] = false;
        return $defaults;
    }

    /**
     * ログイン試行制限設定を取得
     * 
     * @param string $userType ユーザータイプ（'member', 'user'など）
     * @param string|null $pluginName プラグイン名（nullの場合はデフォルト設定）
     * @return array
     */
    public static function getLoginAttemptPolicy(string $userType = 'member', ?string $pluginName = null): array
    {
        // デフォルト設定（全体設定 > セキュリティ設定）
        $defaults = [
            'enabled' => (bool) SecuritySetting::get('login_attempt_limit_enabled_default', false),
            'max_attempts' => (int) SecuritySetting::get('login_attempt_max_attempts_default', 5),
            'max_attempts_ip' => (int) SecuritySetting::get('login_attempt_max_attempts_ip_default', 10),
            'time_window' => (int) SecuritySetting::get('login_attempt_time_window_default', 15),
            'lockout_duration' => (int) SecuritySetting::get('login_attempt_lockout_duration_default', 30),
            'notification_enabled' => (bool) SecuritySetting::get('login_attempt_lockout_notification_enabled_default', true),
        ];

        // プラグインのカスタム設定をチェック
        if ($pluginName && PluginHelper::isPluginEnabled($pluginName)) {
            $customEnabled = PluginHelper::getPluginSetting($pluginName, 'login_attempt_policy_custom_enabled', false);
            
            if ($customEnabled) {
                return [
                    'enabled' => (bool) PluginHelper::getPluginSetting($pluginName, 'login_attempt_limit_enabled', $defaults['enabled']),
                    'max_attempts' => (int) PluginHelper::getPluginSetting($pluginName, 'login_attempt_max_attempts', $defaults['max_attempts']),
                    'max_attempts_ip' => (int) PluginHelper::getPluginSetting($pluginName, 'login_attempt_max_attempts_ip', $defaults['max_attempts_ip']),
                    'time_window' => (int) PluginHelper::getPluginSetting($pluginName, 'login_attempt_time_window', $defaults['time_window']),
                    'lockout_duration' => (int) PluginHelper::getPluginSetting($pluginName, 'login_attempt_lockout_duration', $defaults['lockout_duration']),
                    'notification_enabled' => (bool) PluginHelper::getPluginSetting($pluginName, 'login_attempt_lockout_notification_enabled', $defaults['notification_enabled']),
                    'custom_enabled' => true,
                ];
            }
        }

        $defaults['custom_enabled'] = false;
        return $defaults;
    }

    /**
     * セッション設定を取得
     * 
     * @param string $userType ユーザータイプ（'member', 'user'など）
     * @param string|null $pluginName プラグイン名（nullの場合はデフォルト設定）
     * @return array
     */
    public static function getSessionPolicy(string $userType = 'member', ?string $pluginName = null): array
    {
        // デフォルト設定（全体設定 > セキュリティ設定）
        $defaults = [
            'encrypt' => (bool) SecuritySetting::get('session_encrypt_default', true),
            'lifetime' => (int) SecuritySetting::get('session_lifetime_default', 120),
        ];

        // プラグインのカスタム設定をチェック
        if ($pluginName && PluginHelper::isPluginEnabled($pluginName)) {
            $customEnabled = PluginHelper::getPluginSetting($pluginName, 'session_policy_custom_enabled', false);
            
            if ($customEnabled) {
                return [
                    'encrypt' => (bool) PluginHelper::getPluginSetting($pluginName, 'session_encrypt', $defaults['encrypt']),
                    'lifetime' => (int) PluginHelper::getPluginSetting($pluginName, 'session_lifetime', $defaults['lifetime']),
                    'custom_enabled' => true,
                ];
            }
        }

        $defaults['custom_enabled'] = false;
        return $defaults;
    }

    /**
     * パスワードポリシーの検証ルールを取得
     * 
     * @param string $userType ユーザータイプ
     * @param string|null $pluginName プラグイン名
     * @return array Laravelバリデーションルール配列
     */
    public static function getPasswordValidationRules(string $userType = 'member', ?string $pluginName = null): array
    {
        $policy = static::getPasswordPolicy($userType, $pluginName);
        
        $rules = ['required', 'string', 'min:' . $policy['min_length']];
        
        if ($policy['require_uppercase']) {
            $rules[] = 'regex:/[A-Z]/';
        }
        
        if ($policy['require_number']) {
            $rules[] = 'regex:/[0-9]/';
        }
        
        if ($policy['require_symbol']) {
            $rules[] = 'regex:/[@$!%*#?&]/';
        }
        
        return $rules;
    }

    /**
     * パスワードポリシーの説明テキストを取得
     * 
     * @param string $userType ユーザータイプ
     * @param string|null $pluginName プラグイン名
     * @return string
     */
    public static function getPasswordPolicyDescription(string $userType = 'member', ?string $pluginName = null): string
    {
        $policy = static::getPasswordPolicy($userType, $pluginName);
        
        $requirements = [];
        $requirements[] = __('validation.password_min_length', ['length' => $policy['min_length']]);
        
        if ($policy['require_uppercase']) {
            $requirements[] = __('validation.password_require_uppercase');
        }
        
        if ($policy['require_number']) {
            $requirements[] = __('validation.password_require_number');
        }
        
        if ($policy['require_symbol']) {
            $requirements[] = __('validation.password_require_symbol');
        }
        
        return implode('、', $requirements);
    }

    /**
     * ログイン試行制限が有効かチェック
     * 
     * @param string $userType ユーザータイプ
     * @param string|null $pluginName プラグイン名
     * @return bool
     */
    public static function isLoginAttemptLimitEnabled(string $userType = 'member', ?string $pluginName = null): bool
    {
        $policy = static::getLoginAttemptPolicy($userType, $pluginName);
        return $policy['enabled'];
    }

    /**
     * Pwned Passwordチェックが有効かチェック
     * 
     * @return bool
     */
    public static function isPwnedPasswordCheckEnabled(): bool
    {
        return (bool) SecuritySetting::get('pwned_password_check_enabled', false);
    }

    /**
     * 全セキュリティポリシーを取得（管理画面表示用）
     * 
     * @param string $userType ユーザータイプ
     * @param string|null $pluginName プラグイン名
     * @return array
     */
    public static function getAllPolicies(string $userType = 'member', ?string $pluginName = null): array
    {
        return [
            'password' => static::getPasswordPolicy($userType, $pluginName),
            'login_attempt' => static::getLoginAttemptPolicy($userType, $pluginName),
            'session' => static::getSessionPolicy($userType, $pluginName),
        ];
    }
}
