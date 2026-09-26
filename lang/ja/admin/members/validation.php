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
    'account_name_required' => 'アカウント名を入力してください。',
    'account_name_alpha_num' => 'アカウント名は半角英数字のみ使用できます。',
    'account_name_length' => 'アカウント名は3〜20文字で入力してください。',
    'email_required' => 'メールアドレスを入力してください。',
    'email_invalid' => '有効なメールアドレスを入力してください。',
    'email_unique' => 'このメールアドレスは既に使用されています。',
    'password_required' => 'パスワードを入力してください。',
    'password_min' => 'パスワードは8文字以上で入力してください。',
    'password_confirmed' => 'パスワードが一致しません。',
    'role_required' => 'ロールを選択してください。',
    'role_invalid' => '無効なロールが選択されています。',
    'appearance_required' => '外観モードを選択してください。',
    'appearance_invalid' => '無効な外観モードが選択されています。',
    'status_required' => 'ステータスを選択してください。',
    'status_invalid' => '無効なステータスが選択されています。',
    'two_fa_cannot_enable' => '二段階認証を有効化できません。メールサーバーの設定、パスキーの登録、または回復コードの生成のいずれかが必要です。',
    'two_fa_cannot_enable_new_member' => '新規メンバーの二段階認証を有効化するには、メールサーバーの設定が必要です。',
    'role_above_own' => '自分より上位のロールは設定できません。',
    'initial_admin_locked' => '初期管理者のロールとステータスは変更できません。',
    'own_role_locked' => '自分自身のロールとステータスは変更できません。',
];
