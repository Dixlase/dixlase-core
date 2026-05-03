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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

namespace App\Console\Commands;

use App\Models\Theme;
use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ThemeUpdate extends Command
{
    protected $signature = 'dls:theme:update
        {slug : Theme slug to update}
        {--force : Skip confirmation}';

    protected $description = 'Update a theme to the latest version from its source';

    public function handle(ExtensionSourceManager $manager): int
    {
        $slug = $this->argument('slug');

        $theme = Theme::query()->where('slug', $slug)->first();
        if (! $theme) {
            $this->error("Theme '{$slug}' is not installed.");

            return self::FAILURE;
        }

        if (! $theme->source_id) {
            $this->error("Theme '{$slug}' has no linked source. Use dls:theme:download instead.");

            return self::FAILURE;
        }

        $this->info("Checking for updates for '{$slug}' (current: v{$theme->version})...");

        try {
            $provider = $manager->makeProvider($theme->source);
            $release = $provider->getLatestRelease($slug, 'theme');

            if (! $release) {
                $this->info('No release found from the source.');

                return self::SUCCESS;
            }

            if (! version_compare($release->version, $theme->version, '>')) {
                $this->info("Already at the latest version (v{$theme->version}).");

                return self::SUCCESS;
            }

            $this->info("Update available: v{$theme->version} → v{$release->version}");

            if (! $this->option('force') && ! $this->confirm('Proceed with update?', true)) {
                $this->info('Update cancelled.');

                return self::SUCCESS;
            }

            $zipPath = $manager->download($slug, 'theme', $release->version, $theme->source_id);
            $this->info("Downloaded v{$release->version}");

            $this->extractUpdate($zipPath, $theme);

            $theme->update([
                'version' => $release->version,
                'available_version' => null,
                'last_version_check' => now(),
            ]);

            $this->info("Theme '{$slug}' updated to v{$release->version} successfully.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Update failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function extractUpdate(string $zipPath, Theme $theme): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open downloaded ZIP file.');
        }

        // テーマは resource_path("views/themes/{directory}") に配置される
        $themeDir = resource_path("views/themes/{$theme->directory}");
        File::ensureDirectoryExists($themeDir);

        $zip->extractTo($themeDir);
        $zip->close();
    }
}
