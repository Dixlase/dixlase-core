<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
    // 共通メッセージ
    'security_notice' => 'このログインに心当たりがない場合は、第三者によってログインが試行された可能性があります。  
不正アクセスのリスクがありますので、至急、パスワードを変更するか、システム管理者にお問い合わせください。',
    'regards' => 'よろしくお願いいたします。',
    'details_title' => 'ログイン試行の詳細',
    'ip_address' => 'IPアドレス',
    'user_agent' => 'ブラウザ/デバイス',
    'timestamp' => '日時',
    
    // メール認証
    'email' => [
        'subject' => '【:app_name】二段階認証コード',
        'greeting' => 'こんにちは！',
        'message' => 'ログインのための二段階認証コードをお送りします。',
        'instructions' => 'このコードをログイン画面で入力してください。コードの有効期限は10分間です。',
    ],
];
