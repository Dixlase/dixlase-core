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
    // メール認証（共通）
    'subject' => 'メールアドレスの確認',
    'subject_account' => ':typeアカウントの確認',
    'greeting' => ':nameさん、こんにちは！',
    'message_create' => 'ご登録ありがとうございます。以下のボタンをクリックして、メールアドレスの認証を完了してください。',
    'message_email_change' => 'メールアドレスが変更されました。以下のボタンをクリックして、メールアドレスの変更を完了させてください。',
    'message_resend' => 'メールアドレスの認証が必要です。以下のボタンをクリックして、認証を完了してください。',
    'action_verify_account' => 'メールアドレスを認証',
    'action_change_email' => 'メールアドレスを変更',
    'manual_verification' => 'ボタンをクリックできない場合は、以下のURLをコピーしてブラウザに貼り付けてください:',
    'expiration' => 'この確認リンクは:minutes分後に期限切れになります。',
    'security_notice' => '【重要】このメールに心当たりがない場合は、このメールを無視してください。あなたが認証リンクをクリックしない限り、アカウントは有効化されません。第三者がこのメールアドレスを誤って登録した可能性がありますが、あなたの個人情報が漏洩することはありません。',
    'regards' => 'よろしくお願いいたします',
    
    // メンバーメール認証
    'member' => [
        'subject' => 'メールアドレス変更の確認',
        'subject_account' => 'メンバーアカウントの確認',
        'subject_create' => 'メールアドレスの確認',
        'subject_email_change' => 'メールアドレス変更の確認',
        'subject_resend' => 'メールアドレスの確認（再送）',
        'greeting' => ':nameさん、こんにちは！',
        'message_create' => 'ご登録ありがとうございます。以下のボタンをクリックして、メールアドレスの認証を完了してください。',
        'message_email_change' => 'メールアドレスが変更されました。以下のボタンをクリックして、メールアドレスの変更を完了させてください。',
        'message_resend' => 'メールアドレスの認証が必要です。以下のボタンをクリックして、認証を完了してください。',
        'action_verify_account' => 'メールアドレスを認証',
        'action_change_email' => 'メールアドレスを変更',
        'action_resend' => 'メールアドレスを認証',
        'manual_verification' => 'ボタンをクリックできない場合は、以下のURLをコピーしてブラウザに貼り付けてください:',
        'expiration' => 'この確認リンクは:minutes分後に期限切れになります。',
        'security_notice' => '【重要】このメールに心当たりがない場合は、このメールを無視してください。あなたが認証リンクをクリックしない限り、アカウントは有効化されません。第三者がこのメールアドレスを誤って登録した可能性がありますが、あなたの個人情報が漏洩することはありません。',
        'regards' => 'よろしくお願いいたします',
    ],
    
    // メンバー本人への認証完了通知
    'member_verification_completed' => [
        'subject' => 'アカウント認証が完了しました',
        'greeting' => ':nameさん、こんにちは！',
        'message' => 'あなたのメンバーアカウントの認証が完了しました。',
        'member_info' => '【メンバー情報】',
        'name' => '名前',
        'email' => 'メールアドレス',
        'login_info' => '以下のURLから管理画面にログインできます。',
        'url_info' => '【URL情報】',
        'front_url' => 'フロントページURL',
        'admin_url' => '管理画面URL',
        'thanks' => 'ご利用ありがとうございます。',
        'regards' => 'よろしくお願いいたします',
    ],
];
