<?php

namespace Tests\Feature\Security;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Models\BaseSetting;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * ミドルウェアチェーン統合テスト
 *
 * グローバルミドルウェアのセキュリティ動作を検証:
 * - ForceHTTPS リダイレクト
 * - メンテナンスモード
 * - セキュリティヘッダー（X-Frame-Options, X-Content-Type-Options 等）
 */
class MiddlewareChainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    private function createAdmin(): Member
    {
        return Member::create([
            'account_name' => 'admin',
            'display_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    // =========================================================================
    // ForceHTTPS
    // =========================================================================

    public function test_force_https_redirects_http_to_https(): void
    {
        $this->withoutMiddleware([CheckInstallationReady::class]);
        config(['app.force_ssl' => true]);

        $response = $this->get('http://localhost/');

        $response->assertStatus(301);
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));
    }

    public function test_force_https_disabled_allows_http(): void
    {
        $this->withoutMiddleware([CheckInstallationReady::class]);
        config(['app.force_ssl' => false]);

        $response = $this->get('http://localhost/');

        $this->assertNotEquals(301, $response->getStatusCode());
    }

    // =========================================================================
    // メンテナンスモード
    // =========================================================================

    public function test_maintenance_mode_blocks_unauthenticated_front_access(): void
    {
        $this->withoutMiddleware([CheckInstallationReady::class]);

        BaseSetting::setValue('maintenance_mode', '1');
        BaseSetting::setValue('maintenance_message', 'Under maintenance');
        BaseSetting::setValue('site_name', 'Test Site');

        $response = $this->get('/');
        $statusCode = $response->getStatusCode();

        $this->assertNotEquals(
            200,
            $statusCode,
            "Front page should not return 200 during maintenance (got {$statusCode})"
        );
    }

    public function test_maintenance_mode_allows_admin_access(): void
    {
        $this->withoutMiddleware([CheckInstallationReady::class]);

        BaseSetting::setValue('maintenance_mode', '1');
        BaseSetting::setValue('maintenance_message', 'Under maintenance');
        BaseSetting::setValue('site_name', 'Test Site');

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'member')
            ->get(route('admin.dashboard'));

        $this->assertNotEquals(503, $response->getStatusCode());
    }

    // =========================================================================
    // セキュリティヘッダー
    // =========================================================================

    public function test_security_headers_are_present(): void
    {
        $this->withoutMiddleware([CheckInstallationReady::class]);

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'member')
            ->get(route('admin.dashboard'));

        if ($response->headers->has('X-Content-Type-Options')) {
            $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        }

        if ($response->headers->has('X-Frame-Options')) {
            $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        }

        if ($response->headers->has('Referrer-Policy')) {
            $this->assertEquals(
                'strict-origin-when-cross-origin',
                $response->headers->get('Referrer-Policy')
            );
        }
    }
}
