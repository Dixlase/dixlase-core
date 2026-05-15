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
    'name_prompt' => 'ネットワークキーの表示名',
    'name_required' => '名前は必須です。',

    'invalid_environment' => '不正な --environment :environment です (live または test を指定してください)。',
    'invalid_scope' => "未知のスコープ: :scopes\n利用可能なスコープ: :available",

    'warning' => '警告: ネットワークキーは全サイト (site_id = null) で認証可能です。ルート資格情報と同等に扱ってください。',

    'summary_title' => '以下の設定でネットワーク API キーを作成します:',
    'field' => '項目',
    'value' => '値',
    'summary_name' => '名前',
    'summary_environment' => '環境',
    'summary_scopes' => 'スコープ',
    'summary_rate_limit' => 'レート制限',
    'summary_expires_at' => '有効期限',
    'summary_description' => '説明',

    'confirm_create' => 'このネットワークキーを作成しますか?',
    'cancelled' => 'キャンセルされました。キーは作成されていません。',

    'created' => 'ネットワーク API キーを作成し、audit_logs に記録しました (severity = critical)。',
    'plain_key_warning' => '平文キー (1 度だけ表示します。今コピーしてください — 後から復元できません):',
    'id_label' => 'ID',
    'prefix_label' => 'プレフィックス',
    'audit_logged' => 'このキーの使用は audit_logs に "network_api_key_used" として記録されます。',
];
