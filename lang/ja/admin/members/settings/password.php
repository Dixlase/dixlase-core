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
    'heading' => 'パスワード設定',
    'description' => 'メンバーのパスワードに関する条件やリセット機能、辞書攻撃対策を設定します。',
    'conditions' => 'パスワードの条件',
    'min_length' => 'パスワードの最小文字数',
    'min_length_options' => [
        8 => '8文字以上',
        12 => '12文字以上',
        16 => '16文字以上',
    ],
    'require_uppercase' => '大文字を含める',
    'require_number' => '数字を含める',
    'require_symbol' => '記号を含める',
    'security_warning' => '条件を下げるとセキュリティリスクが高まりますので、ご注意ください。',
    'reset_settings' => 'パスワードリセット機能設定',
    'reset_enabled' => 'ログイン画面でのパスワードリセット機能',
    'reset_help' => '有効にした場合にはセキュリティリスクが高まる恐れがあるのでご注意ください。<br>無効にした場合、管理画面のログイン画面でパスワードリセットリンクが非表示になり、パスワードリセット機能が利用できなくなります。<br>無効時にパスワードをリセットする場合は、メンバー管理の編集画面から行ってください。',
    'reset_mail_test_required' => 'メールサーバーの設定とテストが完了していないので、パスワードリセット機能を有効にしても動作しません。<br>パスワードリセット機能を使用するには、<a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">基本設定</a>でメールサーバーの設定とテストを完了してください。',
    'pwned_settings' => 'パスワード辞書攻撃対策設定',
    'pwned_check_enabled' => '辞書攻撃対策',
    'pwned_help' => '有効にすると、メンバー作成・編集・パスワード変更時にHave I Been Pwned APIを使用してパスワードの安全性をチェックします。<br>漏洩データベースに含まれているパスワードの使用を防ぎます。',
    'pwned_api_info' => 'このチェックはHave I Been Pwned APIを使用します。パスワード自体は送信されず、ハッシュ化された情報のみが使用されるため安全です。',
];
