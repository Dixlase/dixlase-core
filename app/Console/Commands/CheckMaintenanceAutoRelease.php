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

namespace App\Console\Commands;

use App\Contracts\Site\SiteContextInterface;
use App\Models\Site;
use App\Services\Site\SettingResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMaintenanceAutoRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:check-auto-release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and auto-release maintenance mode if scheduled time has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $siteContext = app(SiteContextInterface::class);
        $resolver = app(SettingResolver::class);

        // Iterate over every active site. maintenance_* keys are PerSite
        // scope; SiteContext is switched per site so SettingResolver
        // reads/writes against the correct site's row.
        $sites = Site::query()->where('is_active', true)->get(['id', 'slug']);
        $releasedCount = 0;

        foreach ($sites as $site) {
            $siteContext->setCurrent($site->id);

            $maintenanceMode = (bool) $resolver->get('maintenance_mode');
            $autoRelease = (bool) $resolver->get('maintenance_auto_release');
            $releaseAt = $resolver->get('maintenance_release_at');

            if (! $maintenanceMode || ! $autoRelease || ! $releaseAt) {
                continue;
            }

            $releaseTime = \Carbon\Carbon::parse($releaseAt);
            if (! now()->gte($releaseTime)) {
                continue;
            }

            // Release maintenance mode and clear scheduling state.
            $resolver->set('maintenance_mode', false);
            $resolver->set('maintenance_auto_release', false);
            $resolver->set('maintenance_start_at', null);
            $resolver->set('maintenance_release_at', null);

            Log::info('Maintenance mode auto-released', [
                'site_id' => $site->id,
                'site_slug' => $site->slug,
                'release_at' => $releaseAt,
                'released_at' => now()->toDateTimeString(),
            ]);

            $this->info("Site '{$site->slug}' (id={$site->id}): maintenance mode released.");
            $releasedCount++;
        }

        if ($releasedCount === 0) {
            $this->line('No sites required auto-release.');
        }

        return self::SUCCESS;
    }
}
