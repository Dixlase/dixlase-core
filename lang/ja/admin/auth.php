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
 */

return [
    'login' => [
        'title' => '管理画面ログイン',
        'header' => '管理画面ログイン',
        'description' => '管理画面にアクセスするにはログインしてください。',
        'login_field' => 'メールアドレスまたはアカウント名',
        'remember_me' => 'ログイン状態を保持する',
        'forgot_password' => 'パスワードをお忘れですか？',
        'captcha' => 'セキュリティ認証',
        'back_to_welcome' => 'サイトに戻る',
    ],
    'forgot_password' => [
        'title' => 'パスワードリセット',
        'header' => 'パスワードリセット',
        'description' => 'パスワードをお忘れですか？<br>メールアドレスを入力してください。<br>パスワードリセット用のリンクをお送りします。',
        'email' => 'メールアドレス',
        'send_reset_link' => 'パスワードリセットリンクを送信',
        'back_to_login' => 'ログイン画面に戻る',
    ],
    'reset_password' => [
        'title' => '新しいパスワードの設定',
        'header' => '新しいパスワードの設定',
        'description' => '新しいパスワードを設定してください。',
        'email' => 'メールアドレス',
        'password' => '新しいパスワード',
        'password_confirmation' => 'パスワード確認',
        'reset_password_button' => 'パスワードをリセット',
    ],
];
