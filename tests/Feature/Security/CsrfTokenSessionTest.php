<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Tests\Feature\Security;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SiteSetting;
use App\Session\GuardAwareDatabaseSessionHandler;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use ReflectionClass;
use Tests\TestCase;

/**
 * CSRF / セッション回帰テスト
 *
 * 直近の本番障害（8f575c07: アセット並列リクエストが管理セッションを破壊し
 * 419 を引き起こす）と、その原因周辺となる GuardAwareDatabaseSessionHandler の
 * 管理 URL 解決ロジックを対象に回帰検出を行う。
 */
class CsrfTokenSessionTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        SiteSetting::setValue('site_name', 'Test Site');

        $this->admin = Member::create([
            'account_name' => 'csrfadmin',
            'display_name' => 'CSRF Admin',
            'email' => 'csrfadmin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        // GuardAwareDatabaseSessionHandler の静的キャッシュをテスト間で
        // 持ち越さないようにリセット
        $this->resetGuardAwareCache();
    }

    protected function tearDown(): void
    {
        $this->resetGuardAwareCache();
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    /**
     * リフレクションで保護された静的プロパティをクリアする
     */
    private function resetGuardAwareCache(): void
    {
        $ref = new ReflectionClass(GuardAwareDatabaseSessionHandler::class);
        $prop = $ref->getProperty('resolvedAdminUrl');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    /**
     * アセット配信ルートはセッション・CSRF 系ミドルウェアを必ず除外する
     *
     * 8f575c07 の根本対策。並列 GET でゲスト用 sessions テーブルに同じ
     * session_id で write が走ると members_sessions の admin セッションが
     * 上書きされ 419 を誘発するため、対象ミドルウェアを必ず外しておく。
     */
    public function test_asset_route_excludes_session_and_csrf_middleware(): void
    {
        $route = Route::getRoutes()->getByName('test.front.log')
            ? null
            : null;

        $route = collect(Route::getRoutes())->first(
            fn ($r) => $r->uri() === 'assets/{type}/{file}'
        );

        $this->assertNotNull($route, 'assets/{type}/{file} ルートが見つからない');

        $excluded = $route->excludedMiddleware();

        $this->assertContains(
            StartSession::class,
            $excluded,
            'StartSession が除外されていない（並列 GET でセッション競合のリスク）'
        );
        $this->assertContains(
            ShareErrorsFromSession::class,
            $excluded,
            'ShareErrorsFromSession が除外されていない'
        );
        $this->assertContains(
            AddQueuedCookiesToResponse::class,
            $excluded,
            'AddQueuedCookiesToResponse が除外されていない'
        );
        $this->assertContains(
            PreventRequestForgery::class,
            $excluded,
            'PreventRequestForgery が除外されていない'
        );
    }

    /**
     * アセットの並列 GET 後でも管理画面 POST がセッションを保持できる
     *
     * テスト環境は SESSION_DRIVER=array のためテーブル競合は再現しないが、
     * 「アセット GET → 管理 POST」のフロー自体が 419 を出さないことを担保する。
     * もしアセットルートのセッション除外が失われた場合、StartSession の
     * 動作変化により本フローが崩れる可能性があるため回帰検知の意味がある。
     */
    public function test_admin_post_succeeds_after_parallel_asset_requests(): void
    {
        // 10 本のアセット GET を順次発行（テストでは並列実行できないため逐次代替）
        // 200 (ファイル存在) / 404 (未存在) / 500 (テーマ未ロード) のいずれも許容するが
        // 419 だけは絶対に出してはならない（CSRF middleware の混入回帰）
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/assets/theme/nonexistent-file.css');
            $this->assertNotEquals(
                419,
                $response->getStatusCode(),
                'アセット GET で 419 が発生した（CSRF middleware が混入している可能性）'
            );
        }

        // 直後の管理 POST が 419 にならないこと
        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.base.admin'))
            ->post(route('admin.settings.base.admin.update'), [
                'admin_url' => 'admin',
            ]);

        $this->assertNotEquals(
            419,
            $response->getStatusCode(),
            '管理 POST がアセット並列 GET の影響で 419 になった（回帰）'
        );
    }

    /**
     * CSP レポートエンドポイントは CSRF 検証から除外されている
     *
     * bootstrap/app.php の preventRequestForgery(except: ['csp-report']) が
     * 残っていることを保証するベースラインテスト。
     */
    public function test_csp_report_endpoint_skips_csrf_validation(): void
    {
        $response = $this->postJson('/csp-report', [
            'csp-report' => [
                'document-uri' => 'https://example.com',
                'violated-directive' => 'script-src',
            ],
        ]);

        $this->assertNotEquals(
            419,
            $response->getStatusCode(),
            'CSP report が CSRF で弾かれた（bootstrap/app.php の except 設定が外れた可能性）'
        );
    }

    /**
     * GuardAwareDatabaseSessionHandler が DB の admin_url を優先する
     *
     * 8f575c07 の修正点：config('admin.url.admin_url') はデフォルト値しか
     * 返さないため、必ず DB の site_settings.admin_url を参照する必要がある。
     * カスタム admin_url 設定環境で session が誤テーブルに書かれて 419 が
     * 発生していた事象の回帰検知。
     */
    public function test_guard_aware_handler_resolves_admin_url_from_database(): void
    {
        SiteSetting::setValue('admin_url', 'my-custom-cp');

        $handler = new GuardAwareDatabaseSessionHandler(
            app('db')->connection(),
            'sessions',
            120,
            app()
        );

        $ref = new ReflectionClass($handler);
        $method = $ref->getMethod('resolveAdminUrl');
        $method->setAccessible(true);

        $resolved = $method->invoke($handler);

        $this->assertSame(
            'my-custom-cp',
            $resolved,
            'admin_url が DB の値で解決されない（config デフォルトにフォールバックしている可能性）'
        );
    }

    /**
     * DB の admin_url が未設定なら config デフォルトにフォールバックする
     */
    public function test_guard_aware_handler_falls_back_to_config_default(): void
    {
        // site_settings に admin_url レコードが存在しない状態
        SiteSetting::query()->where('name', 'admin_url')->delete();

        $handler = new GuardAwareDatabaseSessionHandler(
            app('db')->connection(),
            'sessions',
            120,
            app()
        );

        $ref = new ReflectionClass($handler);
        $method = $ref->getMethod('resolveAdminUrl');
        $method->setAccessible(true);

        $resolved = $method->invoke($handler);

        $this->assertSame(
            config('admin.url.admin_url', 'admin') ?: 'admin',
            $resolved,
            'DB 未設定時に config デフォルトへ落ちていない'
        );
    }

    /**
     * GuardAwareDatabaseSessionHandler の guard 解決が admin パスで member を返す
     *
     * パスベース判定が壊れると admin リクエストがゲストテーブルに書かれて
     * セッションが分離されないため、ガード判定の回帰を検知する。
     */
    public function test_guard_aware_handler_routes_admin_path_to_member_guard(): void
    {
        SiteSetting::setValue('admin_url', 'admin');
        $this->resetGuardAwareCache();

        $handler = new GuardAwareDatabaseSessionHandler(
            app('db')->connection(),
            'sessions',
            120,
            app()
        );
        $handler->setGuardTable('member', 'members_sessions');

        // request() を admin パスでバインド
        $request = Request::create('/admin/dashboard', 'GET');
        app()->instance('request', $request);

        $ref = new ReflectionClass($handler);
        $getCurrentGuard = $ref->getMethod('getCurrentGuard');
        $getCurrentGuard->setAccessible(true);
        $guard = $getCurrentGuard->invoke($handler);

        $this->assertSame('member', $guard, 'admin パスが member ガードに解決されない');

        $getTable = $ref->getMethod('getTable');
        $getTable->setAccessible(true);
        $table = $getTable->invoke($handler);

        $this->assertSame(
            'members_sessions',
            $table,
            'admin パスのセッションが members_sessions テーブルに振られない'
        );
    }

    /**
     * カスタム admin_url 設定下でも guard 判定が member を返す
     *
     * 7cc69871 / 8f575c07 が解消した本来のバグ。admin_url='manage' のとき
     * /manage/* は member ガードに振られなければならない。
     */
    public function test_guard_aware_handler_routes_custom_admin_url_to_member_guard(): void
    {
        SiteSetting::setValue('admin_url', 'manage');
        $this->resetGuardAwareCache();

        $handler = new GuardAwareDatabaseSessionHandler(
            app('db')->connection(),
            'sessions',
            120,
            app()
        );
        $handler->setGuardTable('member', 'members_sessions');

        $request = Request::create('/manage/dashboard', 'GET');
        app()->instance('request', $request);

        $ref = new ReflectionClass($handler);
        $getCurrentGuard = $ref->getMethod('getCurrentGuard');
        $getCurrentGuard->setAccessible(true);
        $guard = $getCurrentGuard->invoke($handler);

        $this->assertSame(
            'member',
            $guard,
            'カスタム admin_url のパスが member ガードに振り分けられない（419 の温床）'
        );
    }

    /**
     * ゲストパスは null ガード（デフォルト sessions テーブル）を返す
     *
     * admin / mypage 以外のパスは guest セッションに振り分けられる。
     * ゲスト遷移パスが member ガードに誤判定されないかの回帰検知。
     */
    public function test_guard_aware_handler_returns_null_for_guest_path(): void
    {
        SiteSetting::setValue('admin_url', 'admin');
        $this->resetGuardAwareCache();

        $handler = new GuardAwareDatabaseSessionHandler(
            app('db')->connection(),
            'sessions',
            120,
            app()
        );
        $handler->setGuardTable('member', 'members_sessions');

        $request = Request::create('/about-us', 'GET');
        app()->instance('request', $request);

        $ref = new ReflectionClass($handler);
        $getCurrentGuard = $ref->getMethod('getCurrentGuard');
        $getCurrentGuard->setAccessible(true);
        $guard = $getCurrentGuard->invoke($handler);

        $this->assertNull($guard, 'ゲストパスが何らかのガードに振り分けられた');
    }

    /**
     * Paths whose first segment merely starts with the admin prefix
     * (e.g. /admin-bar/logout under the default 'admin' prefix) must
     * NOT be routed to the member guard. A substring `str_starts_with`
     * match collapsed those routes onto `members_sessions`, while their
     * forms were rendered on front-end pages against the `sessions`
     * row, producing a CSRF token mismatch (419) on every submit. The
     * matcher must compare path segments, not raw string prefixes.
     *
     * Regression covers the /admin-bar/logout endpoint introduced for
     * the front-side admin bar logout flow.
     */
    public function test_guard_aware_handler_does_not_misroute_admin_prefix_lookalike_paths(): void
    {
        SiteSetting::setValue('admin_url', 'admin');
        $this->resetGuardAwareCache();

        $handler = new GuardAwareDatabaseSessionHandler(
            app('db')->connection(),
            'sessions',
            120,
            app()
        );
        $handler->setGuardTable('member', 'members_sessions');

        $ref = new ReflectionClass($handler);
        $getCurrentGuard = $ref->getMethod('getCurrentGuard');
        $getCurrentGuard->setAccessible(true);

        $lookalikePaths = [
            '/admin-bar/logout',
            '/administration',
            '/admin2/dashboard',
        ];

        foreach ($lookalikePaths as $path) {
            $request = Request::create($path, 'GET');
            app()->instance('request', $request);

            // Fresh handler instance per path so the per-instance
            // `currentGuard` cache does not carry over.
            $handler = new GuardAwareDatabaseSessionHandler(
                app('db')->connection(),
                'sessions',
                120,
                app()
            );
            $handler->setGuardTable('member', 'members_sessions');

            $guard = $getCurrentGuard->invoke($handler);

            $this->assertNull(
                $guard,
                "path {$path} must not route to the member guard (admin-prefix segment match regression)",
            );
        }
    }

    /**
     * Mirror of the lookalike regression for a custom admin_url:
     * setting `admin_url = 'manage'` must still match `/manage` and
     * `/manage/...` but must not match `/management-app/...`.
     */
    public function test_guard_aware_handler_segment_matches_custom_admin_url(): void
    {
        SiteSetting::setValue('admin_url', 'manage');
        $this->resetGuardAwareCache();

        $ref = new ReflectionClass(GuardAwareDatabaseSessionHandler::class);
        $getCurrentGuard = $ref->getMethod('getCurrentGuard');
        $getCurrentGuard->setAccessible(true);

        $cases = [
            '/manage' => 'member',
            '/manage/dashboard' => 'member',
            '/management-app/dashboard' => null,
            '/managed-account' => null,
        ];

        foreach ($cases as $path => $expected) {
            $request = Request::create($path, 'GET');
            app()->instance('request', $request);

            $handler = new GuardAwareDatabaseSessionHandler(
                app('db')->connection(),
                'sessions',
                120,
                app()
            );
            $handler->setGuardTable('member', 'members_sessions');

            $guard = $getCurrentGuard->invoke($handler);

            $this->assertSame(
                $expected,
                $guard,
                "path {$path} expected guard ".var_export($expected, true).', got '.var_export($guard, true),
            );
        }
    }
}
