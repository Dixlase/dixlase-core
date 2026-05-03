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
    'heading' => 'コンテンツ設定',
    'description' => 'フロントページや固定ページなど、コンテンツ全般に関する共通設定を行います。',
    'revision_settings' => 'リビジョン設定',
    'revision_retention_count' => 'リビジョン保持件数',
    'revision_retention_count_help' => 'コンテンツ1件あたりに保持するリビジョンの最大件数です（デフォルト: :default 件、最大: :max 件）。上限を超えた場合、古いリビジョンから自動削除されます。',
    'revision_retention_count_zero_help' => '0 を指定するとリビジョン機能が無効になります。',
    'settings_updated' => 'コンテンツ設定を更新しました。',
];
