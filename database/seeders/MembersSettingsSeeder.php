<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MemberSetting;

class MembersSettingsSeeder extends Seeder
{

    protected $table = 'member_settings';
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // パスワード条件の設定
            ['key' => 'password_min_length', 'value' => '8'], // デフォルト: 8文字
            ['key' => 'password_require_uppercase', 'value' => '1'], // デフォルト: 大文字を含める
            ['key' => 'password_require_symbol', 'value' => '0'], // デフォルト: 記号は含めない

            // ログイン通知設定
            ['key' => 'login_notification_mode', 'value' => '0'], // 0 = UseProfileSetting（プロファイルに任せる）

            // 二段階認証設定
            ['key' => 'force_2fa', 'value' => '0'], // 0 = UseProfileSetting（プロファイルに任せる）
        ];

        foreach ($settings as $setting) {
            DB::table($this->table)->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
