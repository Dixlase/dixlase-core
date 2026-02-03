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
    'settings_title' => '基本設定',
    'settings_header' => 'インストール設定',
    'settings_description' => 'ソフトウェアの基本設定を行います。',
    
    // セマンティック見出し
    'site_information' => 'サイト基本情報',
    'admin_account_information' => '管理者アカウント情報',
    'admin_account_details' => '管理者アカウントの詳細',
    'password_settings' => 'パスワード設定',
    'password_setup' => 'パスワードの設定',
    
    // サイト情報
    'site_name' => 'サイト名',
    'admin_email' => '管理者メールアドレス',
    
    // 管理者アカウント
    'admin_account_name' => 'アカウント名',
    'admin_account_name_placeholder' => '半角英数字で入力（例: siteadmin2025）',
    'admin_account_name_requirements' => '3〜20文字の半角英数字を使用してください。<br>本番環境では安易に推測できる名前（admin、administrator、root、user、test、demo、dixlase、manager、webmasterなど）は避けてください。',
    'admin_display_name' => '表示名',
    'admin_display_name_placeholder' => '管理バーに表示される名前（例: 山田 太郎）',
    'admin_display_name_requirements' => '管理バーやプロフィールに表示される名前です。空欄の場合はアカウント名が表示されます。',
    'admin_password' => '管理者パスワード',
    'admin_password_confirmation' => '管理者パスワード確認',
    'admin_password_confirmation_note' => '確認のため、同じパスワードを手入力してください。',
    
    // バリデーション
    'validation' => [
        'admin_account_name_required' => 'アカウント名を入力してください。',
        'admin_account_name_alpha_num' => 'アカウント名は半角英数字のみ使用できます。',
        'admin_account_name_length' => 'アカウント名は3〜20文字で入力してください。',
    ],
    
    // パスワード要件
    'password_requirements' => [
        'length' => '8文字以上',
        'uppercase' => '大文字を1文字以上含む',
        'lowercase' => '小文字を1文字以上含む',
        'number' => '数字を1文字以上含む',
        'symbol' => '記号（!@#$%^&* など）を含むと強度UP',
    ],
    'password_strength_messages' => [
        'weak' => '❌ 条件を満たしていません',
        'medium' => '⚠️ 普通',
        'strong' => '✅ 強い',
    ],
    'password_strength_error' => 'パスワードは8文字以上で、大文字・小文字・数字を最低1つ含める必要があります。',
    'password_strength_weak' => 'パスワードが弱すぎます。',
    'password_strength_medium' => 'パスワードの強度は普通です。',
    'password_strength_strong' => 'パスワードは安全です。',
];
