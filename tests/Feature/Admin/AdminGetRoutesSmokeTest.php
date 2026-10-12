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

namespace Tests\Feature\Admin;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Media;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Core admin GET smoke test.
 *
 * Opens every admin GET route core registers and asserts the server
 * does not return 5xx, for both the ADMIN and the EDITOR role.
 *
 * This catches the class of release regression that showed up in the
 * Inquiry 0.3.0 incident (a controller / view / lang-key crash that
 * only surfaces during the real request lifecycle: middleware stack →
 * controller → view resolution → Blade compile). Unit tests on route
 * config / permissions do not fire that lifecycle, so a page can
 * declare clean and still crash on open.
 *
 * This is complementary to the existing hard-coded smoke tests under
 * `tests/Feature/Admin/Controllers/`:
 *
 *   - The hard-coded ones assert role-boundary behaviour as
 *     SUPER_ADMIN and bypass CSP / CheckInstallationReady /
 *     EnsureEmailIsVerified. They stay as-is.
 *   - This one is route-collection-driven, so a new admin screen
 *     added later is covered without touching the test, and it runs
 *     under the full middleware stack so lifecycle-only failures do
 *     not slip through.
 *
 *   - **In scope**: status < 500 for every admin GET route under
 *     the `admin.*` name prefix, for ADMIN and EDITOR.
 *   - **Out of scope**: 200 vs 403 role boundaries (covered by the
 *     hard-coded smoke tests and permission-config tests); POST /
 *     PATCH / DELETE write flows; MySQL vs SQLite behaviour
 *     differences; CSP / inline-handler scanning.
 */
class AdminGetRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** Route-name prefix core stamps on every admin route. */
    private const ROUTE_NAME_PREFIX = 'admin.';

    /**
     * Routes we intentionally skip. These are either:
     *   - unauthenticated (login / logout / password-reset-with-token /
     *     email-verification-with-signed-hash); hitting them under
     *     actingAs() does not represent a real admin flow,
     *   - signed-URL-only (verification.verify), which cannot be
     *     generated without the owning user,
     *   - or exercised by their own dedicated feature tests already
     *     (e.g. the login lockout test).
     *
     * Everything else is auto-discovered, so a new admin GET screen
     * added later is picked up without a change here.
     */
    private const SKIP_ROUTES = [
        'admin.login',
        'admin.logout',
        'admin.password.request',
        'admin.password.reset',
        'admin.verification.notice',
        'admin.verification.verify',
    ];

    private Member $admin;

    private Member $editor;

    private Media $media;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // Core's AppServiceProvider registers these namespaces at boot,
        // but boot runs with INSTALLED=false in the test harness, so the
        // namespaces are not populated by the time setUp runs. Register
        // them here so admin views resolve against the real view tree.
        \Illuminate\Support\Facades\View::addNamespace(
            'admin',
            [resource_path('views/'.config('themes.admin_theme', 'admin'))],
        );
        \Illuminate\Support\Facades\View::addNamespace(
            'components',
            [resource_path('views/components')],
        );
        \Illuminate\Support\Facades\View::addNamespace(
            'layouts',
            [resource_path('views/layouts')],
        );
        \Illuminate\Support\Facades\View::addNamespace(
            'themes',
            [__DIR__.'/../../stubs/theme'],
        );

        // Pin a known-good 32-byte AES-256-CBC key regardless of what
        // the harness supplied; actingAs() writes to the session which
        // goes through the Encrypter, and some test envs supply an
        // APP_KEY whose decoded length does not satisfy AES-256-CBC.
        $rawKey = random_bytes(32);
        config([
            'app.key' => 'base64:'.base64_encode($rawKey),
            'app.cipher' => 'AES-256-CBC',
        ]);
        $this->app->instance('encrypter', new \Illuminate\Encryption\Encrypter($rawKey, 'AES-256-CBC'));

        $this->admin = $this->makeMember('admin-user', MemberRole::ADMIN);
        $this->editor = $this->makeMember('editor-user', MemberRole::EDITOR);
        $this->media = Media::factory()->create();
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_no_admin_get_route_returns_5xx_for_admin_role(): void
    {
        $urls = $this->adminGetRouteUrls($this->admin);

        foreach ($urls as $name => $url) {
            $response = $this->actingAs($this->admin, 'member')->get($url);
            // `getStatusCode()` works on TestResponse AND on the raw
            // Symfony `StreamedResponse` (file downloads, media
            // preview), unlike TestResponse->status() which only
            // exists on wrapped responses.
            $status = $response->baseResponse->getStatusCode();

            $this->assertLessThan(
                500,
                $status,
                "GET {$url} ({$name}) returned {$status} as admin",
            );
        }
    }

    public function test_no_admin_get_route_returns_5xx_for_editor_role(): void
    {
        $urls = $this->adminGetRouteUrls($this->editor);

        foreach ($urls as $name => $url) {
            $response = $this->actingAs($this->editor, 'member')->get($url);
            $status = $response->baseResponse->getStatusCode();

            $this->assertLessThan(
                500,
                $status,
                "GET {$url} ({$name}) returned {$status} as editor",
            );
        }
    }

    private function makeMember(string $slug, MemberRole $role): Member
    {
        return Member::create([
            'account_name' => $slug,
            'display_name' => ucfirst($slug),
            'email' => $slug.'@example.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => MemberStatus::Active,
            'two_fa_mode' => 0,
        ]);
    }

    /**
     * Discover every admin GET route and fill its placeholders.
     *
     * Placeholders in core admin routes are context-dependent, so each
     * one is resolved to a value whose shape the route expects: `{member}`
     * is the seeded admin's id, `{media}` the seeded Media id, and the
     * free-form slug / token / hash / audit / backup / directory /
     * source / type / id placeholders use arbitrary values the
     * controllers' 404-branch handles safely (404 is < 500, which is all
     * this suite checks).
     *
     * @param  Member  $member  Used so `{member}` resolves to an id the
     *                          acting user is allowed to open on their
     *                          own profile pages.
     * @return array<string, string> Map of route name to request URL.
     */
    private function adminGetRouteUrls(Member $member): array
    {
        $urls = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($name === null || ! str_starts_with($name, self::ROUTE_NAME_PREFIX)) {
                continue;
            }
            if (in_array($name, self::SKIP_ROUTES, true)) {
                continue;
            }
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();
            $uri = str_replace('{member}', (string) $member->id, $uri);
            $uri = str_replace('{media}', (string) $this->media->id, $uri);

            // Everything else is a free-form string / numeric id and
            // controllers short-circuit on anything they can't find.
            $uri = str_replace(
                [
                    '{slug}',
                    '{token}',
                    '{hash}',
                    '{audit}',
                    '{backup}',
                    '{directory}',
                    '{source}',
                    '{type}',
                    '{type?}',
                    '{id}',
                    '{Dir}',
                    '{credentialId}',
                    '{restore}',
                ],
                [
                    'nonexistent-slug',
                    'nonexistent-token',
                    'nonexistent-hash',
                    '1',
                    '1',
                    'nonexistent-directory',
                    'local',
                    'plugin',
                    'app',
                    '1',
                    'DixlaseOnePage',
                    'nonexistent-credential',
                    '1',
                ],
                $uri,
            );
            $urls[$name] = '/'.ltrim($uri, '/');
        }

        $this->assertNotEmpty(
            $urls,
            'No admin GET routes were discovered; is the admin.* name prefix still correct?',
        );

        return $urls;
    }
}
