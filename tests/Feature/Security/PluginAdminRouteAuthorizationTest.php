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
 */
class PluginAdminRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

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
            $this->passesGate('dixlase-pages::admin.pages.index', 'GET', 'DixlasePages'),
            'An unauthenticated request must never pass the plugin admin gate.'
        );
    }

    /**
     * The headline regression: a low-privilege member reaching a plugin write
     * endpoint. DixlasePages::store is the one that mattered most, because the
     * same controller exposes a preview action that renders Blade from request
     * input -- an authorization gap there is a path to code execution, not just
     * unwanted edits.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function lowPrivilegeRoutes(): array
    {
        return [
            'pages list' => ['dixlase-pages::admin.pages.index', 'GET'],
            'pages create form' => ['dixlase-pages::admin.pages.create', 'GET'],
            'pages store' => ['dixlase-pages::admin.pages.store', 'POST'],
            'pages destroy' => ['dixlase-pages::admin.pages.destroy', 'DELETE'],
            'pages preview' => ['dixlase-pages::admin.pages.preview', 'POST'],
        ];
    }

    #[DataProvider('lowPrivilegeRoutes')]
    public function test_contributor_is_refused_everywhere(string $routeName, string $method): void
    {
        Auth::login(Member::factory()->create(['role' => MemberRole::CONTRIBUTOR]));

        $this->assertFalse(
            $this->passesGate($routeName, $method, 'DixlasePages'),
            "A contributor must not reach {$routeName}."
        );
    }

    public function test_admin_still_reaches_read_and_write_routes(): void
    {
        Auth::login(Member::factory()->create(['role' => MemberRole::ADMIN]));

        foreach (self::lowPrivilegeRoutes() as $label => [$routeName, $method]) {
            $this->assertTrue(
                $this->passesGate($routeName, $method, 'DixlasePages'),
                "The gate must not lock an ADMIN out of {$label}."
            );
        }
    }

    /**
     * When the gate landed, DixlasePages declared only its read screens, so an
     * editor could open the page list and the create form and then take a 403
     * on save. The plugin has since declared its write routes, which is the
     * way the asymmetry was always meant to be resolved: the plugin says what
     * an editor may do, rather than the gate guessing which read key a POST
     * belongs to.
     *
     * The boundary moved rather than disappeared. Authoring and the deletions
     * that land in the trash are EDITOR; permanent deletion is not.
     */
    public function test_editor_reaches_declared_authoring_routes(): void
    {
        Auth::login(Member::factory()->create(['role' => MemberRole::EDITOR]));

        foreach ([
            ['dixlase-pages::admin.pages.index', 'GET'],
            ['dixlase-pages::admin.pages.store', 'POST'],
            ['dixlase-pages::admin.pages.update', 'PUT'],
            ['dixlase-pages::admin.pages.destroy', 'DELETE'],
            ['dixlase-pages::admin.pages.trash.restore', 'POST'],
        ] as [$routeName, $method]) {
            $this->assertTrue(
                $this->passesGate($routeName, $method, 'DixlasePages'),
                "{$routeName} is declared EDITOR in the plugin roles.php; the gate must honour it."
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
            ['dixlase-pages::admin.pages.trash.empty', 'POST'],
            ['dixlase-pages::admin.pages.trash.force-destroy', 'DELETE'],
            ['dixlase-pages::admin.pages.revisions.protect', 'POST'],
            ['dixlase-pages::admin.pages.settings.update', 'PUT'],
        ] as [$routeName, $method]) {
            $this->assertFalse(
                $this->passesGate($routeName, $method, 'DixlasePages'),
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
