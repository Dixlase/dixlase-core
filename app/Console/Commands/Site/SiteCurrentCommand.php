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

use App\Contracts\Site\SiteContextInterface;
use Illuminate\Console\Command;
use Throwable;

/**
 * Show the site resolved by SiteContext for the current execution.
 *
 * Useful for verifying that the multisite foundation is wired correctly.
 *
 * Example:
 *   php artisan dls:site:current
 */
class SiteCurrentCommand extends Command
{
    protected $signature = 'dls:site:current';

    protected $description = 'Show the site currently resolved by SiteContext';

    public function handle(SiteContextInterface $siteContext): int
    {
        try {
            $site = $siteContext->currentSite();
        } catch (Throwable $e) {
            $this->error('Failed to resolve current site: '.$e->getMessage());
            $this->line('Did you run "php artisan migrate" and "php artisan db:seed --class=SitesSeeder"?');

            return self::FAILURE;
        }

        $this->info("Current site (id={$site->id}):");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', (string) $site->id],
                ['Slug', $site->slug],
                ['Name', $site->name],
                ['Host', $site->host ?? '(none)'],
                ['Path Prefix', $site->path_prefix ?? '(none)'],
                ['Primary Locale', $site->primary_locale],
                ['Timezone', $site->timezone],
                ['Is Primary', $site->is_primary ? 'true' : 'false'],
                ['Is Active', $site->is_active ? 'true' : 'false'],
            ]
        );

        return self::SUCCESS;
    }
}
