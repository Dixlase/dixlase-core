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
    'heading' => 'メンバー全体設定',
    'description' => 'メンバーのパスワード、セッション、認証に関する全体設定を管理します。',
    
    'nav' => [
        'password' => 'パスワード設定',
        'session' => 'セッション設定',
        'auth' => '認証設定',
    ],
    
    'password_min_length' => '最小文字数',
    'characters' => '文字',
    'requirements' => '必須条件',
    'uppercase' => '大文字',
    'number' => '数字',
    'symbol' => '記号',
    'no_requirements' => '追加条件なし',
    'session_lifetime' => 'セッション有効時間',
    'system_default' => 'システムデフォルト',
    'custom_session_enabled' => 'カスタム設定有効',
    'custom_session_disabled' => 'システムデフォルト使用',
    'two_factor' => '二段階認証',
    'optional' => '任意',
    'required' => '必須',
    'login_attempt_limit' => 'ログイン試行制限',
    'roles_description' => 'メンバーの権限とアクセス制御を設定します。',
    'force_logout_heading' => '強制ログアウト',
    'force_logout_description' => '全ての管理メンバーを強制的にログアウトします。現在ログイン中の全メンバーのセッションが削除されます。',
    'force_logout_all_button' => '全メンバー強制ログアウト',
    'force_logout_all_modal' => [
        'title' => '全メンバー強制ログアウト確認',
        'message' => '全てのメンバーを強制的にログアウトしますか？この操作により、現在ログイン中の全メンバーのセッションが削除されます。',
        'confirm_label' => '強制ログアウト実行',
    ],
    
    // メッセージ
    'updated' => 'メンバー設定を更新しました。',
    
    // 共通単位
    'minutes' => '分',
    'seconds' => '秒',
    'hours' => '時間',
    'times' => '回',
    'codes' => '個',
];
