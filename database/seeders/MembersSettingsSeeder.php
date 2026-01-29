<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MemberSetting;

class MembersSettingsSeeder extends Seeder
{

    protected $table = 'members_settings';
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // ログイン通知設定
            ['key' => 'login_notification_mode', 'value' => '3'], // 0=無効, 1=異なるデバイス, 2=常に有効, 3=プロフィール設定に従う
            ['key' => 'login_notification_send_to_system', 'value' => '0'], // デフォルト: システム通知無効
            ['key' => 'login_notification_system_email', 'value' => ''], // デフォルト: 空（管理者メールアドレス）

            // 二段階認証設定（基本設定のみ）
            ['key' => 'two_fa_mode', 'value' => '3'], // 0=無効, 1=異なるデバイス, 2=常に有効, 3=プロフィール設定に従う
            ['key' => 'two_fa_default_method', 'value' => '0'], // デフォルトの認証方法はメール認証
            ['key' => 'two_fa_passkey_mode', 'value' => '2'], // 0=無効, 1=有効, 2=プロフィール設定に従う（デフォルト: プロフィール設定に従う）
            ['key' => 'two_fa_passkey_max_devices', 'value' => '3'], // Passkey最大登録数（1-5）

            // CAPTCHA設定（管理画面ログイン用）
            ['key' => 'captcha_admin_login_enabled', 'value' => '0'], // デフォルト: 無効
            ['key' => 'captcha_password_reset_enabled', 'value' => '0'], // デフォルト: 無効

        ];

        foreach ($settings as $setting) {
            DB::table($this->table)->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
