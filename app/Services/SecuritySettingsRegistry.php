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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

declare(strict_types=1);

namespace App\Services;

use App\Models\BaseSetting;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * セキュリティ設定統一レジストリ
 *
 * 散らばったセキュリティ設定を一元管理するサービス
 * カテゴリ別に設定を整理し、一貫したAPIを提供
 */
class SecuritySettingsRegistry
{
    /**
     * キャッシュキープレフィックス
     */
    protected const CACHE_PREFIX = 'security_settings:';

    /**
     * キャッシュTTL（秒）
     */
    protected const CACHE_TTL = 300;

    /**
     * 設定カテゴリ
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
     * 設定定義
     * [key => [category, source, type, default, description]]
     */
    protected static array $definitions = [];

    /**
     * 初期化フラグ
     */
    protected static bool $initialized = false;

    /**
     * 設定定義を初期化
     */
    protected static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$definitions = [
            // =========================================================================
            // 認証設定 (auth)
            // =========================================================================
            'two_fa_enabled' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => false,
                'description' => '二段階認証の有効/無効',
            ],
            'two_fa_mode' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'string',
                'default' => 'optional',
                'description' => '二段階認証モード（optional/required/disabled）',
            ],
            'password_min_length' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'int',
                'default' => 8,
                'description' => 'パスワード最小文字数',
            ],
            'password_require_mixed_case' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'パスワードに大文字小文字を必須にするか',
            ],
            'password_require_numbers' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'パスワードに数字を必須にするか',
            ],
            'password_require_symbols' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => false,
                'description' => 'パスワードに記号を必須にするか',
            ],
            'password_check_pwned' => [
                'category' => self::CATEGORY_AUTH,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => '漏洩パスワードチェックを有効にするか',
            ],

            // =========================================================================
            // ログイン設定 (login)
            // =========================================================================
            'login_max_attempts' => [
                'category' => self::CATEGORY_LOGIN,
                'source' => 'members_settings',
                'type' => 'int',
                'default' => 5,
                'description' => 'ログイン試行回数上限',
            ],
            'login_lockout_duration' => [
                'category' => self::CATEGORY_LOGIN,
                'source' => 'members_settings',
                'type' => 'int',
                'default' => 15,
                'description' => 'ロックアウト時間（分）',
            ],
            'login_notification_enabled' => [
                'category' => self::CATEGORY_LOGIN,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'ログイン通知を有効にするか',
            ],
            'lockout_notification_enabled' => [
                'category' => self::CATEGORY_LOGIN,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'ロックアウト通知を有効にするか',
            ],

            // =========================================================================
            // セッション設定 (session)
            // =========================================================================
            'session_driver' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'file',
                'description' => 'セッションドライバー',
            ],
            'session_lifetime' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 120,
                'description' => 'セッション有効期間（分）',
            ],
            'session_encrypt' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => 'セッション暗号化',
            ],
            'members_session_lifetime_enabled' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'members_settings',
                'type' => 'bool',
                'default' => false,
                'description' => 'メンバー用セッション有効期間を有効にするか',
            ],
            'members_session_lifetime' => [
                'category' => self::CATEGORY_SESSION,
                'source' => 'members_settings',
                'type' => 'int',
                'default' => 120,
                'description' => 'メンバー用セッション有効期間（分）',
            ],

            // =========================================================================
            // CAPTCHA設定 (captcha)
            // =========================================================================
            'captcha_enabled' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => 'CAPTCHA有効/無効',
            ],
            'captcha_driver' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'google',
                'description' => 'CAPTCHAドライバー',
            ],
            'captcha_site_key' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => 'CAPTCHAサイトキー',
            ],
            'captcha_secret_key' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => 'CAPTCHAシークレットキー',
                'sensitive' => true,
            ],
            'captcha_google_version' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'v3',
                'description' => 'Google reCAPTCHAバージョン',
            ],
            'captcha_google_min_score' => [
                'category' => self::CATEGORY_CAPTCHA,
                'source' => 'security_settings',
                'type' => 'float',
                'default' => 0.5,
                'description' => 'Google reCAPTCHA最小スコア',
            ],

            // =========================================================================
            // IP制限設定 (ip)
            // =========================================================================
            'enable_allowed_admin_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => '管理画面IP許可リストを有効にするか',
            ],
            'allowed_admin_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => '管理画面許可IPリスト',
            ],
            'enable_blocked_admin_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => '管理画面IPブロックリストを有効にするか',
            ],
            'blocked_admin_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => '管理画面ブロックIPリスト',
            ],
            'enable_allowed_front_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => 'フロントIP許可リストを有効にするか',
            ],
            'allowed_front_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => 'フロント許可IPリスト',
            ],
            'enable_blocked_front_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => 'フロントIPブロックリストを有効にするか',
            ],
            'blocked_front_ips' => [
                'category' => self::CATEGORY_IP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => 'フロントブロックIPリスト',
            ],

            // =========================================================================
            // CSP設定 (csp)
            // =========================================================================
            'csp_enabled' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'CSP有効/無効',
            ],
            'csp_mode' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 1,
                'description' => 'CSPモード（0: development, 1: standard, 2: strict）',
            ],
            'csp_log_violations' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'CSP違反をログに記録するか',
            ],
            'csp_trusted_domains' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => '信頼済みドメイン（改行区切り）',
            ],
            'csp_denied_domains' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '',
                'description' => '拒否ドメイン（改行区切り）',
            ],
            'csp_blocklist_check_enabled' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => 'CSPブロックリスト検出を有効にするか',
            ],
            'csp_blocklist_action' => [
                'category' => self::CATEGORY_CSP,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 0,
                'description' => 'CSPブロックリスト検出時のアクション（0: warn, 1: block）',
            ],

            // =========================================================================
            // 拡張機能セキュリティ設定 (extension)
            // =========================================================================
            'extension_security_preset' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'balanced',
                'description' => '拡張機能セキュリティプリセット',
            ],
            'extension_require_signature' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => '署名を必須にするか',
            ],
            'extension_require_permission_definition' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => false,
                'description' => '権限定義を必須にするか',
            ],
            'extension_allow_undefined_permissions' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => '未定義の権限を許可するか',
            ],
            'extension_plugin_max_health_level' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 2,
                'description' => 'プラグインの最大許可健全性レベル',
            ],
            'extension_theme_max_health_level' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 3,
                'description' => 'テーマの最大許可健全性レベル',
            ],
            'extension_audit_max_age_days' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 30,
                'description' => '監査スキャン期限日数（これを超えると「期限切れ」バッジが表示される）',
            ],
            'extension_allow_logic_themes' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'ロジックを含むテーマを許可するか',
            ],
            'extension_permission_mismatch_action' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => 'warn',
                'description' => '権限不一致時の動作',
            ],
            'extension_notify_on_install' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'インストール時に通知するか',
            ],
            'extension_notify_on_uninstall' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'アンインストール時に通知するか',
            ],
            'extension_log_operations' => [
                'category' => self::CATEGORY_EXTENSION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => '拡張機能操作をログに記録するか',
            ],

            // =========================================================================
            // 通知設定 (notification)
            // =========================================================================
            'notification_enabled' => [
                'category' => self::CATEGORY_NOTIFICATION,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'システム通知を有効にするか',
            ],
            'notification_log_levels' => [
                'category' => self::CATEGORY_NOTIFICATION,
                'source' => 'security_settings',
                'type' => 'string',
                'default' => '8,7,6,5',
                'description' => '通知するログレベル（カンマ区切り）',
            ],

            // =========================================================================
            // API設定 (api)
            // =========================================================================
            'api_rate_limit_enabled' => [
                'category' => self::CATEGORY_API,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'APIレートリミットを有効にするか',
            ],
            'api_rate_limit_per_minute' => [
                'category' => self::CATEGORY_API,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 60,
                'description' => '1分あたりのAPIリクエスト上限',
            ],
            'api_signature_required' => [
                'category' => self::CATEGORY_API,
                'source' => 'security_settings',
                'type' => 'bool',
                'default' => true,
                'description' => 'API署名を必須にするか',
            ],
            'api_timestamp_tolerance' => [
                'category' => self::CATEGORY_API,
                'source' => 'security_settings',
                'type' => 'int',
                'default' => 300,
                'description' => 'APIタイムスタンプ許容範囲（秒）',
            ],
        ];

        self::$initialized = true;
    }

    /**
     * 設定値を取得
     *
     * @param  string  $key  設定キー
     * @param  mixed  $default  デフォルト値（nullの場合は定義のデフォルトを使用）
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        self::initialize();

        // キャッシュから取得
        $cacheKey = self::CACHE_PREFIX.$key;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // 定義を取得
        $definition = self::$definitions[$key] ?? null;
        if (! $definition) {
            return $default;
        }

        // デフォルト値を決定
        $defaultValue = $default ?? $definition['default'];

        // データベースから取得
        $value = self::getFromSource($key, $definition['source'], $defaultValue);

        // 型変換
        $value = self::castValue($value, $definition['type']);

        // キャッシュに保存
        Cache::put($cacheKey, $value, self::CACHE_TTL);

        return $value;
    }

    /**
     * 設定値を設定
     *
     * @param  string  $key  設定キー
     * @param  mixed  $value  値
     */
    public static function set(string $key, $value): bool
    {
        self::initialize();

        $definition = self::$definitions[$key] ?? null;
        if (! $definition) {
            return false;
        }

        // 型変換
        $value = self::castValue($value, $definition['type']);

        // データベースに保存
        $result = self::setToSource($key, $value, $definition['source']);

        // キャッシュをクリア
        self::clearCache($key);

        return $result;
    }

    /**
     * 複数の設定値を一括設定
     *
     * @param  array  $settings  [key => value]
     * @return int 設定された件数
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
     * カテゴリ別に設定を取得
     *
     * @param  string  $category  カテゴリ
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
     * 全設定を取得
     *
     * @param  bool  $includeSensitive  機密情報を含めるか
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
     * カテゴリ別にグループ化した全設定を取得
     *
     * @param  bool  $includeSensitive  機密情報を含めるか
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
     * 設定定義を取得
     *
     * @param  string|null  $key  特定のキー（nullで全て）
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
     * 設定が存在するか
     */
    public static function has(string $key): bool
    {
        self::initialize();

        return isset(self::$definitions[$key]);
    }

    /**
     * キャッシュをクリア
     *
     * @param  string|null  $key  特定のキー（nullで全て）
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
     * 利用可能なカテゴリ一覧を取得
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
     * データソースから値を取得
     */
    protected static function getFromSource(string $key, string $source, $default)
    {
        try {
            switch ($source) {
                case 'security_settings':
                    if (Schema::hasTable('security_settings')) {
                        return SecuritySetting::get($key, $default);
                    }
                    break;
                case 'base_settings':
                    if (Schema::hasTable('base_settings')) {
                        return BaseSetting::get($key, $default);
                    }
                    break;
            }
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * データソースに値を設定
     */
    protected static function setToSource(string $key, $value, string $source): bool
    {
        try {
            // 値を文字列に変換
            $stringValue = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

            switch ($source) {
                case 'security_settings':
                    if (Schema::hasTable('security_settings')) {
                        SecuritySetting::set($key, $stringValue);

                        return true;
                    }
                    break;
                case 'base_settings':
                    if (Schema::hasTable('base_settings')) {
                        BaseSetting::setValue($key, $stringValue);

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
     * 値を型変換
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
     * 設定のエクスポート（バックアップ用）
     *
     * @param  bool  $includeSensitive  機密情報を含めるか
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
     * 設定のインポート（リストア用）
     *
     * @param  array  $data  エクスポートされたデータ
     * @return int インポートされた件数
     */
    public static function import(array $data): int
    {
        if (! isset($data['settings']) || ! is_array($data['settings'])) {
            return 0;
        }

        return self::setMultiple($data['settings']);
    }
}
