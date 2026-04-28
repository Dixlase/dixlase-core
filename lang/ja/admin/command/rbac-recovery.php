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

        'warning_grant' => '⚠️ 警告: スーパー管理者権限を付与すると、全ての操作が可能になります。',
        'confirm_grant' => ':name にスーパー管理者権限を付与しますか？',
        'cancelled' => '操作がキャンセルされました。',
        'role_created' => 'スーパー管理者ロールを作成しました。',
        'grant_success' => '✅ :name にスーパー管理者権限を付与しました。',
        'security_notice' => '⚠️ セキュリティ上の理由から、復旧後は適切な権限設定を見直してください。',
        'role_status_title' => '【:name のロール状態】',
        'current_permissions' => '現在の権限数: :count',
        'warning_reset' => '⚠️ 警告: ロールをリセットすると、現在の権限設定が失われます。',
        'confirm_reset' => ':name のロールをデフォルトにリセットしますか？',
        'reset_success' => '✅ :name をデフォルト権限（:count 件）にリセットしました。',
        'system_status_title' => '【RBAC システム状態】',
        'metric' => '項目',
        'value' => '値',
        'total_roles' => '総ロール数',
        'total_members' => '総メンバー数',
        'members_with_roles' => 'ロール割当済み',
        'super_admins' => 'スーパー管理者数',
        'warning_no_super_admin' => '⚠️ スーパー管理者がいません！管理画面にアクセスできなくなる可能性があります。',
        'roles_title' => '【ロール一覧】',
        'no_roles' => 'ロールが存在しません。',
        'col_id' => 'ID',
        'col_name' => '名前',
        'col_display_name' => '表示名',
        'col_permissions' => '権限数',
        'col_members' => 'メンバー数',
        'col_email' => 'メールアドレス',
        'col_roles' => 'ロール',
        'members_title' => '【メンバー一覧】',
        'no_members' => 'メンバーが存在しません。',
        'member_prompt' => 'メンバーIDまたはメールアドレスを入力してください',
        'member_required' => 'メンバーの指定は必須です。',
        'member_not_found' => 'メンバーが見つかりません: :identifier',
        'role_prompt' => 'ロールIDまたは名前を入力してください',
        'role_required' => 'ロールの指定は必須です。',
        'role_not_found' => 'ロールが見つかりません: :identifier',
        'reason_prompt' => '復旧の理由を入力してください',
        'reason_required' => '理由の入力は必須です。',
        'member_status_title' => '【:name の権限状態】',
        'field' => '項目',
        'member_id' => 'メンバーID',
        'email' => 'メールアドレス',
        'current_roles' => '現在のロール',
        'none' => 'なし',
        'invalid_action' => '無効なアクション: :action',
        'valid_actions' => '有効なアクション:',
        'action_grant' => 'スーパー管理者権限を付与',
        'action_reset' => 'ロールをデフォルトにリセット',
        'action_status' => '状態を表示',
        'action_list' => 'メンバーとロール一覧',
];
