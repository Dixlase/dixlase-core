<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                'access_roles' => '6,7,8,9',
                'view_roles'  => '5,1',
            ],

            // フロントページ管理
            [
                'menu_key' => 'front.index',
                'access_roles' => '9,8',
                'view_roles'  => '9,8',
            ],
            [
                'menu_key' => 'front.design',
                'access_roles' => '9,8',
                'view_roles'  => '9,8',
            ],
            [
                'menu_key' => 'front.settings',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],

            // メディア管理
            [
                'menu_key' => 'media.index',
                'access_roles' => '9,8,7,6',
                'view_roles'  => '9,8,7,6',
            ],
            [
                'menu_key' => 'media.upload',
                'access_roles' => '9,8,7,6',
                'view_roles'  => '9,8,7,6',
            ],
            [
                'menu_key' => 'media.settings',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],

            // 全体設定
            [
                'menu_key' => 'settings.base',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
            [
                'menu_key' => 'settings.security',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],

            // メンバー管理
            [
                'menu_key' => 'settings.members.index',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
            [
                'menu_key' => 'settings.members.create',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
            [
                'menu_key' => 'settings.members.roles',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
            [
                'menu_key' => 'settings.members.settings',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],

            // テーマ管理（コア機能）
            [
                'menu_key' => 'settings.themes.index',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
            [
                'menu_key' => 'settings.themes.install',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],

            // プラグイン設定
            [
                'menu_key' => 'settings.plugins.index',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
            [
                'menu_key' => 'settings.plugins.install',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],

            // システム
            [
                'menu_key' => 'settings.systems.logs',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
            [
                'menu_key' => 'settings.systems.info',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
            [
                'menu_key' => 'settings.systems.cache',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
            [
                'menu_key' => 'settings.systems.database',
                'access_roles' => '9',
                'view_roles'  => '9',
            ],
        ]);
    }
}
