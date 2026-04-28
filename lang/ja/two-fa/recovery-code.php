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
    // 回復コード入力画面
    'title' => '回復コード',
    'prompt' => '回復コードを入力してください。デバイスにアクセスできない場合、回復コードを使用してログインできます。',
    'code_label' => '回復コード',
    'format_hint' => '20桁の数字を入力してください（ハイフンあり・なし両方可）',
    'submit' => '認証してログイン',
    'invalid' => '回復コードが無効です。',
    'invalid_with_attempts' => '回復コードが無効です。残り試行回数: :attempts回',
    'use_recovery_code' => '回復コード',
    'back_to_two_fa' => '二段階認証に戻る',

    // 回復コード表示モーダル
    'warning' => 'これらの回復コードは安全な場所に保管してください。<br>デバイスにアクセスできない場合、これらのコードを使用してアカウントにアクセスできます。',
    'confirm_saved' => '回復コードを安全な場所に保存したことを確認しました',
    'auto_generated_title' => '回復コードが生成されました',
    'auto_generated_message' => '二段階認証が有効になったため、回復コードが自動生成されました。',
];
