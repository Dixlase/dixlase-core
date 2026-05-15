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
    'all_tables_deleted' => 'データベーステーブルをすべて削除しました。',
    'app_cache_skipped_no_tables' => '⚠️ アプリケーションClear cacheをスキップしました（テーブルが存在しない可能性）。',
    'application_cache_cleared' => '✔️ アプリケーションキャッシュをクリアしました。',
    'backup_error' => '❌ バックアップエラー: ',
    'basic_cache_cleared' => '✔️ 基本キャッシュをクリアしました。',
    'clear_cache_error' => 'Clear cache中にエラーが発生しました: ',
    'composer_autoload_regenerated' => '✔️ Composer autoload を再生成しました。',
    'composer_autoload_skipped_not_found' => '⚠️ Regenerate Composer autoloadをスキップしました（composerコマンドが見つからない）。',
    'config_cache_cleared' => '✔️ 設定キャッシュをクリアしました。',
    'config_cache_regen_skipped_uninstall' => 'ℹ️ アンインストール完了のため、設定キャッシュの再生成はスキップしました。',
    'confirm_database_backup' => 'データベースをバックアップしますか？',
    'confirm_delete_all_tables' => 'データベーステーブルをすべて削除しますか？',
    'confirm_env_backup' => '.env を削除せずにバックアップしますか？',
    'confirm_uninstall' => '本当にアンインストールしますか？',
    'database_backed_up' => 'データベースをバックアップしました: :dumpFile',
    'env_backed_up' => '✅ .env をバックアップしました: :backupPath',
    'env_creation_info' => 'ℹ️ インストール時に .env.example から新しい .env が作成されます。',
    'env_deletion_failed' => '❌ .env の削除に失敗しました。手動で削除してください: :envPath',
    'env_file_deleted' => '✅ .env ファイルを完全に削除しました。',
    'env_file_not_found' => '⚠️ .env ファイルが存在しません。',
    'force_option_specified' => '⚠️  --forceオプションが指定されました。対話なしでアンインストールを実行します。',
    'force_skip_database_backup' => '--forceオプション: データベースバックアップをスキップします。',
    'force_skip_env_backup' => '--forceオプション: .envバックアップをスキップします。',
    'optimize_clear_partially_skipped' => '⚠️ optimize:clearの一部をスキップしました。',
    'plugin_symlink_removed' => '✔️ プラグインアセットのシンボリックリンクを削除しました: :pluginLink',
    'regenerating_composer_autoload' => '🔄 Composer autoload を再生成中...',
    'reinstall_cache_cleared' => '   すべてのキャッシュがクリアされているため、',
    'reinstall_no_additional_commands' => '   追加のコマンド実行なしで再インストールが可能です。',
    'reinstall_notes' => '📝 再インストール時の注意:',
    'session_driver_change_failed' => '⚠️ セッションドライバの変更に失敗しました: ',
    'session_driver_changed_to_file' => '✔️ セッションドライバをfileに変更しました。',
    'sqlite_database_deleted' => 'SQLiteデータベースファイルを削除しました: :dbPath',
    'storage_symlink_removed' => '✔️ storage のシンボリックリンクを削除しました。',
    'theme_asset_symlinks_removed' => '✔️ テーマアセットのシンボリックリンクを削除しました。',
    'uninstall_cancelled' => 'アンインストールをキャンセルしました。',
    'uninstall_command_signature' => 'dls:app:uninstall {--force : 対話なしで即座にアンインストールを実行}',
    'uninstall_completed' => '✅ アンインストールが完了しました！',
    'uninstall_description' => 'アプリケーションをアンインストールし、環境設定とデータベースを削除します。',
    'uninstall_warning_message' => '⚠️  注意: この操作はアプリケーションを完全に削除します！',
];
