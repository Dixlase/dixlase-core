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

namespace Tests\Unit\Models;

use App\Models\Plugin;
use App\Models\Site;
use App\Models\SitePluginActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the Plugin model's booted() hook keeps site_plugin_activations
 * in sync with the legacy plugins.enabled_at flag, as documented in the
 * v0.1.0 multisite foundation.
 */
class PluginActivationSyncTest extends TestCase
{
    use RefreshDatabase;

    private Site $primarySite;

    protected function setUp(): void
    {
        parent::setUp();

        // Primary site is auto-seeded by TestCase::ensurePrimarySiteSeeded()
        $this->primarySite = Site::query()->where('is_primary', true)->firstOrFail();
    }

    public function test_enabling_plugin_creates_activation_row(): void
    {
        $plugin = $this->makePlugin(enabledAt: null);

        $this->assertDatabaseMissing('site_plugin_activations', [
            'site_id' => $this->primarySite->id,
            'plugin_id' => $plugin->id,
        ]);

        $plugin->forceFill(['enabled_at' => now()])->save();

        $this->assertDatabaseHas('site_plugin_activations', [
            'site_id' => $this->primarySite->id,
            'plugin_id' => $plugin->id,
            'is_active' => true,
        ]);
    }

    public function test_disabling_plugin_updates_activation_row_to_inactive(): void
    {
        $plugin = $this->makePlugin(enabledAt: null);

        // First enable to seed the activation row, then disable to verify the
        // sync hook updates it. The hook listens to UPDATE events, so we go
        // through a create-then-update path rather than create-with-enabled.
        $plugin->forceFill(['enabled_at' => now()])->save();

        $this->assertDatabaseHas('site_plugin_activations', [
            'site_id' => $this->primarySite->id,
            'plugin_id' => $plugin->id,
            'is_active' => true,
        ]);

        $plugin->forceFill(['enabled_at' => null])->save();

        $this->assertDatabaseHas('site_plugin_activations', [
            'site_id' => $this->primarySite->id,
            'plugin_id' => $plugin->id,
            'is_active' => false,
        ]);
    }

    public function test_initial_create_does_not_trigger_sync_hook(): void
    {
        // Documents the v0.1.0 behaviour: the saved() hook checks
        // wasChanged('enabled_at') which returns false for a fresh INSERT.
        // Activation rows are seeded by dls:plugin:enable on update, not
        // by Plugin::create(). If this changes, update the documentation
        // in app/Models/Plugin.php booted() before adjusting the test.
        $plugin = $this->makePlugin(enabledAt: now());

        $this->assertDatabaseMissing('site_plugin_activations', [
            'site_id' => $this->primarySite->id,
            'plugin_id' => $plugin->id,
        ]);
    }

    public function test_unrelated_field_change_does_not_create_activation_row(): void
    {
        $plugin = $this->makePlugin(enabledAt: null);

        SitePluginActivation::query()
            ->where('plugin_id', $plugin->id)
            ->delete();

        $plugin->forceFill(['version' => '2.0.0'])->save();

        $this->assertDatabaseMissing('site_plugin_activations', [
            'site_id' => $this->primarySite->id,
            'plugin_id' => $plugin->id,
        ]);
    }

    public function test_sync_is_idempotent_across_repeated_enables(): void
    {
        $plugin = $this->makePlugin(enabledAt: null);

        $plugin->forceFill(['enabled_at' => now()])->save();
        $plugin->forceFill(['enabled_at' => null])->save();
        $plugin->forceFill(['enabled_at' => now()])->save();

        $this->assertSame(
            1,
            SitePluginActivation::query()
                ->where('site_id', $this->primarySite->id)
                ->where('plugin_id', $plugin->id)
                ->count(),
            'Repeated enabled_at toggles must update the existing activation row, not create duplicates.'
        );
    }

    private function makePlugin(?\DateTimeInterface $enabledAt): Plugin
    {
        return Plugin::create([
            'name' => 'Test Plugin',
            'package_name' => 'dixlase/test-plugin',
            'directory' => 'TestPlugin',
            'namespace' => 'Plugins\\TestPlugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'installed_at' => now(),
            'enabled_at' => $enabledAt,
        ]);
    }
}
