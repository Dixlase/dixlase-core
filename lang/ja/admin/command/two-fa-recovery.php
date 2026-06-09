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
    'warning_disable' => '⚠️ 警告: 二段階認証を無効化すると、アカウントのセキュリティが低下します。',
    'confirm_disable' => ':name の二段階認証を無効化しますか？',
    'cancelled' => '操作がキャンセルされました。',
    'disabled_success' => '✅ :name の二段階認証を無効化しました。',
    'security_notice' => '⚠️ セキュリティ上の理由から、ユーザーに二段階認証の再設定を促してください。',
    'warning_reset_codes' => '⚠️ 警告: 回復コードをリセットすると、既存のコードは全て無効になります。',
    'confirm_reset_codes' => ':name の回復コードをリセットしますか？',
    'codes_reset_success' => '✅ :name の回復コードをリセットしました。',
    'new_codes_warning' => '⚠️ 以下の新しい回復コードを安全な場所に保存してください:',
    'codes_save_warning' => '⚠️ これらのコードは二度と表示されません。必ず保存してください。',
    'two_fa_not_enabled' => ':name は二段階認証が有効になっていません。',
    'no_members_with_two_fa' => '二段階認証が有効なメンバーはいません。',
    'members_with_two_fa' => '二段階認証有効',
    'no_codes' => 'なし',
    'col_id' => 'ID',
    'col_name' => '名前',
    'col_email' => 'メールアドレス',
    'col_mode' => '認証モード',
    'col_recovery_codes' => '回復コード残数',
    'member_prompt' => 'メンバーIDまたはメールアドレスを入力してください',
    'member_required' => 'メンバーの指定は必須です。',
    'member_not_found' => 'メンバーが見つかりません: :identifier',
    'reason_prompt' => '復旧の理由を入力してください',
    'reason_required' => '理由の入力は必須です。',
    'member_status_title' => '【:name の二段階認証状態】',
    'field' => '項目',
    'value' => '値',
    'member_id' => 'メンバーID',
    'email' => 'メールアドレス',
    'two_fa_mode' => '二段階認証モード',
    'recovery_codes_remaining' => '回復コード残数',
    'disabled' => '無効',
    'none' => 'なし',
    'system_status_title' => '【システム全体の二段階認証状態】',
    'metric' => '項目',
    'total_members' => '総メンバー数',
    'members_without_codes' => '回復コードなし',
    'warning_no_codes' => '⚠️ :count 名のメンバーが回復コードを持っていません。詰みリスクがあります。',
    'invalid_action' => '無効なアクション: :action',
    'valid_actions' => '有効なアクション:',
    'action_disable' => '二段階認証を無効化',
    'action_reset_codes' => '回復コードをリセット',
    'action_status' => '状態を表示',
    'action_list' => '二段階認証有効なメンバー一覧',
];
