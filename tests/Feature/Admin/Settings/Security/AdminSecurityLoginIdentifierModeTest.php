<?php

namespace Tests\Feature\Admin\Settings\Security;

use App\Enums\AdminMode;
use App\Enums\LoginIdentifierMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Helpers\AdminModeHelper;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\BaseSetting;
use App\Models\Member;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ログイン識別子モード設定のFeatureテスト
 *
 * - 設定画面での表示・保存・バリデーション
 * - 各モードでのログイン動作検証（AdminLoginController/LoginTrait経由）
 */
class AdminSecurityLoginIdentifierModeTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        // インストール済みとしてマーク（CheckInstallationReadyミドルウェア対策）
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        BaseSetting::setValue('site_name', 'Test Site');

        // 詳細モードに設定（設定ページへのアクセスに必要）
        BaseSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    /**
     * フォーム送信用のデフォルトデータを取得
     *
     * @param  array<string, mixed>  $overrides  上書きするフィールド
     * @return array<string, mixed>
     */
    private function validFormData(array $overrides = []): array
    {
        return array_merge([
            'login_identifier_mode' => LoginIdentifierMode::EmailOrAccountName->value,
            'login_notification_mode' => 0,
            'login_attempt_limit_enabled' => false,
            'login_attempt_max_attempts' => 5,
            'login_attempt_max_attempts_ip' => 20,
            'login_attempt_time_window' => 15,
            'login_attempt_lockout_duration' => 30,
            'login_attempt_lockout_notification_enabled' => false,
        ], $overrides);
    }

    public function test_login_settings_page_displays_default_identifier_mode(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.security.login'));

        $response->assertStatus(200);
        $response->assertViewHas('loginIdentifierMode', LoginIdentifierMode::EmailOrAccountName->value);
        $response->assertViewHas('loginIdentifierModeOptions');
    }

    public function test_login_settings_page_shows_radio_card_options(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.security.login'));

        $response->assertStatus(200);
        $options = $response->viewData('loginIdentifierModeOptions');
        $this->assertCount(3, $options);

        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('description', $option);
            $this->assertArrayHasKey('icon', $option);
        }
    }

    // ========================================
    // 設定保存
    // ========================================

    public function test_can_save_email_only_mode(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.security.login.update'), $this->validFormData([
                'login_identifier_mode' => LoginIdentifierMode::EmailOnly->value,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertEquals(
            LoginIdentifierMode::EmailOnly->value,
            (int) SecuritySetting::getValue('login_identifier_mode')
        );
    }

    public function test_can_save_email_or_account_name_mode(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.security.login.update'), $this->validFormData([
                'login_identifier_mode' => LoginIdentifierMode::EmailOrAccountName->value,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertEquals(
            LoginIdentifierMode::EmailOrAccountName->value,
            (int) SecuritySetting::getValue('login_identifier_mode')
        );
    }

    public function test_can_save_account_name_only_mode(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.security.login.update'), $this->validFormData([
                'login_identifier_mode' => LoginIdentifierMode::AccountNameOnly->value,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertEquals(
            LoginIdentifierMode::AccountNameOnly->value,
            (int) SecuritySetting::getValue('login_identifier_mode')
        );
    }

    public function test_saved_mode_persists_after_reload(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.security.login.update'), $this->validFormData([
                'login_identifier_mode' => LoginIdentifierMode::AccountNameOnly->value,
            ]));

        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.security.login'));

        $response->assertStatus(200);
        $response->assertViewHas('loginIdentifierMode', LoginIdentifierMode::AccountNameOnly->value);
    }

    // ========================================
    // バリデーション
    // ========================================

    public function test_rejects_invalid_identifier_mode_value(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.security.login.update'), $this->validFormData([
                'login_identifier_mode' => 99,
            ]));

        $response->assertSessionHasErrors('login_identifier_mode');
    }

    // ========================================
    // ログイン認証動作テスト（AdminLoginController経由）
    // ========================================

    public function test_email_only_mode_allows_email_login(): void
    {
        SecuritySetting::set('login_identifier_mode', LoginIdentifierMode::EmailOnly->value);

        $this->post('/login', [
            'login' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('member');
    }

    public function test_email_only_mode_rejects_account_name_login(): void
    {
        SecuritySetting::set('login_identifier_mode', LoginIdentifierMode::EmailOnly->value);

        $this->post('/login', [
            'login' => 'testadmin',
            'password' => 'password',
        ]);

        $this->assertGuest('member');
    }

    public function test_account_name_only_mode_allows_account_name_login(): void
    {
        SecuritySetting::set('login_identifier_mode', LoginIdentifierMode::AccountNameOnly->value);

        $this->post('/login', [
            'login' => 'testadmin',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('member');
    }

    public function test_account_name_only_mode_rejects_email_login(): void
    {
        SecuritySetting::set('login_identifier_mode', LoginIdentifierMode::AccountNameOnly->value);

        $this->post('/login', [
            'login' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest('member');
    }

    public function test_email_or_account_name_mode_allows_email_login(): void
    {
        SecuritySetting::set('login_identifier_mode', LoginIdentifierMode::EmailOrAccountName->value);

        $this->post('/login', [
            'login' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('member');
    }

    public function test_email_or_account_name_mode_allows_account_name_login(): void
    {
        SecuritySetting::set('login_identifier_mode', LoginIdentifierMode::EmailOrAccountName->value);

        $this->post('/login', [
            'login' => 'testadmin',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('member');
    }

    public function test_default_mode_allows_email_login(): void
    {
        // DB設定なし（デフォルト: EmailOrAccountName）
        $this->post('/login', [
            'login' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('member');
    }
}
