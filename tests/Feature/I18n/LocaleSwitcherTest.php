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
use Tests\TestCase;

class LocaleSwitcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
        putenv('INSTALLED=true');

        $this->seed(SitesSeeder::class);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        putenv('INSTALLED=false');
        parent::tearDown();
    }

    public function test_post_switch_writes_cookie_and_redirects_with_locale_swapped(): void
    {
        $response = $this->post('/locale/switch', [
            'locale' => 'en',
            'redirect' => '/ja/about?foo=1',
        ]);

        $response->assertStatus(302);
        $this->assertStringContainsString('/en/about', $response->headers->get('location') ?? '');
        $this->assertStringContainsString('foo=1', $response->headers->get('location') ?? '');

        // The locale cookie is stored as plaintext (excepted from
        // EncryptCookies in bootstrap/app.php), so disable decryption.
        $response->assertCookie(LocaleHelper::COOKIE_NAME, 'en', encrypted: false);
    }

    public function test_post_switch_inserts_locale_when_path_has_none(): void
    {
        $response = $this->post('/locale/switch', [
            'locale' => 'ja',
            'redirect' => '/about',
        ]);

        $response->assertStatus(302);
        $this->assertStringContainsString('/ja/about', $response->headers->get('location') ?? '');
    }

    public function test_post_switch_uses_referer_when_redirect_param_missing(): void
    {
        $response = $this->withHeader('Referer', config('app.url').'/ja/about')
            ->post('/locale/switch', ['locale' => 'en']);

        $response->assertStatus(302);
        $this->assertStringContainsString('/en/about', $response->headers->get('location') ?? '');
    }

    public function test_post_switch_falls_back_to_locale_root_when_no_referer(): void
    {
        $response = $this->post('/locale/switch', ['locale' => 'en']);

        $response->assertStatus(302);
        $this->assertStringContainsString('/en', $response->headers->get('location') ?? '');
    }

    public function test_post_switch_rejects_off_host_redirect(): void
    {
        $response = $this->post('/locale/switch', [
            'locale' => 'en',
            'redirect' => 'https://evil.example.com/ja/about',
        ]);

        $response->assertStatus(302);
        $location = $response->headers->get('location') ?? '';
        $this->assertStringNotContainsString('evil.example.com', $location);
    }

    public function test_post_switch_rejects_unsupported_locale(): void
    {
        $response = $this->from('/ja/about')->post('/locale/switch', [
            'locale' => 'de',
        ]);

        // back() with errors → 302 to /ja/about, no locale cookie set
        $response->assertStatus(302);
        $response->assertSessionHasErrors('locale');
        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertNotSame(LocaleHelper::COOKIE_NAME, $cookie->getName());
        }
    }

    public function test_setfrontlocale_does_not_write_cookie_on_url_visit(): void
    {
        // Visiting /ja/ should NOT set the dixlase_locale cookie. Only
        // the explicit switch endpoint persists the choice. (We can't
        // assertOk because the front theme isn't registered in the test
        // environment, but the middleware still runs and the cookie
        // assertion is what we care about.)
        $response = $this->get('/ja');

        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertNotSame(LocaleHelper::COOKIE_NAME, $cookie->getName(), 'SetFrontLocale must not write the locale cookie.');
        }
    }
}
