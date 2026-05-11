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

namespace Tests\Unit\Services\Site;

use App\Models\Plugin;
use App\Models\Site;
use App\Models\SitePluginActivation;
use App\Models\SiteThemeActivation;
use App\Models\Theme;
use App\Services\Site\SiteContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers SiteContext::isPluginActive / isThemeActive routing through the
 * site-aware activation tables, including the legacy fallback paths that
 * apply when the activation tables are not yet provisioned.
 */
class SiteContextActivationTest extends TestCase
{
    use RefreshDatabase;

    private Site $primarySite;

    private Site $secondarySite;

    private SiteContext $siteContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Primary site is auto-seeded by TestCase::ensurePrimarySiteSeeded()
        $this->primarySite = Site::query()->where('is_primary', true)->firstOrFail();

        $this->secondarySite = Site::create([
            'slug' => 'second',
            'name' => 'Second Site',
            'primary_locale' => 'en',
            'timezone' => 'UTC',
            'is_primary' => false,
            'is_active' => true,
        ]);

        $this->siteContext = new SiteContext();
        $this->siteContext->setCurrent($this->primarySite->id);
    }

    public function test_is_plugin_active_returns_true_for_active_row_on_current_site(): void
    {
        $plugin = $this->makePlugin('active-plugin');

        SitePluginActivation::query()->updateOrCreate(
            ['site_id' => $this->primarySite->id, 'plugin_id' => $plugin->id],
            ['is_active' => true, 'activated_at' => now()]
        );

        $this->assertTrue($this->siteContext->isPluginActive('active-plugin'));
    }

    public function test_is_plugin_active_returns_false_when_row_is_inactive(): void
    {
        $plugin = $this->makePlugin('inactive-plugin');

        SitePluginActivation::query()->updateOrCreate(
            ['site_id' => $this->primarySite->id, 'plugin_id' => $plugin->id],
            ['is_active' => false, 'activated_at' => null]
        );

        $this->assertFalse($this->siteContext->isPluginActive('inactive-plugin'));
    }

    public function test_is_plugin_active_isolates_sites(): void
    {
        $plugin = $this->makePlugin('cross-site-plugin');

        // Active on the secondary site but not the primary one.
        SitePluginActivation::query()->updateOrCreate(
            ['site_id' => $this->secondarySite->id, 'plugin_id' => $plugin->id],
            ['is_active' => true, 'activated_at' => now()]
        );

        // Current context is primary, so the plugin must read as inactive.
        $this->assertFalse($this->siteContext->isPluginActive('cross-site-plugin'));

        $this->siteContext->setCurrent($this->secondarySite->id);

        $this->assertTrue($this->siteContext->isPluginActive('cross-site-plugin'));
    }

    public function test_is_plugin_active_falls_back_to_enabled_at_when_activation_table_missing(): void
    {
        $plugin = $this->makePlugin('legacy-plugin', enabledAt: now());

        // Simulate the pre-Phase-4 state where the activation table is absent.
        Schema::dropIfExists('site_plugin_activations');

        $this->assertTrue($this->siteContext->isPluginActive('legacy-plugin'));
    }

    public function test_is_plugin_active_returns_false_for_unknown_slug(): void
    {
        $this->assertFalse($this->siteContext->isPluginActive('does-not-exist'));
    }

    public function test_is_theme_active_returns_true_for_active_row_on_current_site(): void
    {
        $theme = $this->makeTheme('active-theme');

        SiteThemeActivation::query()->updateOrCreate(
            ['site_id' => $this->primarySite->id, 'theme_id' => $theme->id],
            ['is_active' => true, 'activated_at' => now()]
        );

        $this->assertTrue($this->siteContext->isThemeActive('active-theme'));
    }

    public function test_is_theme_active_isolates_sites(): void
    {
        $theme = $this->makeTheme('cross-site-theme');

        SiteThemeActivation::query()->updateOrCreate(
            ['site_id' => $this->secondarySite->id, 'theme_id' => $theme->id],
            ['is_active' => true, 'activated_at' => now()]
        );

        $this->assertFalse($this->siteContext->isThemeActive('cross-site-theme'));

        $this->siteContext->setCurrent($this->secondarySite->id);

        $this->assertTrue($this->siteContext->isThemeActive('cross-site-theme'));
    }

    public function test_is_theme_active_falls_back_to_config_when_activation_table_missing(): void
    {
        $theme = $this->makeTheme('legacy-theme', directory: 'LegacyTheme');

        config()->set('themes.active_theme', 'LegacyTheme');

        Schema::dropIfExists('site_theme_activations');

        $this->assertTrue($this->siteContext->isThemeActive('legacy-theme'));
    }

    private function makePlugin(string $slug, ?\DateTimeInterface $enabledAt = null): Plugin
    {
        return Plugin::create([
            'name' => 'Plugin '.$slug,
            'package_name' => 'dixlase/'.$slug,
            'directory' => $slug,
            'namespace' => 'Plugins\\'.$slug,
            'slug' => $slug,
            'version' => '1.0.0',
            'author' => 'Test',
            'installed_at' => now(),
            'enabled_at' => $enabledAt,
        ]);
    }

    private function makeTheme(string $slug, string $directory = 'Test'): Theme
    {
        return Theme::create([
            'name' => 'Theme '.$slug,
            'directory' => $directory,
            'namespace' => 'Themes\\'.$directory,
            'slug' => $slug,
            'version' => '1.0.0',
            'author' => 'Test',
            'installed_at' => now(),
        ]);
    }
}
