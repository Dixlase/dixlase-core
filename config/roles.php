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

use App\Enums\MemberRole;

/**
 * コア機能のデフォルト権限設定
 * 
 * 各メニュー/機能に対するデフォルトの権限を定義します。
 * 管理画面で変更された場合のみ、role_permission_overrides テーブルに差分が保存されます。
 * 
 * access_roles: 編集権限（write）- この値以上の権限を持つユーザーが編集可能
 * view_roles: 閲覧権限（read）- この値以上の権限を持つユーザーが閲覧可能
 * 
 * 権限値（MemberRole enum）:
 * - SUPER_ADMIN = 10 (特権管理者専用)
 * - ADMIN = 9 (管理者以上)
 * - EDITOR = 8 (編集者以上)
 * - AUTHOR = 7 (投稿者以上)
 * - CONTRIBUTOR = 6 (寄稿者以上)
 * - RECEPTIONIST = 5 (受付以上)
 * - GUEST = 1 (全員)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | コア機能のデフォルト権限
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        // ダッシュボード
        'dashboard' => [
            'access_roles' => MemberRole::CONTRIBUTOR->value,
            'view_roles' => MemberRole::GUEST->value,
        ],

        // フロントページ管理
        'front.index' => [
            'access_roles' => MemberRole::EDITOR->value,
            'view_roles' => MemberRole::EDITOR->value,
        ],
        'front.edit' => [
            'access_roles' => MemberRole::EDITOR->value,
            'view_roles' => MemberRole::EDITOR->value,
        ],
        'front.settings' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],

        // メディア管理
        'media.index' => [
            'access_roles' => MemberRole::CONTRIBUTOR->value,
            'view_roles' => MemberRole::CONTRIBUTOR->value,
        ],
        'media.upload' => [
            'access_roles' => MemberRole::CONTRIBUTOR->value,
            'view_roles' => MemberRole::CONTRIBUTOR->value,
        ],
        'media.settings' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],

        // プロフィール（自分自身の設定なので全員アクセス可能）
        'profile.index' => [
            'access_roles' => MemberRole::GUEST->value,
            'view_roles' => MemberRole::GUEST->value,
        ],
        'profile.basic' => [
            'access_roles' => MemberRole::GUEST->value,
            'view_roles' => MemberRole::GUEST->value,
        ],
        'profile.password' => [
            'access_roles' => MemberRole::GUEST->value,
            'view_roles' => MemberRole::GUEST->value,
        ],
        'profile.appearance' => [
            'access_roles' => MemberRole::GUEST->value,
            'view_roles' => MemberRole::GUEST->value,
        ],
        'profile.notifications' => [
            'access_roles' => MemberRole::GUEST->value,
            'view_roles' => MemberRole::GUEST->value,
        ],
        'profile.two_factor' => [
            'access_roles' => MemberRole::GUEST->value,
            'view_roles' => MemberRole::GUEST->value,
        ],
        'profile.two_factor_management' => [
            'access_roles' => MemberRole::GUEST->value,
            'view_roles' => MemberRole::GUEST->value,
        ],

        // メンバー管理
        'members.index' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
        'members.create' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
        'members.settings.overview' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'members.settings.password' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'members.settings.session' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'members.settings.auth' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'members.settings.roles' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],

        // 全体設定 - 基本設定
        'settings.base.index' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.base.site' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.base.admin' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.base.mail' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.base.maintenance' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],

        // 全体設定 - セキュリティ設定
        'settings.security.index' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.security.password' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.security.session' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.security.captcha' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.security.ip' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.security.extensions' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.security.csp' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.security.notifications' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.security.environment' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.security.integrity' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],

        // 全体設定 - テーマ管理
        'settings.themes.index' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.themes.add' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],

        // 全体設定 - プラグイン管理
        'settings.plugins.index' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.plugins.add' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],

        // 全体設定 - システム管理
        'settings.systems.cache' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
        'settings.systems.database' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.systems.api' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        'settings.systems.logs.audit' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
        'settings.systems.logs.files' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
        'settings.systems.info' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
    ],
];
