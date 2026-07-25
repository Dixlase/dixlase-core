<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Console\Commands\Site;

use App\Models\Site;
use Illuminate\Console\Command;

/**
 * Site list command.
 *
 * Examples:
 *   php artisan dls:site:list
 *   php artisan dls:site:list --include-trashed
 *   php artisan dls:site:list --inactive
 */
class SiteListCommand extends Command
{
    protected $signature = 'dls:site:list
                            {--include-trashed : Include soft-deleted sites}
                            {--inactive : Include inactive sites}';

    protected $description = 'List all sites in the multisite installation';

    public function handle(): int
    {
        $query = Site::query()->orderBy('id');

        if ($this->option('include-trashed')) {
            $query->withTrashed();
        }

        if (! $this->option('inactive')) {
            $query->where('is_active', true);
        }

        $sites = $query->get();

        if ($sites->isEmpty()) {
            $this->warn('No sites found. Run database seeders to create the primary site.');

            return self::SUCCESS;
        }

        $rows = $sites->map(fn (Site $site) => [
            'id' => $site->id,
            'slug' => $site->slug,
            'name' => $site->name,
            'host' => $site->host ?? '-',
            'path_prefix' => $site->path_prefix ?? '-',
            'locale' => $site->primary_locale,
            'timezone' => $site->timezone,
            'primary' => $site->is_primary ? 'Y' : '',
            'active' => $site->is_active ? 'Y' : '',
            'trashed' => $site->trashed() ? 'Y' : '',
        ])->toArray();

        $this->table(
            ['ID', 'Slug', 'Name', 'Host', 'Path', 'Locale', 'TZ', 'Primary', 'Active', 'Trashed'],
            $rows,
        );

        return self::SUCCESS;
    }
}
