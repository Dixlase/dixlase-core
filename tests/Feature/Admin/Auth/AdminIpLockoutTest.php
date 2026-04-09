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
 * IP レベルロックアウトのセキュリティテスト
 *
 * - 同一 IP からの大量失敗で IP ロックアウト
 * - IP ロックアウト中は別識別子でも拒否
 * - ロックアウト設定の動作確認
 */
class AdminIpLockoutTest extends TestCase
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
            'password' => Hash::make('correct-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        // ロックアウト有効化（低い閾値でテストしやすく）
        SecuritySetting::setValue('login_attempt_limit_enabled', true);
        SecuritySetting::setValue('login_attempt_max_attempts', 3);
        SecuritySetting::setValue('login_attempt_time_window', 15);
        SecuritySetting::setValue('login_attempt_lockout_duration', 30);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    // =========================================================================
    // IP レベルロックアウト
    // =========================================================================

    public function test_ip_lockout_triggers_after_threshold(): void
    {
        // IP ロックアウト閾値 = max_attempts * 2 = 6
        // 異なる識別子で同一 IP から 6 回失敗
        for ($i = 0; $i < 6; $i++) {
            MemberLoginAttempt::recordAttempt(
                "user{$i}@example.com",
                '127.0.0.1',
                'TestAgent',
                false
            );
        }

        // 正しいパスワードでも IP ロックアウトで拒否されること
        $response = $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    public function test_ip_lockout_blocks_different_identifiers(): void
    {
        // 別ユーザーを作成
        $otherAdmin = Member::create([
            'account_name' => 'other',
            'display_name' => 'Other',
            'email' => 'other@example.com',
            'password' => Hash::make('other-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ]);

        // 同一 IP で閾値を超える失敗を記録
        for ($i = 0; $i < 6; $i++) {
            MemberLoginAttempt::recordAttempt(
                "attacker{$i}@example.com",
                '127.0.0.1',
                'TestAgent',
                false
            );
        }

        // 別の正当なユーザーも同じ IP からはブロックされること
        $response = $this->post(route('admin.login.store'), [
            'login' => 'other@example.com',
            'password' => 'other-password',
        ]);

        $response->assertRedirect();
        $this->assertGuest('member');
    }

    public function test_identifier_lockout_does_not_affect_other_users(): void
    {
        $otherAdmin = Member::create([
            'account_name' => 'other',
            'display_name' => 'Other',
            'email' => 'other@example.com',
            'password' => Hash::make('other-password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ]);

        // admin@example.com で 3 回失敗 → 識別子ロックアウト
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login.store'), [
                'login' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // admin はロックアウト
        $response = $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'correct-password',
        ]);
        $this->assertGuest('member');

        // other は（IP ロックアウト閾値未達なら）まだログイン可能
        // IP からの試行は 3 回、IP 閾値は 6 なのでまだ余裕あり
        $response = $this->post(route('admin.login.store'), [
            'login' => 'other@example.com',
            'password' => 'other-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($otherAdmin, 'member');
    }

    public function test_lockout_disabled_allows_unlimited_ip_attempts(): void
    {
        SecuritySetting::setValue('login_attempt_limit_enabled', false);

        // 大量の失敗を記録
        for ($i = 0; $i < 20; $i++) {
            MemberLoginAttempt::recordAttempt(
                "user{$i}@example.com",
                '127.0.0.1',
                'TestAgent',
                false
            );
        }

        // ロックアウト無効なのでログイン可能
        $response = $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin, 'member');
    }

    public function test_successful_login_does_not_clear_ip_lockout_for_others(): void
    {
        // 異なる識別子で IP 閾値に近い失敗を記録（5回 = 閾値6の1手前）
        for ($i = 0; $i < 5; $i++) {
            MemberLoginAttempt::recordAttempt(
                "attacker{$i}@example.com",
                '127.0.0.1',
                'TestAgent',
                false
            );
        }

        // 正当なユーザーがログイン成功
        $this->post(route('admin.login.store'), [
            'login' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $this->assertAuthenticatedAs($this->admin, 'member');

        // IP からの失敗試行カウントは残っていること（他人の失敗はクリアされない）
        $ipFailedCount = MemberLoginAttempt::getFailedAttemptsCountByIp('127.0.0.1', 15);
        $this->assertEquals(5, $ipFailedCount);
    }
}
