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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backfill the primary site's plugin activation rows (#494).
     *
     * Plugin web routes, legacy routes/api.php and routes/api/v1.php only
     * answer where SiteContext::isPluginActive() is true, which reads
     * site_plugin_activations. A row is written by the Plugin model's
     * saved() hook when a plugin is enabled, but a plugin enabled before
     * that table existed, or enabled while the primary site was not yet
     * seeded, has none — and its front routes would turn into 404s.
     *
     * For every plugin with enabled_at set, insert an active row for the
     * primary site when no row exists. Insert-only: an existing row is never
     * changed, so a deliberate per-site deactivation is kept. Idempotent; a
     * no-op on a fresh install (no enabled plugins yet).
     */
    public function up(): void
    {
        if (! Schema::hasTable('plugins')
            || ! Schema::hasTable('sites')
            || ! Schema::hasTable('site_plugin_activations')) {
            return;
        }

        // Same resolution as Site::primary(): active, primary, not deleted.
        $primarySiteId = DB::table('sites')
            ->where('is_primary', true)
            ->where('is_active', true)
            ->when(Schema::hasColumn('sites', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('id')
            ->value('id');

        if ($primarySiteId === null) {
            return;
        }

        $plugins = DB::table('plugins')
            ->whereNotNull('enabled_at')
            ->whereNotExists(function ($query) use ($primarySiteId) {
                $query->select(DB::raw(1))
                    ->from('site_plugin_activations')
                    ->whereColumn('site_plugin_activations.plugin_id', 'plugins.id')
                    ->where('site_plugin_activations.site_id', $primarySiteId);
            })
            ->get(['id', 'enabled_at']);

        $now = now();

        foreach ($plugins as $plugin) {
            DB::table('site_plugin_activations')->insertOrIgnore([
                'site_id' => $primarySiteId,
                'plugin_id' => $plugin->id,
                'is_active' => true,
                'activated_at' => $plugin->enabled_at,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Intentionally a no-op: the rows cannot be told apart from ones the
     * enable flow wrote, and removing them would take plugins offline.
     */
    public function down(): void
    {
        //
    }
};
