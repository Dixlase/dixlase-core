<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

declare(strict_types=1);

namespace App\Services\Site;

use App\Contracts\Site\SiteContextInterface;
use App\Models\Site;
use App\Models\SitePluginActivation;
use App\Models\SiteThemeActivation;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SiteContext implements SiteContextInterface
{
    private ?Site $currentSite = null;

    public function currentSiteId(): int
    {
        return $this->currentSite()->id;
    }

    public function currentSite(): Site
    {
        if ($this->currentSite === null) {
            $primary = Site::primary();
            if ($primary === null) {
                throw new RuntimeException(
                    'No primary site is configured. Run database seeders to create the primary site.'
                );
            }
            $this->currentSite = $primary;
        }

        return $this->currentSite;
    }

    public function setCurrent(int $siteId): void
    {
        $this->currentSite = Site::query()->findOrFail($siteId);
    }

    public function isPluginActive(string $slug): bool
    {
        if (! Schema::hasTable('plugins')) {
            return false;
        }

        // Site-aware path: consult site_plugin_activations once available.
        // Fallback path: derive from the global plugins.enabled_at flag for
        // installations that have not yet populated the activation table.
        if (Schema::hasTable('site_plugin_activations')) {
            return SitePluginActivation::query()
                ->where('site_id', $this->currentSiteId())
                ->where('is_active', true)
                ->whereIn('plugin_id', function ($subquery) use ($slug) {
                    $subquery->select('id')
                        ->from('plugins')
                        ->where('slug', $slug);
                })
                ->exists();
        }

        return DB::table('plugins')
            ->where('slug', $slug)
            ->whereNotNull('enabled_at')
            ->exists();
    }

    public function isThemeActive(string $slug): bool
    {
        if (! Schema::hasTable('themes')) {
            return false;
        }

        // Site-aware path: consult site_theme_activations once available.
        // Fallback path: compare against the legacy config('themes.active_theme')
        // when the activation table has not been populated yet.
        if (Schema::hasTable('site_theme_activations')) {
            return SiteThemeActivation::query()
                ->where('site_id', $this->currentSiteId())
                ->where('is_active', true)
                ->whereIn('theme_id', function ($subquery) use ($slug) {
                    $subquery->select('id')
                        ->from('themes')
                        ->where('slug', $slug);
                })
                ->exists();
        }

        $activeDirectory = config('themes.active_theme');
        if ($activeDirectory === null) {
            return false;
        }

        return DB::table('themes')
            ->where('slug', $slug)
            ->where('directory', $activeDirectory)
            ->exists();
    }

    public function connection(): ConnectionInterface
    {
        return DB::connection();
    }
}
