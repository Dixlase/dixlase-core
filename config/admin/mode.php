<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

use App\Enums\MenuVisibility;

/*
|--------------------------------------------------------------------------
| 管理画面モード設定
|--------------------------------------------------------------------------
|
| かんたんモード（Simple）と詳細モード（Advanced）で
| 各メニューの表示・操作レベルを定義します。
|
| MenuVisibility:
|   Full (0)      = すべて表示、使えるようにする
|   Partial (1)   = 一部の機能のみ表示、非表示の部分は自動設定
|   Hidden (2)    = メニュー丸ごと非表示、自動設定もしくは使えない
|   ReadOnly (3)  = 表示するが「状態表示のみ（読み取り専用）」
|   GuideOnly (4) = 表示するが「導線のみ（設定は別ページ or モード切替へ誘導）」
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | かんたんモードのデフォルトメニュー表示設定
    |--------------------------------------------------------------------------
    |
    | キーはナビゲーション設定のキーに対応します。
    | ネストされた子項目は「親キー.子キー」のドット記法で指定します。
    |
    | 詳細モードではすべてのメニューが Full (0) で表示されます。
    |
    */

    'simple_defaults' => [

        // ダッシュボード - 常に表示
        'dashboard' => MenuVisibility::Full,

        // フロントページ管理
        'front' => MenuVisibility::Full,

        // メディア管理
        'media' => MenuVisibility::Partial,
        'media.index' => MenuVisibility::Full,
        'media.upload' => MenuVisibility::Full,
        'media.settings' => MenuVisibility::Partial,

        // プロフィール設定
        'profile' => MenuVisibility::Full,

        // メンバー管理
        'members' => MenuVisibility::Partial,
        'members.index' => MenuVisibility::Full,
        'members.create_edit' => MenuVisibility::Full,
        'members.roles' => MenuVisibility::Hidden,

        // 全体設定
        'settings' => MenuVisibility::Partial,

        // 全体設定 > 基本設定
        'settings.base' => MenuVisibility::Partial,
        'settings.base.index' => MenuVisibility::Full,
        'settings.base.site' => MenuVisibility::Full,
        'settings.base.admin' => MenuVisibility::Hidden,
        'settings.base.mail' => MenuVisibility::Full,
        'settings.base.maintenance' => MenuVisibility::Full,
        'settings.base.mode' => MenuVisibility::Full,

        // 全体設定 > セキュリティ設定
        'settings.security' => MenuVisibility::Partial,
        'settings.security.index' => MenuVisibility::Full,
        'settings.security.password' => MenuVisibility::Hidden,
        'settings.security.login' => MenuVisibility::Partial,
        'settings.security.two-fa' => MenuVisibility::Partial,
        'settings.security.notifications' => MenuVisibility::Hidden,
        'settings.security.captcha' => MenuVisibility::Full,
        'settings.security.session' => MenuVisibility::Hidden,
        'settings.security.csp' => MenuVisibility::Hidden,
        'settings.security.extensions' => MenuVisibility::Hidden,
        'settings.security.ip' => MenuVisibility::Hidden,
        'settings.security.integrity' => MenuVisibility::Hidden,
        'settings.security.environment' => MenuVisibility::Hidden,

        // 全体設定 > テーマ管理
        'settings.themes' => MenuVisibility::Full,

        // 全体設定 > プラグイン管理
        'settings.plugins' => MenuVisibility::Full,

        // 全体設定 > システム
        'settings.systems' => MenuVisibility::Partial,
        'settings.systems.cache' => MenuVisibility::Full,
        'settings.systems.database' => MenuVisibility::Hidden,
        'settings.systems.api' => MenuVisibility::Hidden,
        'settings.systems.logs' => MenuVisibility::Full,
        'settings.systems.logs.files' => MenuVisibility::Hidden,
        'settings.systems.info' => MenuVisibility::Hidden,
    ],

    /*
    |--------------------------------------------------------------------------
    | メニュー項目のメタ情報
    |--------------------------------------------------------------------------
    |
    | 各メニュー項目の表示名（翻訳キー）、アイコン、
    | 変更可能な表示レベルの選択肢を定義します。
    |
    */

    'menu_items' => [
        'dashboard' => [
            'text_key' => 'admin/navigation.dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'allowed_visibilities' => [MenuVisibility::Full],
            'locked' => true,
        ],
        'front' => [
            'text_key' => 'admin/navigation.front.text',
            'icon' => 'fas fa-desktop',
            'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden],
        ],
        'media' => [
            'text_key' => 'admin/navigation.media.text',
            'icon' => 'fas fa-photo-video',
            'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden],
        ],
        'profile' => [
            'text_key' => 'admin/navigation.profile.text',
            'icon' => 'fas fa-id-badge',
            'allowed_visibilities' => [MenuVisibility::Full],
            'locked' => true,
        ],
        'members' => [
            'text_key' => 'admin/navigation.settings.members.text',
            'icon' => 'fas fa-users-cog',
            'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden, MenuVisibility::ReadOnly],
        ],
        'settings' => [
            'text_key' => 'admin/navigation.settings.text',
            'icon' => 'fas fa-cogs',
            'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial],
            'children' => [
                'base' => [
                    'text_key' => 'admin/navigation.settings.base.text',
                    'icon' => 'fas fa-gear',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial],
                ],
                'security' => [
                    'text_key' => 'admin/navigation.settings.security.text',
                    'icon' => 'fas fa-shield-alt',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden, MenuVisibility::ReadOnly, MenuVisibility::GuideOnly],
                ],
                'themes' => [
                    'text_key' => 'admin/navigation.settings.themes.text',
                    'icon' => 'fas fa-palette',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden],
                ],
                'plugins' => [
                    'text_key' => 'admin/navigation.settings.plugins.text',
                    'icon' => 'fas fa-puzzle-piece',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Partial, MenuVisibility::Hidden],
                ],
                'systems' => [
                    'text_key' => 'admin/navigation.settings.systems.text',
                    'icon' => 'fas fa-server',
                    'allowed_visibilities' => [MenuVisibility::Full, MenuVisibility::Hidden, MenuVisibility::ReadOnly, MenuVisibility::GuideOnly],
                ],
            ],
        ],
    ],

];
