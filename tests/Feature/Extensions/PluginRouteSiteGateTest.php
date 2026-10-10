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

namespace Tests\Feature\Extensions;

use App\Helpers\PluginHelper;
use App\Models\Plugin;
use App\Models\Site;
use App\Models\SitePluginActivation;
use App\Providers\PluginServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Plugin web routes and legacy routes/api.php answer only on sites where
 * the plugin is active (#494)
 *
 * Before the fix routes/web.php was mounted with `web` only and the
 * deprecated routes/api.php with no middleware at all, so a plugin enabled
 * on one site of a multisite install answered on every site. The plugin
 * here lives in a throwaway directory under storage/framework/testing/; the
 * loaders are pointed at it through config('plugins.plugins_directory').
 */
class PluginRouteSiteGateTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'route-gate-probe';

    private string $pluginsRoot;

    private Plugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        config()->set('app.installed', true);
        config()->set('app.force_ssl', false);

        $relativeRoot = 'storage/framework/testing/plugins-'.uniqid();
        $this->pluginsRoot = base_path($relativeRoot);
        config()->set('plugins.plugins_directory', $relativeRoot);

        $routes = $this->pluginsRoot.'/RouteGateProbe/routes';
        File::ensureDirectoryExists($routes);
        File::put($routes.'/web.php', <<<'PHP'
            <?php
            use Illuminate\Support\Facades\Route;
            Route::get('/route-gate-probe/web', fn () => 'probe-web-ok');
            PHP);
        File::put($routes.'/api.php', <<<'PHP'
            <?php
            use Illuminate\Support\Facades\Route;
            Route::get('/route-gate-probe/legacy-api', fn () => response()->json(['probe' => 'legacy-ok']));
            PHP);

        $this->plugin = Plugin::create([
            'name' => 'RouteGateProbe',
            'package_name' => 'dixlase/route-gate-probe',
            'directory' => 'RouteGateProbe',
            'namespace' => 'Plugins\\RouteGateProbe',
            'slug' => self::SLUG,
            'version' => '1.0.0',
            'author' => 'Test',
            'installed_at' => now(),
        ]);

        // Enable the way plugin:enable does: the model's saved() hook writes
        // the primary site's activation row.
        $this->plugin->update(['enabled_at' => now()]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pluginsRoot);
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';

        parent::tearDown();
    }

    public function test_front_loader_web_route_answers_where_the_plugin_is_active(): void
    {
        PluginHelper::loadEnabledWebRoutes();

        $this->get('/route-gate-probe/web')->assertOk()->assertSee('probe-web-ok');
    }

    public function test_front_loader_web_route_is_404_where_the_plugin_is_not_active(): void
    {
        PluginHelper::loadEnabledWebRoutes();
        $this->deactivateOnPrimarySite();

        $response = $this->get('/route-gate-probe/web');

        $response->assertNotFound();
        $this->assertStringNotContainsString('probe-web-ok', (string) $response->getContent());
        $this->assertStringNotContainsString('"error"', (string) $response->getContent(), 'A web route must not answer with the API envelope');
    }

    public function test_provider_loader_web_route_is_gated(): void
    {
        $this->loadRoutesThroughProvider();

        $this->get('/route-gate-probe/web')->assertOk()->assertSee('probe-web-ok');

        $this->deactivateOnPrimarySite();

        $this->get('/route-gate-probe/web')->assertNotFound();
    }

    public function test_legacy_api_route_answers_where_the_plugin_is_active(): void
    {
        PluginHelper::loadEnabledApiRoutes();

        $this->getJson('/route-gate-probe/legacy-api')->assertOk()->assertJsonPath('probe', 'legacy-ok');
    }

    public function test_legacy_api_route_is_404_where_the_plugin_is_not_active(): void
    {
        PluginHelper::loadEnabledApiRoutes();
        $this->deactivateOnPrimarySite();

        $this->getJson('/route-gate-probe/legacy-api')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_activation_on_another_site_does_not_open_the_routes_here(): void
    {
        PluginHelper::loadEnabledWebRoutes();
        PluginHelper::loadEnabledApiRoutes();
        $this->deactivateOnPrimarySite();

        $secondary = Site::create([
            'slug' => 'second',
            'name' => 'Second Site',
            'primary_locale' => 'en',
            'timezone' => 'UTC',
            'is_primary' => false,
            'is_active' => true,
        ]);
        SitePluginActivation::query()->create([
            'site_id' => $secondary->id,
            'plugin_id' => $this->plugin->id,
            'is_active' => true,
            'activated_at' => now(),
        ]);

        // Requests resolve to the primary site, where the plugin is inactive.
        $this->get('/route-gate-probe/web')->assertNotFound();
        $this->getJson('/route-gate-probe/legacy-api')->assertNotFound();
    }

    private function deactivateOnPrimarySite(): void
    {
        $updated = SitePluginActivation::query()
            ->where('site_id', Site::primary()->id)
            ->where('plugin_id', $this->plugin->id)
            ->update(['is_active' => false]);

        $this->assertSame(1, $updated, 'The primary site activation row should exist after enabling the plugin');
    }

    private function loadRoutesThroughProvider(): void
    {
        $provider = new PluginServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'loadPluginRoutes');
        $method->invoke($provider);
    }
}
