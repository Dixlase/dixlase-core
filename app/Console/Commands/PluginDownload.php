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

use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PluginDownload extends Command
{
    protected $signature = 'dls:plugin:download
        {slug : Plugin slug to download}
        {--version= : Specific version (defaults to latest)}
        {--source= : Source ID to download from}
        {--extract : Extract the ZIP to the plugins directory}';

    protected $description = 'Download a plugin from an extension source';

    public function handle(ExtensionSourceManager $manager): int
    {
        $slug = $this->argument('slug');
        $version = $this->option('version');
        $sourceId = $this->option('source') ? (int) $this->option('source') : null;

        $this->info("Downloading plugin '{$slug}'...");

        try {
            $zipPath = $manager->download($slug, 'plugin', $version, $sourceId);
            $this->info("Downloaded to: {$zipPath}");

            if ($this->option('extract')) {
                return $this->extractPlugin($zipPath, $slug);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Download failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function extractPlugin(string $zipPath, string $slug): int
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error('Failed to open downloaded ZIP file.');

            return self::FAILURE;
        }

        $pluginDir = base_path("plugins/{$this->slugToName($slug)}");
        File::ensureDirectoryExists($pluginDir);

        $zip->extractTo($pluginDir);
        $zip->close();

        $this->info("Extracted to: {$pluginDir}");

        return self::SUCCESS;
    }

    /**
     * Convert kebab-case slug to StudlyCase directory name
     */
    protected function slugToName(string $slug): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
    }
}
