<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
    // 共通マイページ翻訳キー
    //'title' => 'マイページ',
    'dashboard' => 'ダッシュボード',
    'welcome' => ':nameさん、こんにちは',
    'dashboard_description' => 'アカウント情報の確認や設定の変更ができます。',

    // プロフィール
    'profile' => 'プロフィール',
    'profile_description' => '個人情報やアカウント設定を管理します。',
    'edit_profile' => 'プロフィールを編集',

    // セキュリティ
    'security' => 'セキュリティ',
    'security_description' => 'パスワードや二段階認証の設定を管理します。',
    'two_fa_enabled' => '二段階認証: 有効',
    'two_fa_disabled' => '二段階認証: 無効',

    // アカウント情報
    'account_info' => 'アカウント情報',
    'email' => 'メールアドレス',
    'last_login' => '最終ログイン',

    // メール未認証
    'email_not_verified_title' => 'メールアドレスが認証されていません',
    'email_not_verified_description' => 'アカウントの全機能を利用するには、メールアドレスの認証が必要です。',

    // プロフィール編集
    'profile_edit' => 'プロフィール編集',
    'basic_info_description' => 'アカウントの基本情報を管理します。',
    'account_name' => 'アカウント名',
    'display_name' => '表示名',
    'last_name' => '姓',
    'first_name' => '名',
    'last_name_kana' => '姓（カナ）',
    'first_name_kana' => '名（カナ）',
    'zip_code' => '郵便番号',
    'pref' => '都道府県',
    'city' => '市区町村',
    'address' => '住所',
    'building' => '建物名・マンション名',
    'building_help' => 'マンションやアパート名、部屋番号などを入力してください。',
    'kana' => 'カタカナ',
    'phone' => '電話番号',
    'tel' => '電話番号',
    'tel_help_japanese' => '携帯電話：000-0000-0000、固定電話：00-0000-0000 または 000-000-0000',
    'gender' => '性別',
    'gender_male' => '男性',
    'gender_female' => '女性',
    'gender_other' => 'その他',
    'birth_date' => '生年月日',
    'save' => '保存',
    'profile_updated' => 'プロフィールを更新しました。',

    // パスワード変更
    'change_password' => 'パスワード変更',
    'password_settings' => 'パスワード設定',
    'password_settings_description' => 'アカウントのパスワードを変更します。',
    'current_password' => '現在のパスワード',
    'new_password' => '新しいパスワード',
    'confirm_password' => '新しいパスワード（確認）',
    'password_updated' => 'パスワードを変更しました。',
    
    // パスワードリセット
    'reset_password_title' => 'パスワードリセット',
    'reset_password_description' => 'パスワードをお忘れの場合は、登録されているメールアドレスにパスワードリセット用のリンクを送信します。',
    
    // 外観設定
    'appearance_settings' => '外観設定',
    'appearance_settings_description' => '表示テーマやインターフェースの設定を管理します。',
    'theme_mode' => 'テーマモード',
    'theme_light' => 'ライトモード',
    'theme_dark' => 'ダークモード',
    'theme_auto' => '自動（システム設定に従う）',
    'appearance_updated' => '外観設定を更新しました。',
    
    // メール通知設定
    'notification_settings' => 'メール通知設定',
    'notification_settings_description' => '受信するメール通知の種類を管理します。',
    'login_notification' => 'ログイン通知',
    'login_notification_description' => '新しいデバイスからログインがあった際に通知を受け取ります。',
    'notification_updated' => '通知設定を更新しました。',

    // 二段階認証設定
    'two_fa_settings' => '二段階認証設定',
    'two_fa_mode' => '二段階認証モード',
    'two_factor_mode_disabled' => '無効',
    'two_factor_mode_always' => '常に有効',
    'two_factor_mode_new_device' => '新しいデバイスのみ',
    'two_fa_method' => '認証方法',
    'two_factor_method_email' => 'メール認証',
    'two_factor_method_passkey' => 'パスキー',
    'two_factor_method_recovery' => '回復コード',

    // 信頼済みデバイス
    'trusted_devices' => '信頼済みデバイス',
    'trusted_devices_description' => '二段階認証をスキップするデバイスを管理します。',
    'no_trusted_devices' => '信頼済みデバイスはありません。',
    'remove_device' => '削除',
    'device_removed' => 'デバイスを削除しました。',

    // アカウント削除
    'delete_account' => 'アカウント削除',
    'delete_account_description' => 'アカウントを削除すると、すべてのデータが完全に削除されます。この操作は取り消せません。',
    'delete_account_confirm' => '本当にアカウントを削除しますか？',
    'account_deleted' => 'アカウントを削除しました。',

    // パスワードをお忘れの方
    'forgot_password' => [
        'title' => 'パスワードをお忘れの方',
        'heading' => 'パスワードをリセット',
        'description' => '登録済みのメールアドレスを入力してください。パスワードリセット用のリンクをお送りします。',
        'email' => 'メールアドレス',
        'submit' => 'リセットリンクを送信',
        'back_to_login' => 'ログイン画面に戻る',
    ],

    // パスワードリセット（新パスワード設定）
    'reset_password' => [
        'title' => 'パスワードリセット',
        'heading' => '新しいパスワードを設定',
        'email' => 'メールアドレス',
        'password' => '新しいパスワード',
        'password_confirmation' => '新しいパスワード（確認）',
        'submit' => 'パスワードを変更',
    ],

    // メール認証
    'verify_email' => [
        'title' => 'メール認証',
        'heading' => 'メールアドレスを認証してください',
        'description' => 'ご登録いただいたメールアドレスに認証リンクを送信しました。メールをご確認ください。',
        'link_sent' => '新しい認証リンクを送信しました。',
        'resend' => '認証メールを再送信',
        'logout' => 'ログアウト',
    ],

    // パスワード確認
    'confirm_password' => [
        'title' => 'パスワード確認',
        'heading' => 'パスワードを確認',
        'description' => 'セキュリティのため、続行する前にパスワードを入力してください。',
        'password' => 'パスワード',
        'submit' => '確認',
    ],

    // 二段階認証詳細設定（Myページ専用）
    'two_fa_authentication' => '二段階認証',
    'two_fa_description' => 'セキュリティを強化するため、ログイン時に追加の認証を設定できます。',
];
