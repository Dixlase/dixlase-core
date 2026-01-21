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
    |
    | config/admin.php の nav 構造と同じ階層構造で定義
    | 権限設定画面でアコーディオン形式で表示するため
    |
    */
    'permissions' => [
        // ダッシュボード
        'dashboard' => [
            'access_roles' => MemberRole::CONTRIBUTOR->value,
            'view_roles' => MemberRole::GUEST->value,
        ],

        // フロントページ管理
        'front' => [
            'children' => [
                'index' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'edit' => [
                    'access_roles' => MemberRole::EDITOR->value,
                    'view_roles' => MemberRole::EDITOR->value,
                ],
                'settings' => [
                    'access_roles' => MemberRole::ADMIN->value,
                    'view_roles' => MemberRole::ADMIN->value,
                ],
            ],
        ],

        // メディア管理
        'media' => [
            'children' => [
                'index' => [
                    'access_roles' => MemberRole::CONTRIBUTOR->value,
                    'view_roles' => MemberRole::CONTRIBUTOR->value,
                ],
                'upload' => [
                    'access_roles' => MemberRole::CONTRIBUTOR->value,
                    'view_roles' => MemberRole::CONTRIBUTOR->value,
                ],
                'settings' => [
                    'access_roles' => MemberRole::SUPER_ADMIN->value,
                    'view_roles' => MemberRole::SUPER_ADMIN->value,
                ],
            ],
        ],

        // プロフィール（profile）は権限設定から除外
        // 自分自身の設定なので全員が読み書き可能（AdminHelperで固定）

        // メンバー管理
        'members' => [
            'children' => [
                'index' => [
                    'access_roles' => MemberRole::ADMIN->value,
                    'view_roles' => MemberRole::ADMIN->value,
                ],
                'create' => [
                    'access_roles' => MemberRole::ADMIN->value,
                    'view_roles' => MemberRole::ADMIN->value,
                ],
                'settings' => [
                    'children' => [
                        'overview' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'password' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'session' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'auth' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'roles' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                    ],
                ],
            ],
        ],

        // 全体設定
        'settings' => [
            'children' => [
                // 基本設定
                'base' => [
                    'children' => [
                        'index' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'site' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'admin' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'mail' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'maintenance' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                    ],
                ],

                // セキュリティ設定
                'security' => [
                    'children' => [
                        'index' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'password' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'session' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'captcha' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'ip' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'extensions' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'csp' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'notifications' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'environment' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'integrity' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                    ],
                ],

                // テーマ管理
                'themes' => [
                    'children' => [
                        'index' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'add' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                    ],
                ],

                // プラグイン管理
                'plugins' => [
                    'children' => [
                        'index' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'add' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                    ],
                ],

                // システム管理
                'systems' => [
                    'children' => [
                        'cache' => [
                            'access_roles' => MemberRole::ADMIN->value,
                            'view_roles' => MemberRole::ADMIN->value,
                        ],
                        'database' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'api' => [
                            'access_roles' => MemberRole::SUPER_ADMIN->value,
                            'view_roles' => MemberRole::SUPER_ADMIN->value,
                        ],
                        'logs' => [
                            'children' => [
                                'audit' => [
                                    'access_roles' => MemberRole::ADMIN->value,
                                    'view_roles' => MemberRole::ADMIN->value,
                                ],
                                'files' => [
                                    'access_roles' => MemberRole::ADMIN->value,
                                    'view_roles' => MemberRole::ADMIN->value,
                                ],
                            ],
                        ],
                        'info' => [
                            'access_roles' => MemberRole::ADMIN->value,
                            'view_roles' => MemberRole::ADMIN->value,
                        ],
                    ],
                ],
            ],
        ],
    ],
];
