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
    // 件名
    'subject_installed' => '【:app_name】:type「:name」がインストールされました',
    'subject_uninstalled' => '【:app_name】:type「:name」がアンインストールされました',
    'subject_enabled' => '【:app_name】:type「:name」が有効化されました',
    'subject_disabled' => '【:app_name】:type「:name」が無効化されました',
    'subject_unhealthy_warning' => '【:app_name 警告】健全性に注意が必要な:typeが操作されました',
    
    // タイプ
    'type_plugin' => 'プラグイン',
    'type_theme' => 'テーマ',
    
    // 本文
    'greeting' => 'システム管理者様',
    'message_installed' => ':type「:name」がインストールされました。',
    'message_uninstalled' => ':type「:name」がアンインストールされました。',
    'message_enabled' => ':type「:name」が有効化されました。',
    'message_disabled' => ':type「:name」が無効化されました。',
    'message_unhealthy_warning' => '健全性が「良好」以外の:typeが操作されました。内容をご確認ください。',
    
    // 詳細
    'details_title' => '操作詳細',
    'extension_name' => '拡張機能名',
    'extension_type' => '種類',
    'operation' => '操作',
    'operation_installed' => 'インストール',
    'operation_uninstalled' => 'アンインストール',
    'operation_enabled' => '有効化',
    'operation_disabled' => '無効化',
    'operated_by' => '操作者',
    'operated_at' => '操作日時',
    'health_status' => '健全性',
    'health_healthy' => '良好',
    'health_warning' => '注意',
    'health_needs_attention' => '要確認',
    'health_not_verified' => '未確認',
    'version' => 'バージョン',
    
    // 警告メッセージ
    'unhealthy_notice' => 'この拡張機能は健全性が「:level」です。使用する機能や権限について確認することをお勧めします。',
    
    // フッター
    'regards' => 'よろしくお願いいたします。',
    'auto_notification' => 'この通知はセキュリティ設定に基づいて自動送信されています。',
];
