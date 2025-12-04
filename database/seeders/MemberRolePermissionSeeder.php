<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\MemberRole;

/**
 * メンバー権限設定のシーダー
 * 
 * access_roles: 編集権限 - この値以上の権限を持つユーザーが編集可能
 * view_roles: 閲覧権限 - この値以上の権限を持つユーザーが閲覧可能
 * 
 * 権限値:
 * - SUPER_ADMIN = 10 (特権管理者専用)
 * - ADMIN = 9 (管理者以上)
 * - EDITOR = 8 (編集者以上)
 * - AUTHOR = 7 (投稿者以上)
 * - CONTRIBUTOR = 6 (寄稿者以上)
 * - RECEPTIONIST = 5 (受付以上)
 * - GUEST = 1 (全員)
 */
class MemberRolePermissionSeeder extends Seeder
{

    protected $table = 'members_role_permissions';
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table($this->table)->insert([
            // ダッシュボード
            [
                'menu_key' => 'dashboard',
                'access_roles' => MemberRole::CONTRIBUTOR->value,  // 寄稿者以上が編集可能
                'view_roles'  => MemberRole::GUEST->value,         // 全員が閲覧可能
            ],

            // フロントページ管理
            [
                'menu_key' => 'front.index',
                'access_roles' => MemberRole::EDITOR->value,       // 編集者以上
                'view_roles'  => MemberRole::EDITOR->value,
            ],
            [
                'menu_key' => 'front.design',
                'access_roles' => MemberRole::EDITOR->value,       // 編集者以上
                'view_roles'  => MemberRole::EDITOR->value,
            ],
            [
                'menu_key' => 'front.settings',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],

            // メディア管理
            [
                'menu_key' => 'media.index',
                'access_roles' => MemberRole::CONTRIBUTOR->value,  // 寄稿者以上
                'view_roles'  => MemberRole::CONTRIBUTOR->value,
            ],
            [
                'menu_key' => 'media.upload',
                'access_roles' => MemberRole::CONTRIBUTOR->value,  // 寄稿者以上
                'view_roles'  => MemberRole::CONTRIBUTOR->value,
            ],
            [
                'menu_key' => 'media.settings',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],

            // 全体設定
            [
                'menu_key' => 'settings.base',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],
            [
                'menu_key' => 'settings.security',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],

            // メンバー管理
            [
                'menu_key' => 'settings.members.index',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],
            [
                'menu_key' => 'settings.members.create',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],
            [
                'menu_key' => 'settings.members.roles',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],
            [
                'menu_key' => 'settings.members.settings',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],

            // テーマ管理（コア機能）
            [
                'menu_key' => 'settings.themes.index',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],
            [
                'menu_key' => 'settings.themes.install',
                'access_roles' => MemberRole::SUPER_ADMIN->value,  // 特権管理者専用
                'view_roles'  => MemberRole::SUPER_ADMIN->value,
            ],

            // プラグイン設定
            [
                'menu_key' => 'settings.plugins.index',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],
            [
                'menu_key' => 'settings.plugins.install',
                'access_roles' => MemberRole::SUPER_ADMIN->value,  // 特権管理者専用
                'view_roles'  => MemberRole::SUPER_ADMIN->value,
            ],

            // システム
            [
                'menu_key' => 'settings.systems.logs',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],
            [
                'menu_key' => 'settings.systems.info',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],
            [
                'menu_key' => 'settings.systems.cache',
                'access_roles' => MemberRole::ADMIN->value,        // 管理者以上
                'view_roles'  => MemberRole::ADMIN->value,
            ],
            [
                'menu_key' => 'settings.systems.database',
                'access_roles' => MemberRole::SUPER_ADMIN->value,  // 特権管理者専用
                'view_roles'  => MemberRole::SUPER_ADMIN->value,
            ],
        ]);
    }
}
