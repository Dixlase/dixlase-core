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
    'prompt' => <<<'TEXT'
認証コードが書かれたメールを送信しました。
メールに書かれている6桁の認証コードを入力してください。
TEXT,
    'code_title' => 'メール認証',
    'code_prompt' => 'メールに記載された6桁の認証コードを入力してください。',
    'code_label' => '認証コード',
    'expire_notice' => '認証コードは :minutes 分間有効です。',
    'expire_label' => 'コードの有効期限',
    'expired' => '期限切れ',
    'submit' => '認証してログイン',
    'verify' => '認証する',
    'resend' => '認証コードを再送信する',
    'invalid' => '認証コードが間違っているか、有効期限が切れています。',
    'invalid_code' => '認証コードが間違っているか、有効期限が切れています。',
    'invalid_with_attempts' => '認証コードが間違っています。残り試行回数: :attempts回',
    'resend_success' => 'メールを再送信しました。',
    'resend_failed' => 'コードの再送信に失敗しました',
    'network_error' => 'ネットワークエラーが発生しました',
    'minutes_suffix' => '分',
    'seconds_suffix' => '秒',
    'use_email_code' => 'メール認証',
];
