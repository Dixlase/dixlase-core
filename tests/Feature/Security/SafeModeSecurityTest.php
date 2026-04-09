<?php

namespace Tests\Feature\Security;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * SafeMode セキュリティテスト
 *
 * - SafeMode は認証済みユーザーのみ使用可能であること
 * - CSP SafeMode が正しく動作すること
 * - 未認証での SafeMode パラメータは無視されること
 */
class SafeModeSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        $this->admin = Member::create([
            'account_name' => 'admin',
            'display_name' => 'Admin',
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
        parent::tearDown();
    }

    // =========================================================================
    // SafeMode 認証要件
    // =========================================================================

    public function test_safe_mode_requires_authentication(): void
    {
        // 未認証で ?safe=csp を付けてもセッションにフラグが設定されないこと
        $this->get('/?safe=csp');

        $this->assertNull(session('safe_mode_csp'));
    }

    public function test_safe_mode_activates_for_authenticated_user(): void
    {
        $this->actingAs($this->admin, 'member')
            ->get(route('admin.dashboard', ['safe' => 'csp']));

        $this->assertTrue(session('safe_mode_csp', false));
    }

    public function test_safe_mode_plugins_activates_for_authenticated_user(): void
    {
        $this->actingAs($this->admin, 'member')
            ->get(route('admin.dashboard', ['safe' => 'plugins']));

        $this->assertTrue(session('safe_mode_plugins', false));
    }

    // =========================================================================
    // SafeMode パラメータの安全性
    // =========================================================================

    public function test_invalid_safe_mode_parameter_is_ignored(): void
    {
        $this->actingAs($this->admin, 'member')
            ->get(route('admin.dashboard', ['safe' => 'invalid_mode']));

        // 不正なモードではセッションフラグが設定されないこと
        $this->assertNull(session('safe_mode_invalid_mode'));
    }
}
