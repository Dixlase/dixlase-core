<?php
/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

return [

    // 管理画面へのアクセスを許可するIPアドレス
    'allowed_admin_ips' => [
        //'127.0.0.1', // 例: ローカルIP
        //'192.168.1.10',
        '10.5.1.148',
        '0.0.0.0'
    ],
    // 管理画面へのアクセスを拒否するIPアドレス
    'blocked_admin_ips' => [
        //'123.456.789.0', // 例: 拒否するIP
    ],

    // フロントエンドへのアクセスを許可するIPアドレス
    'allowed_frontend_ips' => [
        // 例: 許可するIP
    ],
    // フロントエンドへのアクセスを拒否するIPアドレス
    'blocked_frontend_ips' => [
        // 例: 拒否するIP
    ],

    // SSLを強制するかどうか
    'force_ssl' => env('FORCE_SSL', false),

    /*
    |--------------------------------------------------------------------------
    | パスワード漏洩チェック設定
    |--------------------------------------------------------------------------
    |
    | Have I Been Pwned APIを使用したパスワード漏洩チェックの設定
    | パスワード自体は送信されず、SHA-1ハッシュの先頭5文字のみが送信されます
    |
    */
    'pwned_passwords' => [
        // Have I Been Pwned API エンドポイント
        'api_endpoint' => env('PWNED_PASSWORDS_API_ENDPOINT', 'https://api.pwnedpasswords.com'),
        
        // APIリクエストのタイムアウト（秒）
        'timeout' => env('PWNED_PASSWORDS_TIMEOUT', 5),
        
        // 障害時の挙動: 'fail_open'（許可）または 'fail_closed'（拒否）
        'on_failure' => env('PWNED_PASSWORDS_ON_FAILURE', 'fail_open'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 監査ログ保持設定
    |--------------------------------------------------------------------------
    |
    | 監査ログの保持期間、アーカイブ、クリーンアップに関する設定
    |
    */
    'audit_log' => [
        // ログ保持期間（日数）- 0は無期限
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
        
        // アーカイブを有効にするか
        'archive_enabled' => env('AUDIT_LOG_ARCHIVE_ENABLED', true),
        
        // アーカイブ保存先（storage/app配下のパス）
        'archive_path' => env('AUDIT_LOG_ARCHIVE_PATH', 'audit-archives'),
        
        // アーカイブ形式: 'json' または 'csv'
        'archive_format' => env('AUDIT_LOG_ARCHIVE_FORMAT', 'json'),
        
        // アーカイブ前の最小経過日数
        'archive_after_days' => env('AUDIT_LOG_ARCHIVE_AFTER_DAYS', 90),
        
        // 自動クリーンアップを有効にするか
        'auto_cleanup_enabled' => env('AUDIT_LOG_AUTO_CLEANUP', false),
        
        // クリーンアップ時にアーカイブを保持するか
        'keep_archives' => env('AUDIT_LOG_KEEP_ARCHIVES', true),
        
        // 重要度別の保持期間（日数）- nullはデフォルトを使用
        'retention_by_severity' => [
            'critical' => env('AUDIT_LOG_RETENTION_CRITICAL', null), // 無期限推奨
            'error' => env('AUDIT_LOG_RETENTION_ERROR', null),
            'warning' => env('AUDIT_LOG_RETENTION_WARNING', null),
            'info' => env('AUDIT_LOG_RETENTION_INFO', null),
        ],
        
        // 日次署名（Daily Seal）の保持期間（日数）
        'daily_seal_retention_days' => env('AUDIT_LOG_SEAL_RETENTION_DAYS', 730), // 2年
    ],

    /*
    |--------------------------------------------------------------------------
    | 外部依存サービス障害時の挙動
    |--------------------------------------------------------------------------
    |
    | 外部APIが利用不可の場合の挙動設定
    | 'fail_open': 操作を許可（セキュリティ低下、可用性優先）
    | 'fail_closed': 操作を拒否（セキュリティ優先、可用性低下）
    |
    */
    'external_services' => [
        // CAPTCHA障害時
        'captcha_on_failure' => env('CAPTCHA_ON_FAILURE', 'fail_closed'),
        
        // CAPTCHAタイムアウト（秒）
        'captcha_timeout' => env('CAPTCHA_TIMEOUT', 10),
        
        // GeoIP障害時（将来用）
        'geoip_on_failure' => env('GEOIP_ON_FAILURE', 'fail_open'),
        
        // GeoIPタイムアウト（秒）（将来用）
        'geoip_timeout' => env('GEOIP_TIMEOUT', 5),
    ],

];
