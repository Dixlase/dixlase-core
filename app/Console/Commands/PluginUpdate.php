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

namespace App\Console\Commands;

use App\Models\Plugin;
use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PluginUpdate extends Command
{
    protected $signature = 'dls:plugin:update
        {slug : Plugin slug to update}
        {--force : Skip confirmation}';

    protected $description = 'Update a plugin to the latest version from its source';

    public function handle(ExtensionSourceManager $manager): int
    {
        $slug = $this->argument('slug');

        $plugin = Plugin::query()->where('slug', $slug)->first();
        if (! $plugin) {
            $this->error("Plugin '{$slug}' is not installed.");

            return self::FAILURE;
        }

        if (! $plugin->source_id) {
            $this->error("Plugin '{$slug}' has no linked source. Use dls:plugin:download instead.");

            return self::FAILURE;
        }

        $this->info("Checking for updates for '{$slug}' (current: v{$plugin->version})...");

        try {
            $provider = $manager->makeProvider($plugin->source);
            $release = $provider->getLatestRelease($slug, 'plugin');

            if (! $release) {
                $this->info('No release found from the source.');

                return self::SUCCESS;
            }

            if (! version_compare($release->version, $plugin->version, '>')) {
                $this->info("Already at the latest version (v{$plugin->version}).");

                return self::SUCCESS;
            }

            $this->info("Update available: v{$plugin->version} → v{$release->version}");

            if (! $this->option('force') && ! $this->confirm('Proceed with update?', true)) {
                $this->info('Update cancelled.');

                return self::SUCCESS;
            }

            $zipPath = $manager->download($slug, 'plugin', $release->version, $plugin->source_id);
            $this->info("Downloaded v{$release->version}");

            $this->extractUpdate($zipPath, $plugin);

            $plugin->update([
                'version' => $release->version,
                'available_version' => null,
                'last_version_check' => now(),
            ]);

            $this->info("Plugin '{$slug}' updated to v{$release->version} successfully.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Update failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function extractUpdate(string $zipPath, Plugin $plugin): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open downloaded ZIP file.');
        }

        $pluginDir = base_path("plugins/{$plugin->directory}");
        File::ensureDirectoryExists($pluginDir);

        $zip->extractTo($pluginDir);
        $zip->close();
    }
}
