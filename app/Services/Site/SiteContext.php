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
