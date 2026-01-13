<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;

class PasswordHelper
{
    /**
     * パスワードをハッシュ化（配列内のパスワードフィールドを処理）
     * 
     * パスワードが空の場合は配列から削除します。
     * パスワードが存在する場合はハッシュ化します。
     *
     * @param array &$data パスワードフィールドを含む配列（参照渡し）
     * @param string $field パスワードフィールド名（デフォルト: 'password'）
     * @return void
     */
    public static function hashPasswordIfPresent(array &$data, string $field = 'password'): void
    {
        if (!empty($data[$field])) {
            $data[$field] = Hash::make($data[$field]);
        } else {
            unset($data[$field]);
        }
    }

    /**
     * パスワードをハッシュ化（文字列を直接ハッシュ化）
     *
     * @param string $password 平文パスワード
     * @return string ハッシュ化されたパスワード
     */
    public static function hash(string $password): string
    {
        return Hash::make($password);
    }

    /**
     * パスワードを検証
     *
     * @param string $password 平文パスワード
     * @param string $hashedPassword ハッシュ化されたパスワード
     * @return bool
     */
    public static function verify(string $password, string $hashedPassword): bool
    {
        return Hash::check($password, $hashedPassword);
    }

    /**
     * パスワードの再ハッシュ化が必要かチェック
     *
     * @param string $hashedPassword ハッシュ化されたパスワード
     * @return bool
     */
    public static function needsRehash(string $hashedPassword): bool
    {
        return Hash::needsRehash($hashedPassword);
    }
}
