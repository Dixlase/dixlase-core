<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Models\MemberLoginAttempt;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * 管理画面ログインフローの統合セキュリティテスト
 *
 * 認証フロー全体のセキュリティホール検出を目的とする。
 * - 正常ログイン/ログアウト
 * - 認証失敗時の適切なエラーハンドリング
 * - セッション管理（固定攻撃防止、再生成）
 * - 未認証アクセスの拒否
 */
class AdminLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secure-password-123'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        SecuritySetting::setValue('login_attempt_limit_enabled', false);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    // =========================================================================
    // 正常系: ログイン成功
    // =========================================================================

    public function test_login_with_valid_email_and_password_succeeds(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'secure-password-123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin, 'member');
    }

    public function test_login_with_account_name_succeeds(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login' => 'testadmin',
            'password' => 'secure-password-123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin, 'member');
    }

    public function test_login_regenerates_session_id(): void
    {
        $oldSessionId = session()->getId();

        $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'secure-password-123',
        ]);

        $this->assertNotEquals($oldSessionId, session()->getId());
    }

    // =========================================================================
    // 異常系: ログイン失敗
    // =========================================================================

    public function test_login_with_wrong_password_fails(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    public function test_login_with_nonexistent_email_fails(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login' => 'nonexistent@example.com',
            'password' => 'any-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    public function test_login_with_empty_credentials_fails(): void
    {
        $response = $this->post(route('admin.login.store'), [
            'login' => '',
            'password' => '',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    public function test_failed_login_does_not_reveal_user_existence(): void
    {
        $responseExisting = $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $responseNonExisting = $this->post(route('admin.login.store'), [
            'login' => 'nonexistent@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertEquals($responseExisting->getStatusCode(), $responseNonExisting->getStatusCode());
    }

    public function test_failed_login_records_attempt(): void
    {
        SecuritySetting::setValue('login_attempt_limit_enabled', true);
        SecuritySetting::setValue('login_attempt_max_attempts', 10);
        SecuritySetting::setValue('login_attempt_time_window', 15);

        $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('members_login_attempts', [
            'identifier' => 'admin@example.com',
            'successful' => false,
        ]);
    }

    public function test_successful_login_clears_failed_attempts(): void
    {
        SecuritySetting::setValue('login_attempt_limit_enabled', true);
        SecuritySetting::setValue('login_attempt_max_attempts', 10);
        SecuritySetting::setValue('login_attempt_time_window', 15);
        SecuritySetting::setValue('login_attempt_lockout_duration', 30);

        MemberLoginAttempt::recordAttempt('admin@example.com', '127.0.0.1', null, false);
        MemberLoginAttempt::recordAttempt('admin@example.com', '127.0.0.1', null, false);

        $this->assertEquals(2, MemberLoginAttempt::getFailedAttemptsCount('admin@example.com', 15));

        $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'secure-password-123',
        ]);

        $this->assertEquals(0, MemberLoginAttempt::getFailedAttemptsCount('admin@example.com', 15));
    }

    // =========================================================================
    // HTTP メソッド制限
    // =========================================================================

    public function test_login_via_put_method_is_rejected(): void
    {
        $response = $this->put(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'secure-password-123',
        ]);

        $response->assertStatus(405);
        $this->assertGuest('member');
    }

    // =========================================================================
    // ログアウト
    // =========================================================================

    public function test_logout_clears_authentication(): void
    {
        $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'secure-password-123',
        ]);

        $this->assertAuthenticatedAs($this->admin, 'member');

        $this->post(route('admin.logout'));

        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect();
    }

    public function test_logout_regenerates_csrf_token(): void
    {
        $this->actingAs($this->admin, 'member');

        $oldToken = csrf_token();

        $this->post(route('admin.logout'));

        $this->assertNotEquals($oldToken, csrf_token());
    }

    // =========================================================================
    // 未認証アクセスの拒否
    // =========================================================================

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_unauthenticated_user_cannot_access_settings(): void
    {
        $response = $this->get(route('admin.settings.base.index'));

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    // =========================================================================
    // 認証済みユーザーの制限
    // =========================================================================

    public function test_authenticated_user_is_redirected_from_login_page(): void
    {
        $this->actingAs($this->admin, 'member');

        $response = $this->get(route('admin.login'));

        $response->assertRedirect(route('admin.dashboard'));
    }

    // =========================================================================
    // パスワードハッシュ
    // =========================================================================

    public function test_plaintext_password_does_not_match(): void
    {
        $this->admin->refresh();
        $this->assertNotEquals('secure-password-123', $this->admin->password);
        $this->assertTrue(Hash::check('secure-password-123', $this->admin->password));
    }

    // =========================================================================
    // 無効なアカウント状態
    // =========================================================================

    public function test_inactive_member_cannot_access_dashboard_after_login(): void
    {
        $inactiveMember = Member::create([
            'account_name' => 'inactive',
            'display_name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Inactive,
        ]);

        $this->post(route('admin.login.store'), [
            'login' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response = $this->get(route('admin.dashboard'));
        $this->assertTrue(
            $response->isRedirect() || $response->isForbidden(),
            'Inactive member should be redirected or forbidden'
        );
    }
}
