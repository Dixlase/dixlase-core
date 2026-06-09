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

return [
    'admin_count' => ':adminCount人',
    'app_already_installed' => 'このアプリケーションは既にインストールされています。',
    'check_install_admin_user_check' => 'CheckInstallationReady: 管理者ユーザー存在確認',
    'check_install_migration_check_error' => 'CheckInstallationReady: マイグレーションチェックエラー - ',
    'check_install_migration_completed' => 'CheckInstallationReady: ✅ マイグレーション完了を確認（全チェック通過）',
    'check_install_no_admin_user' => 'CheckInstallationReady: 管理者ユーザーが存在しません（初期データ未投入）',
    'check_install_no_db_connection' => 'CheckInstallationReady: データベース接続なし',
    'check_install_no_site_settings_data' => 'CheckInstallationReady: site_settings に初期データが存在しません',
    'check_install_session_driver_switched' => 'CheckInstallationReady: セッションドライバーを一時的にfileに切り替えました',
    'check_installation_table_missing' => 'CheckInstallationReady: 主要テーブル \':table\' が存在しません',
    'migration_count_required' => ':migrationCount件 (必要: :minRequiredMigrations)',
    'skip_direct_sql_execution' => 'SKIP (直接SQL実行の可能性)',
    'skip_migrations_table_not_found' => 'SKIP (migrationsテーブル不在)',
];
