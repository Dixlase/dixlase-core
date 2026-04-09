<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\BaseSetting;
use App\Models\Member;
use App\Models\MemberTwoFaToken;
use App\Models\SecuritySetting;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * 2FA（二段階認証）フローの統合セキュリティテスト
 *
 * - メール 2FA コード検証
 * - 不正コード・期限切れの拒否
 * - リカバリーコードの使用と無効化
 * - セッション管理（2FA 未完了でのアクセス拒否）
 */
class AdminTwoFaFlowTest extends TestCase
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
            'two_fa_mode' => AuthenticationMode::Always->value,
        ]);

        // 2FA 有効化 + ロックアウト無効化
        SecuritySetting::setValue('two_fa_mode', AuthenticationMode::Always->value);
        SecuritySetting::setValue('two_fa_expire_minutes', 5);
        SecuritySetting::setValue('two_fa_max_attempts', 5);
        SecuritySetting::setValue('two_fa_attempt_window', 15);
        SecuritySetting::setValue('two_fa_lockout_duration', 30);
        SecuritySetting::setValue('login_attempt_limit_enabled', false);

        // メールサーバーテスト済みフラグを設定（2FAがスキップされないように）
        BaseSetting::setValue('mail_connection_tested', true);
        BaseSetting::setValue('mail_send_tested', true);
        BaseSetting::setValue('mail_receive_tested', true);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /**
     * ログインして 2FA セッションを確立し、既知のコードでトークンを置き換える
     *
     * ログイン時にサービスが自動的にトークンを生成するため、
     * ログイン後にトークンを置き換える必要がある。
     */
    private function loginAndSetupTwoFa(string $plainCode = '654321'): void
    {
        $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'secure-password-123',
        ]);

        // 自動生成されたトークンを削除して既知のコードで置換
        MemberTwoFaToken::where('member_id', $this->admin->id)->delete();
        MemberTwoFaToken::create([
            'member_id' => $this->admin->id,
            'code' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    /**
     * ログインして 2FA セッションを確立する（トークン操作なし）
     */
    private function loginAndGetTwoFaSession(): void
    {
        $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'secure-password-123',
        ]);
    }

    /**
     * 期限切れ 2FA トークンに置き換える
     */
    private function replaceWithExpiredToken(string $plainCode = '654321'): void
    {
        MemberTwoFaToken::where('member_id', $this->admin->id)->delete();
        MemberTwoFaToken::create([
            'member_id' => $this->admin->id,
            'code' => Hash::make($plainCode),
            'expires_at' => now()->subMinutes(1),
        ]);
    }

    // =========================================================================
    // 2FA が有効な場合のログインフロー
    // =========================================================================

    public function test_login_with_2fa_enabled_redirects_to_2fa_page(): void
    {
        $this->loginAndGetTwoFaSession();

        // ダッシュボードではなく 2FA ページにリダイレクトされること
        $this->assertGuest('member');
        // セッションにユーザーIDが保存されていること
        $this->assertTrue(session()->has('login.id'));
    }

    public function test_2fa_session_without_login_redirects_to_login(): void
    {
        // ログインせずに 2FA ページにアクセス
        $response = $this->get(route('admin.two-fa.email.show'));

        $response->assertRedirect(route('admin.login'));
    }

    // =========================================================================
    // メール 2FA コード検証
    // =========================================================================

    public function test_valid_2fa_code_completes_authentication(): void
    {
        $this->loginAndSetupTwoFa('654321');

        $response = $this->post(route('admin.two-fa.email.verify'), [
            'code' => '654321',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin, 'member');
    }

    public function test_invalid_2fa_code_is_rejected(): void
    {
        $this->loginAndSetupTwoFa('654321');

        $response = $this->post(route('admin.two-fa.email.verify'), [
            'code' => '000000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['code']);
        $this->assertGuest('member');
    }

    public function test_expired_2fa_code_is_rejected(): void
    {
        $this->loginAndGetTwoFaSession();
        $this->replaceWithExpiredToken('654321');

        $response = $this->post(route('admin.two-fa.email.verify'), [
            'code' => '654321',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['code']);
        $this->assertGuest('member');
    }

    public function test_2fa_code_is_consumed_after_use(): void
    {
        $this->loginAndSetupTwoFa('654321');

        // 検証成功
        $this->post(route('admin.two-fa.email.verify'), [
            'code' => '654321',
        ]);

        $this->assertAuthenticatedAs($this->admin, 'member');

        // トークンが消費されていること（DB から削除）
        $this->assertDatabaseMissing('members_two_fa_tokens', [
            'member_id' => $this->admin->id,
        ]);
    }

    public function test_empty_2fa_code_is_rejected(): void
    {
        $this->loginAndGetTwoFaSession();

        $response = $this->post(route('admin.two-fa.email.verify'), [
            'code' => '',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    // =========================================================================
    // リカバリーコード
    // =========================================================================

    public function test_valid_recovery_code_completes_authentication(): void
    {
        $this->loginAndGetTwoFaSession();

        // リカバリーコードを生成
        $recoveryService = app(TwoFaRecoveryCodeService::class);
        $codes = $recoveryService->generate($this->admin);

        $response = $this->post(route('admin.two-fa.recovery-code.confirm'), [
            'recovery_code' => $codes[0],
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin, 'member');
    }

    public function test_invalid_recovery_code_is_rejected(): void
    {
        $this->loginAndGetTwoFaSession();

        $response = $this->post(route('admin.two-fa.recovery-code.confirm'), [
            'recovery_code' => '00000-00000-00000-00000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['recovery_code']);
        $this->assertGuest('member');
    }

    public function test_used_recovery_code_cannot_be_reused(): void
    {
        // リカバリーコード生成
        $recoveryService = app(TwoFaRecoveryCodeService::class);
        $codes = $recoveryService->generate($this->admin);
        $firstCode = $codes[0];

        // 1回目のログイン + リカバリーコード使用
        $this->loginAndGetTwoFaSession();
        $this->post(route('admin.two-fa.recovery-code.confirm'), [
            'recovery_code' => $firstCode,
        ]);
        $this->assertAuthenticatedAs($this->admin, 'member');

        // 使用済みコードが DB で used_at が設定されていること
        $usedCode = $this->admin->twoFaRecoveryCodes()
            ->whereNotNull('used_at')
            ->first();
        $this->assertNotNull($usedCode, 'Recovery code should be marked as used');

        // 使用済みコードの validate は false を返すこと
        $this->assertFalse(
            $recoveryService->validate($this->admin, $firstCode),
            'Used recovery code should not validate'
        );
    }

    public function test_recovery_code_remaining_count_decreases_after_use(): void
    {
        $this->loginAndGetTwoFaSession();

        $recoveryService = app(TwoFaRecoveryCodeService::class);
        $codes = $recoveryService->generate($this->admin);
        $initialCount = $recoveryService->getRemainingCount($this->admin);

        // 1つ使用
        $this->post(route('admin.two-fa.recovery-code.confirm'), [
            'recovery_code' => $codes[0],
        ]);

        // 残りが減っていること
        $this->assertEquals($initialCount - 1, $recoveryService->getRemainingCount($this->admin));
    }

    // =========================================================================
    // 2FA 未完了でのアクセス制限
    // =========================================================================

    public function test_cannot_access_dashboard_during_2fa_verification(): void
    {
        $this->loginAndGetTwoFaSession();

        // 2FA 検証中にダッシュボードにアクセスしようとする
        $response = $this->get(route('admin.dashboard'));

        // ダッシュボードにはアクセスできないこと
        // (ログインが完了していないので未認証)
        $this->assertGuest('member');
        $response->assertRedirect();
    }
}
