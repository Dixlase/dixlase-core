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

use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Apply all available extension updates in a single run.
 *
 *   php artisan dls:source:update --all
 *   php artisan dls:source:update --all --type=plugin
 *   php artisan dls:source:update --all --type=theme
 */
class SourceUpdate extends Command
{
    protected $signature = 'dls:source:update
                            {--all : Update every extension that has an available_version}
                            {--type=both : Limit to plugin / theme / both}
                            {--force : Skip per-extension confirmation}';

    protected $description = 'Apply all available plugin / theme updates in sequence';

    public function handle(): int
    {
        if (! $this->option('all')) {
            $this->error('Pass --all to apply every available update. Single-extension updates are handled by dls:plugin:update / dls:theme:update.');

            return self::FAILURE;
        }

        $type = $this->option('type');
        if (! in_array($type, ['plugin', 'theme', 'both'], true)) {
            $this->error("--type must be one of: plugin, theme, both (got: {$type})");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        $pluginSlugs = $type === 'theme'
            ? []
            : Plugin::query()->whereNotNull('available_version')->pluck('slug')->all();

        $themeSlugs = $type === 'plugin'
            ? []
            : Theme::query()->whereNotNull('available_version')->pluck('slug')->all();

        if (empty($pluginSlugs) && empty($themeSlugs)) {
            $this->info('No updates pending. Run dls:source:check to refresh available versions first.');

            return self::SUCCESS;
        }

        $total = count($pluginSlugs) + count($themeSlugs);
        $this->info("Updating {$total} extension(s)...");

        $succeeded = 0;
        $failed = 0;

        foreach ($pluginSlugs as $slug) {
            $this->newLine();
            $this->info("--- Plugin: {$slug} ---");
            $code = Artisan::call('dls:plugin:update', array_filter([
                'slug' => $slug,
                '--force' => $force,
            ]));
            $this->line(rtrim(Artisan::output()));
            $code === self::SUCCESS ? $succeeded++ : $failed++;
        }

        foreach ($themeSlugs as $slug) {
            $this->newLine();
            $this->info("--- Theme: {$slug} ---");
            $code = Artisan::call('dls:theme:update', array_filter([
                'slug' => $slug,
                '--force' => $force,
            ]));
            $this->line(rtrim(Artisan::output()));
            $code === self::SUCCESS ? $succeeded++ : $failed++;
        }

        $this->newLine();
        $this->info("Done: {$succeeded} succeeded, {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : 1;
    }
}
