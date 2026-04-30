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
    'edit_menu' => 'メニュー編集',
    'done_editing' => '完了',
    'reset_menu' => 'リセット',
    'reset_confirm_title' => 'メニューをリセット',
    'reset_confirm_message' => '表示/非表示と並び順の設定をすべてリセットします。この操作は取り消せません。',
    'reset_confirm_button' => 'リセットする',
    'reset_cancel_button' => 'キャンセル',

    'dashboard' => 'ダッシュボード',
    'front' => [
        'text' => 'フロントページ管理',
        'index' => 'フロントページマスター',
        'edit' => 'フロントページ編集',
        'settings' => 'フロントページ設定',
    ],
    'media' => [
        'text' => 'メディア管理',
        'index' => 'メディアマスター',
        'upload' => 'メディアアップロード',
        'settings' => 'メディア設定',
    ],
    'profile' => [
        'text' => 'プロフィール設定',
        'index' => '概要',
        'basic' => '基本情報',
        'password' => 'パスワード設定',
        'appearance' => '外観設定',
        'notifications' => '通知設定',
        'two_fa' => '二段階認証',
        'two_fa_management' => 'パスキー・コード',
    ],
    'settings' => [
        'text' => '全体設定',
        'base' => [
            'text' => '基本設定',
            'index' => '概要',
            'site' => 'サイト設定',
            'admin' => '管理画面設定',
            'mail' => 'メール設定',
            'maintenance' => 'メンテナンス設定',
            'mode' => 'モード設定',
            'content' => 'コンテンツ設定',
            'editor' => 'GUIエディター設定',
        ],
        'security' => [
            'text' => 'セキュリティ設定',
            'index' => '概要',
            'password' => 'パスワード',
            'login_attempt' => 'ログイン',
            'session' => 'セッション',
            'two-fa' => '二段階認証',
            'captcha' => 'CAPTCHA',
            'ip' => 'IPアクセス制御',
            'extensions' => '拡張機能',
            'csp' => 'CSP',
            'notifications' => 'エラー通知',
            'environment' => '環境設定',
            'integrity' => 'ファイル整合性',
        ],
        'members' => [
            'text' => 'メンバー管理',
            'index' => 'メンバーマスター',
            'create' => 'メンバー新規作成',
            'profile' => 'プロフィール設定',
            'roles' => 'メンバー権限設定',
            'roles_short' => '権限設定',
            'settings' => 'メンバー全体設定',
            'overview' => '概要',
            'settings_nav' => [
                'password' => 'パスワード設定',
                'session' => 'セッション設定',
                'auth' => '認証設定',
            ],
        ],
        'themes' => [
            'text' => 'テーマ管理',
            'index' => 'テーママスター',
            'index_page' => [
                'heading' => 'テーママスター',
                'installed_heading' => 'インストール済みテーマ',
                'uninstalled_heading' => 'アンインストール済みテーマ',
                'table' => [
                    'caption' => 'テーマ一覧',
                    'id' => 'ID',
                    'name' => 'テーマ名',
                ],
                'uninstalled_table' => [
                    'caption' => 'アンインストール済みテーマ一覧',
                ],
                'no_themes' => 'テーマがありません',
                'uninstall' => [
                    'confirm_title' => 'テーマのアンインストール',
                    'confirm_message' => '「{name}」をアンインストールしますか？',
                ],
                'add' => [
                    'confirm_title' => 'テーマの追加',
                    'confirm_message' => '「{name}」を追加しますか？',
                ],
                'delete' => [
                    'confirm_title' => 'テーマの削除',
                    'confirm_message' => '「{name}」のファイルとフォルダを完全に削除しますか？この操作は取り消せません。',
                ],
            ],
            'add' => '追加',
            'settings' => 'テーマ設定',
        ],
        'plugins' => [
            'text' => 'プラグイン管理',
            'index' => 'プラグインマスター',
            'add' => '追加',
            'index_page' => [
                'heading' => 'プラグインマスター',
                'installed_heading' => 'インストール済みプラグイン',
                'uninstalled_heading' => 'アンインストール済みプラグイン',
                'table' => [
                    'caption' => 'プラグイン一覧',
                    'id' => 'ID',
                    'name' => 'プラグイン名',
                ],
                'uninstalled_table' => [
                    'caption' => 'アンインストール済みプラグイン一覧',
                ],
                'no_plugins' => 'プラグインがありません',
                'uninstall' => [
                    'confirm_title' => 'プラグインのアンインストール',
                    'confirm_message' => '「{name}」をアンインストールしますか？',
                    'remove_data_checkbox' => 'データベースのデータも削除する',
                ],
                'install' => [
                    'confirm_title' => 'プラグインのインストール',
                    'confirm_message' => '「{name}」をインストールしますか？',
                ],
                'delete' => [
                    'confirm_title' => 'プラグインの削除',
                    'confirm_message' => '「{name}」のファイルとフォルダを完全に削除しますか？この操作は取り消せません。',
                ],
            ],
            'install' => 'インストール',
        ],
        'systems' => [
            'text' => 'システム',
            'api' => 'API管理',
            'cache' => 'キャッシュ管理',
            'database' => 'データベース管理',
            'backup' => [
                'text' => 'バックアップ',
                'index' => 'バックアップ一覧',
                'restores' => '復元履歴',
                'settings' => 'バックアップ設定',
            ],
            'logs' => [
                'text' => 'ログ管理',
                'audit' => '監査ログ',
                'files' => 'ファイルログ',
            ],
            'info' => 'システム情報',
        ],
    ],
];
