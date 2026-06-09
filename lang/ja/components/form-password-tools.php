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
    'toolbar_label' => 'パスワードツール',
    'strength' => [
        'error' => 'パスワードが条件を満たしていません',
        'normal' => '普通の強度',
        'strong' => '強いパスワード',
    ],
    'tooltip' => [
        'generate' => '自動生成',
        'copy' => 'コピー',
        'toggle' => '表示切替',
    ],
    'copied' => 'パスワードがコピーされました！',
    'requirements' => [
        'length' => '8文字以上',
        'lowercase' => '小文字を1文字以上含む',
        'number' => '数字を1文字以上含む',
        'uppercase' => '大文字を1文字以上含む',
        'symbol' => '記号（!@#$%^&* など）を1文字以上含む',
        'length_full' => ':min文字以上（推奨 :recommended 文字以上）',
        'length_simple' => ':min文字以上',
        'lowercase_optional_note' => '小文字を含む',
        'number_optional_note' => '数字を含む',
        'uppercase_optional_note' => '大文字を含む',
        'symbol_optional_note' => '記号（!@#$%^&*-_=+など）',
        'weak' => '弱い',
        'normal' => '普通',
        'strong' => '強い',
        'very_strong' => '非常に強い',
    ],
    'error' => 'パスワードが条件を満たしていません。',
];
