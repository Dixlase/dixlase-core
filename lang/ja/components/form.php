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
    'placeholder' => [
        'search' => '検索キーワードを入力...',
        'email' => 'メールアドレスを入力',
        'password' => 'パスワードを入力',
        'name' => '名前を入力',
        'title' => 'タイトルを入力',
        'description' => '説明を入力',
    ],
    'show_password' => 'パスワードを表示',
    'hide_password' => 'パスワードを非表示',
    'validation' => [
        'required' => 'この項目は必須です',
        'email' => '有効なメールアドレスを入力してください',
        'unique' => 'この値は既に存在しています',
        'min_length' => '最低 :min 文字以上で入力してください',
        'max_length' => '最大 :max 文字以内で入力してください',
        'confirmed' => 'パスワード確認が一致しません',
    ],
];
