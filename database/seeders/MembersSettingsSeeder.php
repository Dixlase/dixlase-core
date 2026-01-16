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

            // ログイン通知設定
            ['key' => 'login_notification_mode', 'value' => '3'], // 0=無効, 1=異なるデバイス, 2=常に有効, 3=プロフィール設定に従う
            ['key' => 'login_notification_send_to_system', 'value' => '0'], // デフォルト: システム通知無効
            ['key' => 'login_notification_system_email', 'value' => ''], // デフォルト: 空（管理者メールアドレス）

            // 二段階認証設定
            ['key' => 'two_fa_mode', 'value' => '3'], // 0=無効, 1=異なるデバイス, 2=常に有効, 3=プロフィール設定に従う
            ['key' => 'two_fa_default_method', 'value' => '0'], // デフォルトの認証方法はメール認証
            ['key' => 'two_fa_expire_minutes', 'value' => '5'], // デフォルト: 5分（メール認証）
            ['key' => 'two_fa_resend_interval_seconds', 'value' => '60'], // デフォルト: 60秒
            ['key' => 'two_fa_passkey_mode', 'value' => '2'], // 0=無効, 1=有効, 2=プロフィール設定に従う（デフォルト: プロフィール設定に従う）
            ['key' => 'two_fa_passkey_max_devices', 'value' => '3'], // Passkey最大登録数（1-5）
            ['key' => 'two_fa_recovery_codes_count', 'value' => '5'], // 回復コード生成個数（1-5）
            ['key' => 'two_fa_recovery_code_regenerate_interval', 'value' => '24'], // 回復コード再生成間隔（時間）
            ['key' => 'two_fa_verification_timeout', 'value' => '10'], // 2FA認証待ち画面タイムアウト（5-60分）
            ['key' => 'two_fa_max_attempts', 'value' => '5'], // 2FA試行制限（1-10回）
            ['key' => 'two_fa_attempt_window', 'value' => '15'], // 2FA試行制限時間枠（5-60分）
            ['key' => 'two_fa_lockout_duration', 'value' => '30'], // 2FAロックアウト時間（5-1440分）
            ['key' => 'two_fa_lockout_notification_enabled', 'value' => '1'], // 2FAロックアウト通知有効/無効

            // ログイン試行制限設定
            ['key' => 'login_attempt_limit_enabled', 'value' => '1'], // デフォルト: 有効
            ['key' => 'login_attempt_max_attempts', 'value' => '5'], // デフォルト: 5回（識別子ベース）
            ['key' => 'login_attempt_max_attempts_ip', 'value' => '10'], // デフォルト: 10回（IPベース）
            ['key' => 'login_attempt_time_window', 'value' => '15'], // デフォルト: 15分
            ['key' => 'login_attempt_lockout_duration', 'value' => '30'], // デフォルト: 30分
            ['key' => 'login_attempt_lockout_notification_enabled', 'value' => '1'], // デフォルト: 有効

            // 管理メンバー用セッション設定
            ['key' => 'members_session_lifetime_enabled', 'value' => '0'], // デフォルト: 無効（セキュリティ設定のデフォルト値を使用）
            ['key' => 'members_session_lifetime', 'value' => '120'], // デフォルト: 120分

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
