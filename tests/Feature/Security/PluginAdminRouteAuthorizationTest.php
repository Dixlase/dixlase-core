<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
use App\Http\Middleware\EnsurePluginAdminAccess;
use App\Models\Member;
use App\Services\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Plugin admin routes are registered by PluginServiceProvider behind
 * authentication only. Until EnsurePluginAdminAccess was added they had no
 * authorization gate at all, so any verified member -- contributor,
 * receptionist -- could reach all 72 of them: create and delete pages,
 * rewrite navigation, change SEO output. The role restrictions plugins
 * declare in config/admin/roles.php were read only by CheckMenuAccess, which
 * is not applied to plugin routes, so they governed sidebar visibility and
 * nothing more.
 *
 * These tests pin the gate itself. They drive the middleware directly rather
 * than issuing HTTP requests because the route table depends on which plugins
 * happen to be installed and enabled in the database, which is not something a
 * security regression test should be at the mercy of.
 *
 * For the same reason they judge a registered fixture rather than a real
 * plugin's roles.php. An earlier version asserted against DixlasePages, which
 * made this file fail whenever that plugin's declarations changed -- and since
 * plugin CI checks Core out at main and Core CI checks plugins out at main, a
 * paired change could not go green on either side until the other had merged.
 * What a plugin grants belongs to that plugin's own tests; what the gate does
 * with a declaration belongs here.
 *
 * Note that registerPlugin() is looked up by exact key, so these tests cover
 * the gate's own walk from the most specific route segment to the least, not
 * the nested-array walk that reads a roles.php from disk. That one is covered
 * directly in PermissionRegistryNestedKeyTest.
 */
class PluginAdminRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A directory name with no plugins/ directory behind it, so
     * resolvePluginRolesPath() finds nothing and only the registered map
     * below is consulted.
     */
    private const FIXTURE = 'FixtureAuthorizationPlugin';

    protected function setUp(): void
    {
        parent::setUp();

        // Shaped like a real plugin: read screens and authoring an editor may
        // reach, a parent screen that is itself a route, a destructive child
        // under that parent, and a settings key only a super admin may edit.
        PermissionRegistry::registerPlugin(self::FIXTURE, [
            'things.index' => ['access_roles' => MemberRole::EDITOR->value, 'view_roles' => MemberRole::EDITOR->value],
            'things.create' => ['access_roles' => MemberRole::EDITOR->value, 'view_roles' => MemberRole::EDITOR->value],
            'things.store' => ['access_roles' => MemberRole::EDITOR->value, 'view_roles' => MemberRole::EDITOR->value],
            'things.destroy' => ['access_roles' => MemberRole::EDITOR->value, 'view_roles' => MemberRole::EDITOR->value],
            'things.trash' => ['access_roles' => MemberRole::EDITOR->value, 'view_roles' => MemberRole::EDITOR->value],
            'things.trash.empty' => ['access_roles' => MemberRole::ADMIN->value, 'view_roles' => MemberRole::ADMIN->value],
            'things.settings' => ['access_roles' => MemberRole::SUPER_ADMIN->value, 'view_roles' => MemberRole::ADMIN->value],
        ]);
    }

    protected function tearDown(): void
    {
        // registerPlugin() writes to a static, which would otherwise leak into
        // every test that runs after this one in the same process.
        PermissionRegistry::unregisterPlugin(self::FIXTURE);

        parent::tearDown();
    }

    /**
     * Run a fabricated plugin admin route through the gate.
     *
     * @return bool true when the request passed the gate
     */
    private function passesGate(string $routeName, string $method, string $pluginDirectory): bool
    {
        $request = Request::create('/admin/whatever', $method);
        $route = (new Route([$method], '/admin/whatever', []))->name($routeName);
        $request->setRouteResolver(fn () => $route);

        try {
            (new EnsurePluginAdminAccess())->handle(
                $request,
                fn () => response('ok'),
                $pluginDirectory
            );

            return true;
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode(), 'The gate should refuse with 403.');

            return false;
        }
    }

    public function test_unauthenticated_request_is_refused(): void
    {
        $this->assertFalse(
            $this->passesGate('fixture::admin.things.index', 'GET', self::FIXTURE),
            'An unauthenticated request must never pass the plugin admin gate.'
        );
    }

    /**
     * The headline regression: a low-privilege member reaching a plugin write
     * endpoint. A store action was the one that mattered most in practice,
     * because DixlasePages exposed a preview action on the same controller
     * that rendered Blade from request input -- an authorization gap there is
     * a path to code execution, not just unwanted edits.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function lowPrivilegeRoutes(): array
    {
        return [
            'list' => ['fixture::admin.things.index', 'GET'],
            'create form' => ['fixture::admin.things.create', 'GET'],
            'store' => ['fixture::admin.things.store', 'POST'],
            'destroy' => ['fixture::admin.things.destroy', 'DELETE'],
            'empty trash' => ['fixture::admin.things.trash.empty', 'POST'],
        ];
    }

    #[DataProvider('lowPrivilegeRoutes')]
    public function test_contributor_is_refused_everywhere(string $routeName, string $method): void
    {
        Auth::login(Member::factory()->create(['role' => MemberRole::CONTRIBUTOR]));

        $this->assertFalse(
            $this->passesGate($routeName, $method, self::FIXTURE),
            "A contributor must not reach {$routeName}."
        );
    }

    public function test_admin_still_reaches_read_and_write_routes(): void
    {
        Auth::login(Member::factory()->create(['role' => MemberRole::ADMIN]));

        foreach (self::lowPrivilegeRoutes() as $label => [$routeName, $method]) {
            $this->assertTrue(
                $this->passesGate($routeName, $method, self::FIXTURE),
                "The gate must not lock an ADMIN out of {$label}."
            );
        }
    }

    /**
     * When the gate landed, plugins declared only their read screens, so an
     * editor could open a list and a create form and then take a 403 on save.
     * The way out was always for the plugin to declare its write routes --
     * the plugin says what an editor may do, rather than the gate guessing
     * which read key a POST belongs to.
     *
     * What this pins is that a declaration on a write route is honoured at
     * all. Whether a given plugin should grant one is that plugin's decision,
     * tested in that plugin's repository.
     */
    public function test_editor_reaches_declared_authoring_routes(): void
    {
        Auth::login(Member::factory()->create(['role' => MemberRole::EDITOR]));

        foreach ([
            ['fixture::admin.things.index', 'GET'],
            ['fixture::admin.things.store', 'POST'],
            ['fixture::admin.things.destroy', 'DELETE'],
            ['fixture::admin.things.trash', 'GET'],
        ] as [$routeName, $method]) {
            $this->assertTrue(
                $this->passesGate($routeName, $method, self::FIXTURE),
                "{$routeName} is declared EDITOR; the gate must honour it, on writes as well as reads."
            );
        }
    }

    /**
     * Deletions an editor can take back are theirs; the ones that destroy data
     * outright are not. These sit under `pages.trash`, which an editor may
     * open, so they are the pair most likely to drift back to EDITOR by
     * inheritance.
     */
    public function test_editor_cannot_delete_permanently(): void
    {
        Auth::login(Member::factory()->create(['role' => MemberRole::EDITOR]));

        foreach ([
            // Declared ADMIN, and sitting under a parent the editor may open.
            ['fixture::admin.things.trash.empty', 'POST'],
            // Undeclared, so it inherits things.settings (SUPER_ADMIN to edit).
            ['fixture::admin.things.settings.update', 'PUT'],
        ] as [$routeName, $method]) {
            $this->assertFalse(
                $this->passesGate($routeName, $method, self::FIXTURE),
                "{$routeName} destroys data or widens rights and must stay above EDITOR."
            );
        }
    }

    /**
     * A route whose name resolves to nothing declared must not be waved
     * through. This is the property that protects plugins Core has never seen.
     */
    public function test_unknown_plugin_route_requires_admin(): void
    {
        Auth::login(Member::factory()->create(['role' => MemberRole::EDITOR]));
        $this->assertFalse(
            $this->passesGate('some-plugin::admin.anything.at.all', 'GET', 'NoSuchPluginDirectory'),
            'An unrecognised plugin route must fall back to requiring ADMIN.'
        );

        Auth::logout();
        Auth::login(Member::factory()->create(['role' => MemberRole::ADMIN]));
        $this->assertTrue(
            $this->passesGate('some-plugin::admin.anything.at.all', 'GET', 'NoSuchPluginDirectory'),
            'The ADMIN fallback must remain usable, otherwise every unknown plugin breaks.'
        );
    }

    /**
     * The gate is only worth anything if it is actually attached. Guards the
     * provider wiring, which is the thing that was missing.
     */
    public function test_plugin_route_loader_attaches_the_gate(): void
    {
        $provider = file_get_contents(base_path('app/Providers/PluginServiceProvider.php'));

        $this->assertStringContainsString(
            "'plugin.admin.access:'.\$plugin->directory",
            $provider,
            'PluginServiceProvider must attach the authorization gate to plugin admin routes.'
        );
    }
}
