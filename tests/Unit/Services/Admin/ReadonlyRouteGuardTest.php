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

namespace Tests\Unit\Services\Admin;

use App\Contracts\PluginIntegration\ReadonlyRouteProviderInterface;
use App\Enums\MemberRole;
use App\Models\Member;
use App\Services\Admin\ReadonlyRouteGuard;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mockery;
use Tests\TestCase;

/**
 * ReadonlyRouteGuard aggregates the blocked route names from every registered
 * ReadonlyRouteProviderInterface, falls back to its built-in list while none
 * is registered, and answers the two matchers + the active/applies-to helpers
 * the middleware and the admin views rely on.
 */
class ReadonlyRouteGuardTest extends TestCase
{
    protected PluginPermissionService|Mockery\MockInterface $permissionService;

    protected PluginServiceResolver $resolver;

    protected ReadonlyRouteGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = Mockery::mock(PluginPermissionService::class);
        $this->permissionService->shouldReceive('check')->andReturn(true)->byDefault();

        $this->resolver = new PluginServiceResolver($this->permissionService);
        $this->app->instance(PluginServiceResolver::class, $this->resolver);

        $this->guard = new ReadonlyRouteGuard($this->resolver);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_falls_back_to_the_builtin_list_when_no_provider_is_registered(): void
    {
        $names = $this->guard->routeNames();

        $this->assertCount(56, $names);
        $this->assertContains('admin.profile.password.update', $names);
        $this->assertContains('admin.settings.systems.backup.*', $names);
    }

    public function test_falls_back_when_the_only_provider_is_unavailable(): void
    {
        $this->resolver->register(
            ReadonlyRouteProviderInterface::class,
            $this->makeProvider('disabled', ['foo.update'], available: false)
        );

        $this->assertContains('admin.profile.password.update', $this->guard->routeNames());
    }

    public function test_a_registered_provider_replaces_the_builtin_list(): void
    {
        $this->resolver->register(
            ReadonlyRouteProviderInterface::class,
            $this->makeProvider('demo', ['foo.update', 'bar.*'])
        );

        $this->assertSame(['foo.update', 'bar.*'], $this->guard->routeNames());
        $this->assertTrue($this->guard->lockedForRequest('bar.baz'));
        $this->assertFalse($this->guard->lockedForRequest('admin.profile.password.update'));
    }

    public function test_multiple_providers_are_merged_and_deduped(): void
    {
        $this->resolver->register(
            ReadonlyRouteProviderInterface::class,
            $this->makeProvider('a', ['foo.update', 'shared.update'])
        );
        $this->resolver->register(
            ReadonlyRouteProviderInterface::class,
            $this->makeProvider('b', ['bar.update', 'shared.update'])
        );

        $names = $this->guard->routeNames();

        $this->assertEqualsCanonicalizing(['foo.update', 'shared.update', 'bar.update'], $names);
        $this->assertCount(3, $names, 'duplicate entries must be deduped');
    }

    public function test_request_matcher_is_strict(): void
    {
        $this->assertTrue($this->guard->lockedForRequest('admin.profile.password.update'));
        // ".*" entry: equals the prefix, or nested under it.
        $this->assertTrue($this->guard->lockedForRequest('admin.settings.systems.backup'));
        $this->assertTrue($this->guard->lockedForRequest('admin.settings.systems.backup.run'));
        // Strict: a parent of a blocked route is NOT itself blocked for requests.
        $this->assertFalse($this->guard->lockedForRequest('admin.settings.systems'));
        $this->assertFalse($this->guard->lockedForRequest('admin.profile.password'));
        $this->assertFalse($this->guard->lockedForRequest('admin.profile.password.update.extra'));
    }

    public function test_menu_matcher_is_looser_and_bidirectional(): void
    {
        // A parent page of a blocked route is marked (so the menu shows the eye icon).
        $this->assertTrue($this->guard->menuLocked('admin.profile.password'));
        // Parent of a ".*" namespace is marked too.
        $this->assertTrue($this->guard->menuLocked('admin.settings.systems'));
        // Unrelated routes are not.
        $this->assertFalse($this->guard->menuLocked('admin.profile.appearance'));
        $this->assertFalse($this->guard->menuLocked(null));
        $this->assertFalse($this->guard->menuLocked(''));
    }

    public function test_is_active_follows_demo_mode(): void
    {
        config(['dixlase.demo_mode' => false]);
        $this->assertFalse($this->guard->isActive());

        config(['dixlase.demo_mode' => true]);
        $this->assertTrue($this->guard->isActive());
    }

    public function test_exempt_and_applies_to(): void
    {
        config(['dixlase.demo_mode' => true]);

        $admin = new Member(['role' => MemberRole::ADMIN]);
        $super = new Member(['role' => MemberRole::SUPER_ADMIN]);

        // Super admin bypasses the guard; a plain admin is subject to it.
        $this->assertTrue($this->guard->isExempt($super));
        $this->assertFalse($this->guard->isExempt($admin));
        // An anonymous request is never exempt (the middleware still enforces it).
        $this->assertFalse($this->guard->isExempt(null));

        // The admin read-only cues apply only to a signed-in non-super-admin.
        $this->assertTrue($this->guard->appliesTo($admin));
        $this->assertFalse($this->guard->appliesTo($super));
        $this->assertFalse($this->guard->appliesTo(null));

        // ...and never when demo mode is off.
        config(['dixlase.demo_mode' => false]);
        $this->assertFalse($this->guard->appliesTo($admin));
    }

    public function test_should_block_covers_the_full_request_order(): void
    {
        config(['dixlase.demo_mode' => true]);

        $admin = new Member(['role' => MemberRole::ADMIN]);
        $super = new Member(['role' => MemberRole::SUPER_ADMIN]);

        // POST to a blocked route as a plain admin → blocked.
        $this->assertTrue($this->guard->shouldBlock($this->request('POST', 'admin.profile.password.update', $admin)));
        // Read-only methods always pass.
        $this->assertFalse($this->guard->shouldBlock($this->request('GET', 'admin.profile.password.update', $admin)));
        // Super admin bypasses.
        $this->assertFalse($this->guard->shouldBlock($this->request('POST', 'admin.profile.password.update', $super)));
        // Route-less requests pass.
        $this->assertFalse($this->guard->shouldBlock($this->request('POST', null, $admin)));
        // A non-blocked route passes.
        $this->assertFalse($this->guard->shouldBlock($this->request('POST', 'admin.profile.appearance.update', $admin)));

        // Demo mode off → nothing is blocked.
        config(['dixlase.demo_mode' => false]);
        $this->assertFalse($this->guard->shouldBlock($this->request('POST', 'admin.profile.password.update', $admin)));
    }

    private function request(string $method, ?string $routeName, ?Member $user): Request
    {
        $request = Request::create('/_probe', $method);
        $route = new Route([$method], '/_probe', []);
        if ($routeName !== null) {
            $route->name($routeName);
        }
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    /**
     * @param  list<string>  $names
     */
    protected function makeProvider(string $slug, array $names, bool $available = true): ReadonlyRouteProviderInterface
    {
        return new class($slug, $names, $available) implements ReadonlyRouteProviderInterface
        {
            /**
             * @param  list<string>  $names
             */
            public function __construct(
                private string $slug,
                private array $names,
                private bool $available,
            ) {}

            public function getPluginSlug(): string
            {
                return $this->slug;
            }

            public function isCapabilityAvailable(): bool
            {
                return $this->available;
            }

            public function getReadonlyRouteNames(): array
            {
                return $this->names;
            }
        };
    }
}
