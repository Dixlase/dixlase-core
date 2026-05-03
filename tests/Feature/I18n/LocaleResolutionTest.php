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
use Database\Seeders\SitesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

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

        // Force the primary site's locale to a known value so tests don't
        // depend on the operator's local environment.
        DB::table('sites')->where('is_primary', true)->update(['primary_locale' => 'ja']);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        putenv('INSTALLED=false');
        parent::tearDown();
    }

    public function test_locale_less_url_redirects_to_site_primary_locale(): void
    {
        // Strip Accept-Language so the resolver falls through to Site.primary_locale.
        $response = $this->withHeader('Accept-Language', '')->get('/about');

        $response->assertStatus(302);
        $this->assertStringContainsString('/ja/about', $response->headers->get('location') ?? '');
    }

    public function test_root_url_redirects_to_site_primary_locale(): void
    {
        $response = $this->withHeader('Accept-Language', '')->get('/');

        $response->assertStatus(302);
        $this->assertStringContainsString('/ja', $response->headers->get('location') ?? '');
    }

    public function test_cookie_overrides_site_primary_locale_in_fallback(): void
    {
        $response = $this->withCookie(LocaleHelper::COOKIE_NAME, 'en')->get('/about');

        $response->assertStatus(302);
        $this->assertStringContainsString('/en/about', $response->headers->get('location') ?? '');
    }

    public function test_accept_language_header_used_when_no_cookie(): void
    {
        $response = $this->withHeader('Accept-Language', 'en-US,en;q=0.9')->get('/about');

        $response->assertStatus(302);
        $this->assertStringContainsString('/en/about', $response->headers->get('location') ?? '');
    }

    public function test_path_already_locale_prefixed_returns_404_not_redirect(): void
    {
        $response = $this->get('/ja/this-route-does-not-exist');

        $response->assertStatus(404);
    }

    public function test_unsupported_locale_in_path_falls_back_to_redirect(): void
    {
        // /de/foo: 'de' isn't supported, so the locale group doesn't match
        // and the fallback redirects to /{site_default}/de/foo.
        $response = $this->withHeader('Accept-Language', '')->get('/de/foo');

        $response->assertStatus(302);
        $this->assertStringContainsString('/ja/de/foo', $response->headers->get('location') ?? '');
    }

    public function test_admin_url_is_not_captured_by_locale_fallback(): void
    {
        // Admin URLs must reach their own routes, not get caught by the
        // locale.fallback redirect. Use the named route so the test
        // works regardless of the configured admin prefix.
        $adminLogin = route('admin.login', [], false);
        $adminPath = ltrim(parse_url($adminLogin, PHP_URL_PATH), '/');

        $response = $this->get($adminLogin);

        $location = (string) $response->headers->get('location', '');
        $this->assertStringNotContainsString('/ja/'.$adminPath, $location, 'Admin URL must not be hijacked by locale fallback redirect.');
        $this->assertStringNotContainsString('/en/'.$adminPath, $location, 'Admin URL must not be hijacked by locale fallback redirect.');
    }

    public function test_locale_prefix_loads_with_correct_app_locale(): void
    {
        // Front pages depend on the active theme. We can't render here,
        // but we can confirm app()->getLocale() is set by SetFrontLocale.
        $this->get('/ja');
        $this->assertSame('ja', app()->getLocale());

        $this->get('/en');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_url_defaults_locale_is_set_after_setfrontlocale(): void
    {
        $this->get('/ja');

        // After SetFrontLocale runs, route() should preserve the resolved
        // locale automatically.
        $this->assertStringContainsString('/ja', route('welcome'));
        $this->assertStringContainsString('/en', route('welcome', ['locale' => 'en']));
    }
}
