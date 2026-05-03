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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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
    // ダッシュボード
    'dashboard.view' => 'ダッシュボード閲覧',

    // メンバー管理
    'members.view' => 'メンバー閲覧',
    'members.create' => 'メンバー作成',
    'members.update' => 'メンバー編集',
    'members.delete' => 'メンバー削除',
    'members.manage_roles' => '権限管理',

    // 設定
    'settings.view' => '設定閲覧',
    'settings.base' => '基本設定',
    'settings.security' => 'セキュリティ設定',
    'settings.members' => 'メンバー設定',
    'settings.system' => 'システム設定',
    'settings.api' => 'API設定',

    // プラグイン
    'plugins.view' => 'プラグイン閲覧',
    'plugins.install' => 'プラグインインストール',
    'plugins.uninstall' => 'プラグインアンインストール',
    'plugins.enable' => 'プラグイン有効化',
    'plugins.disable' => 'プラグイン無効化',
    'plugins.settings' => 'プラグイン設定',

    // テーマ
    'themes.view' => 'テーマ閲覧',
    'themes.install' => 'テーマインストール',
    'themes.uninstall' => 'テーマアンインストール',
    'themes.enable' => 'テーマ有効化',
    'themes.disable' => 'テーマ無効化',
    'themes.settings' => 'テーマ設定',

    // メディア
    'media.view' => 'メディア閲覧',
    'media.upload' => 'メディアアップロード',
    'media.delete' => 'メディア削除',

    // 監査ログ
    'audit_logs.view' => '監査ログ閲覧',
    'audit_logs.export' => '監査ログエクスポート',

    // システム
    'system.logs_view' => 'システムログ閲覧',
    'system.logs_delete' => 'システムログ削除',
    'system.cache_clear' => 'キャッシュクリア',
    'system.maintenance' => 'メンテナンスモード',
    'system.backup' => 'バックアップ',
    'system.restore' => 'リストア',

    // API
    'api.keys_view' => 'APIキー閲覧',
    'api.keys_create' => 'APIキー作成',
    'api.keys_delete' => 'APIキー削除',

    // Webhook
    'webhooks.view' => 'Webhook閲覧',
    'webhooks.create' => 'Webhook作成',
    'webhooks.update' => 'Webhook編集',
    'webhooks.delete' => 'Webhook削除',

    // リソース名
    'resources' => [
        'dashboard' => 'ダッシュボード',
        'members' => 'メンバー',
        'settings' => '設定',
        'plugins' => 'プラグイン',
        'themes' => 'テーマ',
        'media' => 'メディア',
        'audit_logs' => '監査ログ',
        'system' => 'システム',
        'api' => 'API',
        'webhooks' => 'Webhook',
    ],

    // アクション名
    'actions' => [
        'view' => '閲覧',
        'create' => '作成',
        'update' => '編集',
        'delete' => '削除',
        'install' => 'インストール',
        'uninstall' => 'アンインストール',
        'enable' => '有効化',
        'disable' => '無効化',
        'settings' => '設定',
        'upload' => 'アップロード',
        'export' => 'エクスポート',
        'manage_roles' => '権限管理',
        'logs_view' => 'ログ閲覧',
        'logs_delete' => 'ログ削除',
        'cache_clear' => 'キャッシュクリア',
        'maintenance' => 'メンテナンス',
        'backup' => 'バックアップ',
        'restore' => 'リストア',
        'keys_view' => 'キー閲覧',
        'keys_create' => 'キー作成',
        'keys_delete' => 'キー削除',
    ],
];
