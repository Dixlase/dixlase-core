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

declare(strict_types=1);

namespace Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;

/**
 * Guards that a theme's route files are registered inside the `web` group.
 *
 * ThemeServiceProvider::loadThemeRoutes() runs at provider boot, outside any
 * route group, so whatever middleware the theme's routes get has to be named
 * there explicitly. It named `admin.ip` and `admin.no-cache` but not `web`,
 * which left the theme's admin screens without StartSession or
 * EncryptCookies: `auth:member` saw no session, treated a signed-in operator
 * as a guest and bounced them to the login screen, which sent them on to the
 * dashboard. The theme settings page was unreachable (issue #465). The front
 * include had the same defect, latent only because no bundled theme ships a
 * routes/web.php.
 *
 * This is a source-level guard, not a behavioural one. A behavioural test
 * cannot reach these routes: loadThemeRoutes() needs `theme_settings`
 * .enabled_theme_id at provider boot, which a RefreshDatabase test never has,
 * so the group is simply never registered under test.
 *
 * Pure file reads: no framework boot, no database.
 */
class ThemeRouteMiddlewareTest extends TestCase
{
    private const REPO_ROOT = __DIR__.'/../../..';

    /**
     * The stack a theme's admin screens share with core's own admin routes
     * (routes/admin.php, wrapped in `web` by bootstrap/app.php's withRouting)
     * and with a plugin's admin routes (PluginServiceProvider).
     */
    private const SHARED_ADMIN_MIDDLEWARE = [
        "'web'",
        "'admin.ip'",
        "'admin.no-cache'",
        "'auth:member'",
        "'member.active'",
        "'verified'",
        "'log.admin.activity'",
    ];

    public function test_theme_admin_routes_are_registered_with_the_web_group(): void
    {
        $this->assertStringContainsString(
            "->middleware(['web', 'admin.ip', 'admin.no-cache'])",
            $this->themeProvider(),
            'A theme\'s admin routes must lead with the `web` group, or auth:member sees no session.'
        );
    }

    public function test_theme_front_routes_are_registered_with_the_web_group(): void
    {
        $source = $this->themeProvider();

        $this->assertStringContainsString(
            "\\Route::middleware('web')->group(\$webRoutePath);",
            $source,
            'A theme\'s front routes must go through the `web` group, as a plugin\'s do.'
        );
        $this->assertStringNotContainsString(
            'include $webRoutePath;',
            $source,
            'A bare include registers the theme\'s front routes with no middleware at all.'
        );
    }

    public function test_the_shared_admin_middleware_appear_in_both_providers(): void
    {
        $theme = $this->themeProvider();
        $plugin = $this->pluginProvider();

        foreach (self::SHARED_ADMIN_MIDDLEWARE as $middleware) {
            $this->assertStringContainsString(
                $middleware,
                $theme,
                "ThemeServiceProvider must apply {$middleware} to a theme's admin routes."
            );
            $this->assertStringContainsString(
                $middleware,
                $plugin,
                "PluginServiceProvider must apply {$middleware} to a plugin's admin routes."
            );
        }
    }

    private function themeProvider(): string
    {
        return $this->read('app/Providers/ThemeServiceProvider.php');
    }

    private function pluginProvider(): string
    {
        return $this->read('app/Providers/PluginServiceProvider.php');
    }

    private function read(string $relativePath): string
    {
        $path = self::REPO_ROOT.'/'.$relativePath;
        $contents = file_get_contents($path);

        $this->assertIsString($contents, "Could not read {$relativePath}.");

        return $contents;
    }
}
