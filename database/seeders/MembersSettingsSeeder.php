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
            // パスワード条件の設定
            ['key' => 'password_min_length', 'value' => '8'], // デフォルト: 8文字
            ['key' => 'password_require_uppercase', 'value' => '1'], // デフォルト: 大文字を含める
            ['key' => 'password_require_number', 'value' => '1'], // デフォルト: 数字を含める
            ['key' => 'password_require_symbol', 'value' => '1'], // デフォルト: 記号を含める

            // パスワードリセット機能設定
            ['key' => 'password_reset_enabled', 'value' => '0'], // デフォルト: 無効

            // パスワード辞書攻撃対策設定
            ['key' => 'pwned_password_check_enabled', 'value' => '0'], // デフォルト: 無効

            // ログイン通知設定
            ['key' => 'login_notification_mode', 'value' => '0'], // 0 = UseProfileSetting（プロファイルに任せる）
            ['key' => 'send_login_notice_to_system', 'value' => '0'], // デフォルト: システム通知無効
            ['key' => 'system_login_notice_email', 'value' => ''], // デフォルト: 空（管理者メールアドレス）

            // 二段階認証設定
            ['key' => 'force_2fa', 'value' => '1'], // 0 = 無効, 1 = 新しいデバイスのみ, 2 = 常に有効, 3 = プロフィール設定を反映
            ['key' => 'enabled_two_factor_methods', 'value' => '0'], // メール認証のみ有効
            ['key' => 'default_two_factor_method', 'value' => '0'], // デフォルトの認証方法はメール認証
            ['key' => 'two_factor_expire_minutes', 'value' => '10'], // デフォルト: 10分（メール・デバイス共通）
            ['key' => 'two_factor_resend_interval_seconds', 'value' => '60'], // デフォルト: 60秒

            // ログイン試行制限設定
            ['key' => 'login_attempt_limit_enabled', 'value' => '1'], // デフォルト: 有効
            ['key' => 'login_attempt_max_attempts', 'value' => '5'], // デフォルト: 5回
            ['key' => 'login_attempt_time_window', 'value' => '15'], // デフォルト: 15分
            ['key' => 'login_attempt_lockout_duration', 'value' => '30'], // デフォルト: 30分
            ['key' => 'lockout_notification_enabled', 'value' => '1'], // デフォルト: 有効

            // 管理メンバー用セッション設定
            ['key' => 'members_session_lifetime_enabled', 'value' => '0'], // デフォルト: 無効（セキュリティ設定のデフォルト値を使用）
            ['key' => 'members_session_lifetime', 'value' => '120'], // デフォルト: 120分

        ];

        foreach ($settings as $setting) {
            DB::table($this->table)->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
