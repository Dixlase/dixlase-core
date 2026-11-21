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
    'title' => '本人の確認',
    'description' => 'この操作は、サイトやメンバーの守りを変えます。続けるには、本人であることを確認してください。確認すると、:minutes 分のあいだは聞かれません。',
    'required' => '続けるには、パスワードかパスキーで本人であることを確認してください。',
    'password_label' => 'パスワード',
    'submit' => '確認する',
    'or' => 'または',
    'use_passkey' => 'パスキーを使う',
    'cancel' => 'キャンセル',
    'no_method' => 'このアカウントには、ここで使えるパスワードもパスキーもありません。パスワードの再設定の画面でパスワードを設定してから、もう一度お試しください。',
    'password_incorrect' => 'パスワードが違います。',
    'passkey_unavailable' => 'このアカウントで、ここで使えるパスキーがありません。',
    'passkey_failed' => 'パスキーを確認できませんでした。',
    'passkey_cancelled' => 'パスキーの確認が取り消されました。',
    'too_many_attempts' => '確認に続けて失敗したため、ログアウトしました。続けるには、もう一度ログインしてください。',
    'confirmed' => '確認しました。:minutes 分のあいだは、もう一度聞かれることはありません。',
    'confirmed_resubmit' => '確認しました。もう一度操作を行ってください。:minutes 分のあいだは、もう一度聞かれることはありません。',
    'error' => 'エラーが発生しました。もう一度お試しください。',
];
