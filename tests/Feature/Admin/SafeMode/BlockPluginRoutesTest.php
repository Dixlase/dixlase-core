<?php

namespace Tests\Feature\Admin\SafeMode;

use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\BlockPluginRoutes;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * BlockPluginRoutes ミドルウェアのテスト
 *
 * テスト専用ルートとミドルウェア直接テストの組み合わせで
 * プラグインルートのブロック動作を検証する。
 */
class BlockPluginRoutesTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $this->superAdmin = Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
            'two_fa_mode' => AuthenticationMode::Disabled,
        ]);

        // テスト専用ルート: コアルート（BlockPluginRoutes ミドルウェア付き）
        Route::middleware('web')->get('/test-core-route', function () {
            return response('ok');
        })->middleware(BlockPluginRoutes::class)->name('test.core-route');
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    public function test_non_plugin_routes_are_not_blocked_in_plugin_safe_mode(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        $response = $this->withSession(['safe_mode_plugins' => true])
            ->get('/test-core-route');

        // コアルート（クロージャ）はブロックされない
        $response->assertOk();
        $response->assertSee('ok');
    }

    public function test_routes_are_not_blocked_when_plugin_safe_mode_inactive(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        // セーフモード未有効時はルートがブロックされない
        $response = $this->get('/test-core-route');

        $response->assertOk();
    }

    public function test_middleware_blocks_plugin_controller_routes(): void
    {
        $this->actingAs($this->superAdmin, 'member');

        // BlockPluginRoutes ミドルウェアのロジックを直接テスト
        $middleware = app(BlockPluginRoutes::class);

        $request = Request::create('/test-plugin-route', 'GET');

        // プラグインコントローラーを模擬するルートを作成
        $route = new RoutingRoute('GET', '/test-plugin-route', [
            'uses' => 'Plugins\\TestPlugin\\Http\\Controllers\\TestController@index',
        ]);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        // セッションにプラグインセーフモードを設定
        session(['safe_mode_plugins' => true]);

        $response = $middleware->handle($request, fn ($req) => response('ok'));

        // プラグインルートはリダイレクトされる
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_middleware_does_not_block_when_safe_mode_inactive(): void
    {
        // セーフモード未有効時のミドルウェア直接テスト
        $middleware = app(BlockPluginRoutes::class);

        $request = Request::create('/test-plugin-route', 'GET');

        $route = new RoutingRoute('GET', '/test-plugin-route', [
            'uses' => 'Plugins\\TestPlugin\\Http\\Controllers\\TestController@index',
        ]);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $response = $middleware->handle($request, fn ($req) => response('ok'));

        // セーフモード未有効なのでブロックされない
        $this->assertEquals(200, $response->getStatusCode());
    }
}
