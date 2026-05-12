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

namespace Tests\Feature\I18n;

use App\Helpers\LocaleHelper;
use App\Http\Middleware\SetFrontLocale;
use Database\Seeders\SitesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Locale resolution behavior in v0.1.0.
 *
 * v0.1.0 ships the locale infrastructure (helpers, middleware, contracts)
 * but does NOT register a /{locale}/ URL group or auto-redirect from
 * locale-less URLs. Any multilingual plugin (first-party, third-party,
 * or a custom in-house implementation) that wires itself to the same
 * contracts opts into URL routing by wrapping its routes in
 * Route::prefix('{locale}') and registering its own Route::fallback()
 * redirect.
 */
class LocaleResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
        putenv('INSTALLED=true');

        $this->seed(SitesSeeder::class);
        DB::table('sites')->where('is_primary', true)->update(['primary_locale' => 'ja']);

        // TestCase auto-seed primes SiteContext with the seeder's default
        // primary_locale (config('app.locale'), which is 'en' on CI). Re-prime
        // it after the update so currentSite() reflects the test's ja value.
        app(\App\Contracts\Site\SiteContextInterface::class)->setCurrent(1);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        putenv('INSTALLED=false');
        parent::tearDown();
    }

    public function test_root_url_does_not_redirect_to_locale_prefix(): void
    {
        $response = $this->get('/');

        $this->assertNotEquals(302, $response->getStatusCode(), 'v0.1.0 must not auto-redirect / to /{locale}/');
    }

    public function test_locale_less_unknown_url_returns_404_not_redirect(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(404);
    }

    public function test_locale_prefixed_urls_are_404_in_v0_1_0(): void
    {
        // /{locale}/... URLs are reserved for the future multilingual plugin.
        // Until then, they 404 (no route registered inside a locale group).
        $this->get('/ja')->assertStatus(404);
        $this->get('/en')->assertStatus(404);
    }

    public function test_admin_url_is_unaffected(): void
    {
        $response = $this->get(route('admin.login', [], false));

        // Admin must respond without being captured by any locale machinery.
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_locale_helper_returns_site_default(): void
    {
        $this->assertSame('ja', LocaleHelper::getSiteDefaultLocale());
    }

    public function test_locale_helper_supports_only_ja_and_en_in_v0_1_0(): void
    {
        $this->assertTrue(LocaleHelper::isSupported('ja'));
        $this->assertTrue(LocaleHelper::isSupported('en'));
        $this->assertFalse(LocaleHelper::isSupported('de'));
        $this->assertFalse(LocaleHelper::isSupported(''));
    }

    public function test_setfrontlocale_resolves_url_first_when_locale_segment_present(): void
    {
        // SetFrontLocale is a unit-testable middleware. It runs only when
        // the multilingual plugin attaches it to a /{locale}/ route group;
        // here we exercise the resolution directly.
        $request = Request::create('/ja/about', 'GET');
        $middleware = new SetFrontLocale();

        $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('ja', app()->getLocale());
    }

    public function test_setfrontlocale_falls_back_to_cookie_when_no_url_locale(): void
    {
        $request = Request::create('/about', 'GET');
        $request->cookies->set(LocaleHelper::COOKIE_NAME, 'en');
        $middleware = new SetFrontLocale();

        $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('en', app()->getLocale());
    }

    public function test_setfrontlocale_falls_back_to_site_default_when_no_signal(): void
    {
        $request = Request::create('/about', 'GET');
        $request->headers->set('Accept-Language', '');
        $middleware = new SetFrontLocale();

        $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('ja', app()->getLocale());
    }
}
