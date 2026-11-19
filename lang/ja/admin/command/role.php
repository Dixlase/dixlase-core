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
    'invalid_slug' => 'スラッグ「:slug」は使えません。英小文字・数字・ハイフンで、100 文字以内にしてください。',
    'slug_taken' => 'スラッグ「:slug」のカスタムロールは既にあります（削除済みのものを含みます）。',
    'invalid_base' => 'ベースのロール「:base」は使えません。admin・editor・contributor・guest のどれかにしてください。スーパー管理者はベースにできません。',
    'invalid_actor_type' => 'アクターの種類「:type」は使えません。human・ai・service のどれかにしてください。',
    'invalid_max_members' => 'メンバー数の上限「:value」は使えません。1 以上の整数にしてください。',
    'created' => 'カスタムロール「:slug」を :base をベースに作成しました。',
    'role_not_found' => 'カスタムロールが見つかりません: :slug',
    'invalid_type' => '種類「:type」は使えません。grant・deny・clear のどれかにしてください。',
    'unknown_permission' => 'コアの権限「:permission」はありません。プラグインのメニューキーなら --plugin=<ディレクトリ名> を付けてください。',
    'unknown_plugin_key' => 'プラグイン「:plugin」の config/admin/roles.php にメニューキー「:key」の宣言がありません。',
    'site_not_found' => 'サイトが見つかりません: :site',
    'override_set' => 'カスタムロール「:slug」: サイト :site で :permission を :type にしました。',
    'member_not_found' => 'メンバーが見つかりません: :member',
    'super_admin_refused' => ':member はスーパー管理者なので、カスタムロールを割り当てられません。先にロールを変えてください。',
    'role_full' => 'カスタムロール「:slug」のメンバーは上限の :max 人に達しています。',
    'assigned' => 'カスタムロール「:slug」を :member に割り当てました。ロールは :base になりました。',
    'assigned_inactive' => 'カスタムロール「:slug」は無効です。有効にするまで上書きは効きません。',
    'unassigned' => ':member のカスタムロールを外しました。ロールはそのままです。',
    'no_roles' => 'カスタムロールはありません。',
    'no_overrides' => 'カスタムロール「:slug」に上書きはありません。',
    'col_slug' => 'スラッグ',
    'col_name' => '名前',
    'col_base' => 'ベースのロール',
    'col_actor_type' => 'アクターの種類',
    'col_active' => '有効',
    'col_members' => 'メンバー',
    'col_site' => 'サイト',
    'col_permission' => '権限',
    'col_type' => '種類',
    'yes' => 'はい',
    'no' => 'いいえ',
];
