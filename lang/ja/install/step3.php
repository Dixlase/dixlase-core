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
    'database_title' => 'データベース設定',
    'database_header' => 'データベース情報を入力してください',
    'database_description' => 'システムで使用するデータベースの設定を行います。',

    // データベース設定関連
    'database_connection_settings' => 'データベース接続設定',
    'database_connection_details' => 'データベース接続詳細',
    'data_preservation_settings' => 'データ保持設定',
    'database_preservation_options' => 'データベース保持オプション',
    'database_connection_test' => 'データベース接続テスト',

    'db_connection' => 'データベースの種類',
    'db_host' => 'データベースホスト',
    'db_port' => 'データベースポート',
    'db_database' => 'データベース名',
    'db_username' => 'データベースユーザー名',
    'db_password' => 'データベースパスワード',
    'db_password_required' => 'データベースパスワードは必須です。',
    'db_database_sqlite_help' => 'SQLite の場合は絶対パスを入力してください。ファイルが存在しなければ自動生成されます。',

    'preserve_database' => 'データベースをリセットしない',
    'preserve_database_help' => 'チェックを入れると、既存のデータを保持したまま必要な更新のみを適用します。チェックを外すと、インストール時に既存のデータがすべて削除されます。',

    'test_db_connection' => '接続テスト',
    'db_connection_success' => 'データベース接続成功！',
    'db_connection_error' => 'データベース接続に失敗しました: :error',
    'db_test_required' => '⚠️ 次の画面へ進む前にデータベース接続テストを行ってください。',
    'db_test_success' => '✅ データベース接続が成功しました！次の画面へお進みください！',
];
