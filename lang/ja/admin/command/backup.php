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

    // 共通メッセージ
    'starting' => 'バックアップを開始します...',
    'completed' => 'バックアップが完了しました！',
    'no_targets' => 'バックアップ対象が指定されていません。',
    'use_options' => '--all, --core, --plugins, --themes, --custom, --storage-public, --storage-private, --logs, --database を使用してください',
    'cannot_use_both_only' => '--files-only と --db-only を同時に使用することはできません。',
    'summary' => 'バックアップサマリー',
    'no_backups_created' => 'バックアップは作成されませんでした。',
    'files_saved' => 'ファイルバックアップ: :path',
    'database_saved' => 'データベースバックアップ: :path',

    // ファイルバックアップ
    'section_files' => 'ファイルバックアップ',
    'creating_file_backup' => 'ファイルバックアップを作成中: :file',
    'failed_to_create_zip' => 'ZIPファイルの作成に失敗しました。',
    'path_not_found' => 'パスが見つかりません: :path',
    'adding_target' => ':target を追加中: :path',
    'no_files_added' => 'バックアップに追加するファイルがありませんでした。',
    'file_backup_completed' => 'ファイルバックアップ完了: :file (:count ファイル, :size)',
    'no_paths_to_backup' => 'バックアップするパスがありません。',

    // データベースバックアップ
    'section_database' => 'データベースバックアップ',
    'creating_database_backup' => 'データベースバックアップを作成中: :file',
    'database_config_not_found' => 'データベース設定が見つかりません。',
    'running_mysqldump' => 'mysqldump を実行中...',
    'mysqldump_failed' => 'mysqldump が失敗しました: :error',
    'database_backup_completed' => 'データベースバックアップ完了: :file (テーブル: :tables, :size)',
    'all_tables' => 'すべてのテーブル',

    // バックアップ一覧
    'list' => [
        'title' => '利用可能なバックアップ',
        'file_backups' => 'ファイルバックアップ',
        'database_backups' => 'データベースバックアップ',
        'no_file_backups' => 'ファイルバックアップはありません。',
        'no_database_backups' => 'データベースバックアップはありません。',
        'filename' => 'ファイル名',
        'size' => 'サイズ',
        'date' => '作成日時',
        'backup_directory' => 'バックアップディレクトリ: :path',
    ],

    // バックアップクリーンアップ
    'cleanup' => [
        'invalid_days' => '日数は0以上の整数である必要があります。',
        'confirm_delete_all' => 'すべてのバックアップを削除してもよろしいですか？この操作は元に戻すことができません。',
        'confirm_delete_old' => ':days 日より古いバックアップを削除してもよろしいですか？',
        'cancelled' => '操作がキャンセルされました。',
        'starting' => '古いバックアップを削除中...',
        'no_backups_deleted' => '削除するバックアップはありませんでした。',
        'deleted_count' => ':count 件のバックアップを削除しました。',
    ],

    // 古いバックアップ削除
    'deleted_old_backup' => '古いバックアップを削除しました: :file',
];
