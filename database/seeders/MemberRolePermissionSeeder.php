<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemberRolePermissionSeeder extends Seeder
{

    protected $table = 'member_role_permissions';
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table($this->table)->insert([
            // ダッシュボード
            [
                'menu_key' => 'dashboard',
                'access_roles' => '6,7,8,9',  // CONTRIBUTOR以上編集可
                'view_roles'  => '5,1',       // RECEPTIONIST, GUEST閲覧可
            ],

            // フロントページ管理
            [
                'menu_key' => 'front.index',
                'access_roles' => '6,7,8,9',
                'view_roles'  => '5,1',
            ],
            [
                'menu_key' => 'front.design',
                'access_roles' => '7,8,9',    // AUTHOR以上
                'view_roles'  => '6,5,1',
            ],
            [
                'menu_key' => 'front.settings',
                'access_roles' => '8,9',      // EDITOR以上
                'view_roles'  => '7,6,5,1',
            ],

            // メディア管理
            [
                'menu_key' => 'media.index',
                'access_roles' => '6,7,8,9',
                'view_roles'  => '5,1',
            ],
            [
                'menu_key' => 'media.upload',
                'access_roles' => '7,8,9',
                'view_roles'  => '6,5,1',
            ],
            [
                'menu_key' => 'media.settings',
                'access_roles' => '9',        // ADMINのみ
                'view_roles'  => '8,7,6,5,1',
            ],

            // 全体設定
            [
                'menu_key' => 'settings.base',
                'access_roles' => '9',
                'view_roles'  => '',
            ],
            [
                'menu_key' => 'settings.security',
                'access_roles' => '9',
                'view_roles'  => '',
            ],

            // メンバー管理
            [
                'menu_key' => 'settings.members.index',
                'access_roles' => '9',
                'view_roles'  => '8',
            ],
            [
                'menu_key' => 'settings.members.create',
                'access_roles' => '9',
                'view_roles'  => '',
            ],
            [
                'menu_key' => 'settings.members.profile',
                'access_roles' => '6,7,8,9',
                'view_roles'  => '5,1',
            ],
            [
                'menu_key' => 'settings.members.roles',
                'access_roles' => '9',
                'view_roles'  => '',
            ],
            [
                'menu_key' => 'settings.members.settings',
                'access_roles' => '9',
                'view_roles'  => '',
            ],

            // テーマ設定
            [
                'menu_key' => 'settings.themes.index',
                'access_roles' => '9',
                'view_roles'  => '',
            ],
            [
                'menu_key' => 'settings.themes.install',
                'access_roles' => '9',
                'view_roles'  => '',
            ],

            // プラグイン設定
            [
                'menu_key' => 'settings.plugins.index',
                'access_roles' => '9',
                'view_roles'  => '',
            ],
            [
                'menu_key' => 'settings.plugins.install',
                'access_roles' => '9',
                'view_roles'  => '',
            ],

            // システム
            [
                'menu_key' => 'settings.systems.logs',
                'access_roles' => '9',
                'view_roles'  => '',
            ],
            [
                'menu_key' => 'settings.systems.info',
                'access_roles' => '9',
                'view_roles'  => '8',
            ],
        ]);
    }
}
