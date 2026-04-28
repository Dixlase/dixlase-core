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

return [
    'type_required' => '--type オプションが必要です',
    'confirm_delete_all' => ':type の全レコードを削除してもよろしいですか？',
    'operation_cancelled' => '操作がキャンセルされました',
    'force_required' => 'Webインターフェースからの実行時は --force フラグが必要です',
    'invalid_days' => '日数は0以上の整数を指定してください',
    'type_not_found' => 'クリーンアップタイプ :type が見つかりません',
    'deleting_all' => ':type の全レコードを削除しています...',
    'cleaning_up' => ':type の :days 日より古いレコードをクリーンアップしています...',
    'deleted_success' => ':count 件のレコードを正常に削除しました',
    'cleanup_failed' => 'クリーンアップに失敗しました: :error',
    'cleaning_up_all' => '全テーブルの :days 日より古いレコードをクリーンアップしています...',
    'deleted_all_success' => '合計 :count 件のレコードを正常に削除しました',
    'available_types' => '利用可能なクリーンアップタイプ:',
    'core_tables' => '【コアテーブル】',
    'plugin_tables' => '【プラグインテーブル】',
    'no_plugin_tables' => 'プラグインのクリーンアップテーブルはありません',
    'usage_examples' => '使用例:',
    'table_type' => 'テーブルタイプ',
    'table_count' => '削除件数',
];
