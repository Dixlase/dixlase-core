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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

return [
    'heading' => 'データベース管理',
    'description' => 'システムパフォーマンスを維持するために古いデータベースレコードをクリーンアップします',
    'core_cleanup_heading' => 'コアテーブルのクリーンアップ',
    'core_cleanup_description' => 'Dixlaseコアシステムが使用するテーブルのクリーンアップ設定です。',
    'default_retention' => 'デフォルト保持期間: :days 日',
    'expired_only' => '期限切れのみ削除',
    'all_cleanup_button' => '全種類クリーンアップ',
    'all_cleanup_description' => 'すべてのデータベーステーブルを共通の保持日数でクリーンアップします',
    'all_cleanup_warning' => 'この操作は元に戻すことができません。',
    'all_tables' => 'すべてのテーブル',
    'all_days_label' => '共通保持日数',
    'all_days_help' => '0日を指定するとすべてのレコードを削除します。個別に日数を指定したい場合は各項目のクリーンアップを実行してください。',
    'info_title' => 'クリーンアップ対象の説明',
    'info_login_attempts' => 'ログイン試行履歴の古いレコードを削除します。',
    'info_password_reset' => 'パスワードリセットトークンの期限切れレコードを削除します。',
    'info_two_fa_attempts' => '二段階認証試行履歴の古いレコードを削除します。',
    'info_two_fa_tokens' => '二段階認証トークンの期限切れレコードを削除します。',
    'info_recovery_codes' => '使用済みの古い回復コードを削除します。',
    'info_passkeys' => '削除済みの古いPASSKEYを完全に削除します。',
    'info_cache' => 'キャッシュデータの期限切れレコードを削除します。',
    'info_sessions' => 'セッションの古いレコードを削除します。',
    'login_attempts' => [
        'name' => 'ログイン試行履歴',
        'description' => '古いログイン試行記録をクリーンアップします',
        'invalid_days' => '日数は0以上の整数である必要があります。',
    ],
    'info_panel' => [
        'title' => '重要な注意事項',
        'notes' => [
            'irreversible' => 'データベースクリーンアップは不可逆的な操作です。実行前に必要なデータのバックアップを取ることをお勧めします。',
            'performance' => '定期的なクリーンアップはシステムパフォーマンスの向上に役立ちます。',
            'production' => '本番環境では慎重に実行し、メンテナンス時間中に行うことをお勧めします。',
            'defaults' => 'デフォルト設定は推奨値に基づいています。必要に応じて調整してください。',
        ],
    ],
    'modal' => [
        'title' => 'データベースクリーンアップの確認',
        'message' => 'この操作を実行しますか？',
        'message_single' => ':name のクリーンアップを実行しますか？',
        'message_all' => 'すべてのデータベーステーブルのクリーンアップを実行しますか？',
        'message_plugin' => 'プラグイン「:plugin」の :name のクリーンアップを実行しますか？',
    ],
    'plugin_cleanup_heading' => 'プラグインデータのクリーンアップ',
    'plugin_cleanup_description' => '有効なプラグインが提供するクリーンアップ対象テーブルです。各プラグインのplugin.jsonで定義されています。',
    'plugin_cleanup_description_config' => '有効なプラグインが提供するクリーンアップ対象テーブルです。各プラグインのdatabase-cleanup.phpで定義されています。',
    'password_reset_tokens' => [
        'name' => 'パスワードリセットトークン',
        'description' => '古いパスワードリセットトークン記録をクリーンアップします',
        'invalid_days' => '日数は0以上の整数である必要があります。',
    ],
    'two_fa_attempts' => [
        'name' => '二段階認証試行履歴',
        'description' => '古い二段階認証試行履歴をクリーンアップします',
        'invalid_days' => '日数は0以上の整数である必要があります。',
    ],
    'two_fa_tokens' => [
        'name' => '二段階認証トークン(メール認証)',
        'description' => '期限切れの二段階認証(メール認証)の認証コードをクリーンアップします',
        'default_days' => '7日',
    ],
    'recovery_codes' => [
        'name' => '回復コード',
        'description' => '使用済み・無効化された回復コードをクリーンアップします',
        'default_days' => '90日',
    ],
    'passkeys' => [
        'name' => '二段階認証用PASSKEY(生体認証)',
        'description' => '削除済みの古い二段階認証用PASSKEY(生体認証)をクリーンアップします',
        'default_days' => '90日',
    ],
    'backup_records' => [
        'name' => 'バックアップ記録',
        'description' => '期限切れまたは削除済みのバックアップ記録を削除します',
        'default_days' => '365日',
    ],
    'restore_records' => [
        'name' => '復元履歴',
        'description' => '指定した保持期間より古い復元履歴レコードを削除します',
        'default_days' => '365日',
    ],
    'audit_logs' => [
        'name' => '監査ログ',
        'description' => '指定した保持期間より古い監査ログレコードを削除します',
        'default_days' => '365日',
    ],
    'api_request_logs' => [
        'name' => 'APIリクエストログ',
        'description' => '指定した保持期間より古いAPIリクエストログレコードを削除します',
        'default_days' => '90日',
    ],
    'cache_data' => [
        'name' => 'キャッシュデータ',
        'description' => '期限切れのキャッシュエントリとロックをクリーンアップします',
        'default_days' => '期限切れのみ',
    ],
    'sessions' => [
        'name' => 'セッション',
        'description' => '古いセッション記録をクリーンアップします',
        'default_days' => '7日',
    ],
    'cleanup_success' => ':count 件のレコードを正常にクリーンアップしました。',
    'cleanup_error' => 'クリーンアップ中にエラーが発生しました: :error',
    'confirm_cleanup' => ':type のレコードをクリーンアップしてもよろしいですか？',
    'days_label' => '保持日数',
    'days_zero_info' => '0日を指定するとすべてのレコードを削除します',
    'cleanup_button' => 'クリーンアップ',
];
