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

    /*
    |--------------------------------------------------------------------------
    | パスワード言語行
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、パスワードの結果として、無効なパスワード/リセットトークンなどの
    | 理由を示すデフォルトのメッセージです。
    |
    */

    'reset' => 'パスワードがリセットされました。',
    'sent' => 'パスワードリセットリンクをメールで送信しました。',
    'throttled' => '再試行する前にお待ちください。',
    'token' => 'このパスワードリセットトークンは無効です。',
    'user' => 'そのメールアドレスのユーザーが見つかりません。',
    'error' => 'パスワードが条件を満たしていません',
    'requirements' => [
        'password' => 'パスワードの要件',
        'length_full' => ':min文字以上（:recommended文字以上推奨）',
        'length_simple' => ':min文字以上',
        'suggestion' => ':length文字以上推奨',
        'lowercase' => '1文字以上の小文字',
        'number' => '1文字以上の数字',
        'symbol_required' => '記号（!@#$%^&* など）を含む',
        'symbol_optional' => '記号（!@#$%^&* など）を含むと強度UP（推奨）',
        'uppercase_required' => '1文字以上の大文字',
        'uppercase_optional' => '1文字以上の大文字（推奨）',
        'password_min_length' => 'パスワードの最小文字数',
        'password_min_length_options' => [
            8 => '8文字以上',
            12 => '12文字以上',
            16 => '16文字以上',
        ],
        'password_require_uppercase' => '大文字を含める',
        'password_require_uppercase_options' => [
            1 => '含める',
            0 => '含めない',
        ],
        'password_require_symbol' => '記号を含める',
        'password_require_symbol_options' => [
            1 => '含める',
            0 => '含めない',
        ],
        'weak' => '弱いパスワード',
        'normal' => '普通の強度',
        'strong' => '強いパスワード',
        'very_strong' => '非常に強いパスワード',
    ],
    'tooltip' => [
        'generate' => 'パスワードを自動生成',
        'copy' => 'パスワードをコピー',
        'toggle' => 'パスワードの表示切り替え',
    ],
];
