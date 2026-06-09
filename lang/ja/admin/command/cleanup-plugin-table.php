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
    'missing_options' => '--plugin と --table オプションは必須です。',
    'table_not_found' => 'テーブル :table が見つかりません。',
    'column_not_found' => 'テーブル :table にカラム :column が見つかりません。',
    'table_not_allowed' => 'テーブル :table はプラグイン :plugin のクリーンアップ対象として許可されていません。',
    'no_records' => '削除対象のレコードはありません。',
    'confirm' => ':table から :days 日より古い :count 件のレコードを削除しますか？',
    'cancelled' => '操作がキャンセルされました。',
    'success' => ':table から :count 件のレコードを削除しました。',
];
