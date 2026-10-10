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

namespace Tests\Feature\Migration;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The backfill migration gives every enabled plugin an activation row on
 * the primary site, without touching rows that already exist (#494)
 *
 * Plugins are inserted through the query builder so the Plugin model's
 * saved() hook does not write the row first — that is the drift the
 * migration repairs on existing sites.
 */
class BackfillPrimarySitePluginActivationsTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/0001_01_01_000053_backfill_primary_site_plugin_activations.php';

    private int $primarySiteId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->primarySiteId = Site::query()->where('is_primary', true)->firstOrFail()->id;
    }

    public function test_enabled_plugin_without_a_row_gets_an_active_row(): void
    {
        $pluginId = $this->insertPlugin('drifted-plugin', enabled: true);

        $this->runBackfill();

        $this->assertDatabaseHas('site_plugin_activations', [
            'site_id' => $this->primarySiteId,
            'plugin_id' => $pluginId,
            'is_active' => true,
        ]);
    }

    public function test_existing_inactive_row_is_left_untouched(): void
    {
        $pluginId = $this->insertPlugin('deactivated-plugin', enabled: true);
        DB::table('site_plugin_activations')->insert([
            'site_id' => $this->primarySiteId,
            'plugin_id' => $pluginId,
            'is_active' => false,
            'activated_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runBackfill();

        $rows = DB::table('site_plugin_activations')->where('plugin_id', $pluginId)->get();
        $this->assertCount(1, $rows);
        $this->assertFalse((bool) $rows->first()->is_active, 'A deliberate per-site deactivation must survive the backfill');
    }

    public function test_disabled_plugin_gets_no_row(): void
    {
        $pluginId = $this->insertPlugin('disabled-plugin', enabled: false);

        $this->runBackfill();

        $this->assertDatabaseMissing('site_plugin_activations', ['plugin_id' => $pluginId]);
    }

    public function test_backfill_is_idempotent(): void
    {
        $pluginId = $this->insertPlugin('drifted-plugin', enabled: true);

        $this->runBackfill();
        $this->runBackfill();

        $this->assertSame(1, DB::table('site_plugin_activations')->where('plugin_id', $pluginId)->count());
    }

    private function insertPlugin(string $slug, bool $enabled): int
    {
        return (int) DB::table('plugins')->insertGetId([
            'name' => $slug,
            'package_name' => 'dixlase/'.$slug,
            'directory' => $slug,
            'namespace' => 'Plugins\\'.$slug,
            'slug' => $slug,
            'version' => '1.0.0',
            'author' => 'Test',
            'installed_at' => now(),
            'enabled_at' => $enabled ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function runBackfill(): void
    {
        $migration = require base_path(self::MIGRATION);
        $migration->up();
    }
}
