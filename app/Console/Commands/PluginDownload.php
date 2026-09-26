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

use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\ExtensionSourceSidecar;
use App\Support\ComposerLocalManifest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PluginDownload extends Command
{
    /**
     * The target version is `--to=`, not `--version=`: Symfony's Application
     * already registers a global `--version` (`-V`), and declaring it again
     * here made every invocation — including `--help` — abort with
     * "An option named \"version\" already exists.". `--to=` also matches
     * dls:core:update, which names its target version the same way.
     */
    protected $signature = 'dls:plugin:download
        {slug : Plugin slug to download}
        {--to= : Specific version to download (defaults to latest)}
        {--source= : Source ID to download from}
        {--extract : Extract the ZIP to the plugins directory}';

    protected $description = 'Download a plugin from an extension source';

    public function handle(ExtensionSourceManager $manager, ExtensionSourceSidecar $sidecar): int
    {
        $slug = $this->argument('slug');
        $version = $this->option('to');
        $sourceId = $this->option('source') ? (int) $this->option('source') : null;

        $this->info("Downloading plugin '{$slug}'...");

        try {
            // Keep the source that served the ZIP: after --extract it is
            // written next to plugin.json so dls:plugin:install can link
            // the plugin to it (dls:plugin:update refuses to run for an
            // unlinked plugin). Same mechanism as the admin "add from
            // source" page.
            $download = $manager->downloadWithSource($slug, 'plugin', $version, $sourceId);
            $zipPath = $download['path'];
            $this->info("Downloaded to: {$zipPath}");

            if ($this->option('extract')) {
                $result = $this->extractPlugin($zipPath, $slug);
                if ($result === self::SUCCESS) {
                    $sidecar->write(
                        base_path("plugins/{$this->slugToName($slug)}"),
                        $manager->resolveSourceLinkage($download['source'], $slug, 'plugin'),
                    );
                    $this->info("Recorded source '{$download['source']->name}' for dls:plugin:install.");
                }

                return $result;
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

        // Extract to a staging dir first. Release ZIPs wrap the plugin
        // under a single top-level directory (e.g. DixlaseCookie/), so
        // extracting straight into plugins/<Name>/ would double-nest to
        // plugins/<Name>/<Name>/ and leave the plugin unloadable. Locate
        // the payload root (the directory holding plugin.json) and install
        // that. Mirrors InstallThemeDownloader's extract handling.
        $stagingDir = storage_path('app/private/plugin-download/'.uniqid('extract_', true));
        File::ensureDirectoryExists($stagingDir);
        $zip->extractTo($stagingDir);
        $zip->close();

        try {
            $entries = array_values(array_filter(
                scandir($stagingDir) ?: [],
                fn ($e) => $e !== '.' && $e !== '..'
            ));
            $payloadRoot = (count($entries) === 1 && is_dir($stagingDir.'/'.$entries[0]))
                ? $stagingDir.'/'.$entries[0]
                : $stagingDir;

            if (! is_file($payloadRoot.'/plugin.json')) {
                $this->error('Downloaded archive does not contain a plugin.json.');

                return self::FAILURE;
            }

            $pluginDir = base_path("plugins/{$this->slugToName($slug)}");
            if (File::isDirectory($pluginDir)) {
                File::deleteDirectory($pluginDir);
            }
            File::ensureDirectoryExists(dirname($pluginDir));

            // copyDirectory, not moveDirectory: moveDirectory only calls
            // rename(), which fails with EXDEV when the staging dir and
            // plugins/ live on different filesystems (e.g. a bind-mounted
            // plugins/ in Docker). The staging dir is removed in finally.
            if (! File::copyDirectory($payloadRoot, $pluginDir)) {
                $this->error("Failed to install the plugin into plugins/{$this->slugToName($slug)}.");

                return self::FAILURE;
            }

            // Not installed yet: withhold its autoload.files until
            // dls:plugin:install clears this, so an unrelated autoload sync in
            // between (another install, a core update) cannot run its code.
            ComposerLocalManifest::markPendingInstall($pluginDir);

            $this->info("Extracted to: {$pluginDir}");

            return self::SUCCESS;
        } finally {
            File::deleteDirectory($stagingDir);
        }
    }

    /**
     * Convert kebab-case slug to StudlyCase directory name
     */
    protected function slugToName(string $slug): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
    }
}
