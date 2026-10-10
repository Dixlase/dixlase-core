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
use App\Support\ExtensionDirectoryName;
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
                // The sidecar has to land in the directory the extract
                // actually created, which the manifest decides -- not in
                // plugins/{studly slug}. Those two differ for any extension
                // with an acronym in its name (#488).
                $pluginDir = $this->extractPlugin($zipPath, $slug);
                if ($pluginDir === null) {
                    return self::FAILURE;
                }

                $sidecar->write(
                    $pluginDir,
                    $manager->resolveSourceLinkage($download['source'], $slug, 'plugin'),
                );
                $this->info("Recorded source '{$download['source']->name}' for dls:plugin:install.");

                return self::SUCCESS;
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Download failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Extract the downloaded archive into plugins/, returning the absolute
     * path of the directory it created, or null when it could not.
     */
    protected function extractPlugin(string $zipPath, string $slug): ?string
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error('Failed to open downloaded ZIP file.');

            return null;
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
            // Remember the archive's own folder name separately: when the
            // archive has no single top-level directory, $payloadRoot is the
            // staging dir, whose name is a uniqid() and must never become a
            // plugin directory name.
            $archiveRoot = (count($entries) === 1 && is_dir($stagingDir.'/'.$entries[0]))
                ? $entries[0]
                : null;
            $payloadRoot = $archiveRoot !== null ? $stagingDir.'/'.$archiveRoot : $stagingDir;

            if (! is_file($payloadRoot.'/plugin.json')) {
                $this->error('Downloaded archive does not contain a plugin.json.');

                return null;
            }

            $directory = $this->resolveDirectoryName($payloadRoot.'/plugin.json', $archiveRoot, $slug);

            $pluginDir = base_path("plugins/{$directory}");
            if (File::isDirectory($pluginDir)) {
                File::deleteDirectory($pluginDir);
            }
            File::ensureDirectoryExists(dirname($pluginDir));

            // copyDirectory, not moveDirectory: moveDirectory only calls
            // rename(), which fails with EXDEV when the staging dir and
            // plugins/ live on different filesystems (e.g. a bind-mounted
            // plugins/ in Docker). The staging dir is removed in finally.
            if (! File::copyDirectory($payloadRoot, $pluginDir)) {
                $this->error("Failed to install the plugin into plugins/{$directory}.");

                return null;
            }

            // Not installed yet: withhold its autoload.files until
            // dls:plugin:install clears this, so an unrelated autoload sync in
            // between (another install, a core update) cannot run its code.
            ComposerLocalManifest::markPendingInstall($pluginDir);

            $this->info("Extracted to: {$pluginDir}");

            return $pluginDir;
        } finally {
            File::deleteDirectory($stagingDir);
        }
    }

    /**
     * The directory name to install under.
     *
     * The manifest decides, because it is the only place that states the
     * namespace the plugin's classes declare, and core generates
     * composer.local.json's PSR-4 prefix from the directory name alone. The
     * slug cannot decide: ucwords('dixlase seo') is 'DixlaseSeo', while the
     * plugin declares Plugins\DixlaseSEO, and Composer matches a PSR-4 prefix
     * case-sensitively — so the classes resolve only through an optimized
     * classmap, and only until someone dumps the autoloader without
     * --optimize (#488).
     *
     * Same order as the admin "add from source" page, so both entry points
     * produce the same tree from the same release:
     *
     *   1. the manifest (package, then the final segment of namespace, then
     *      of package_name)
     *   2. the archive's own top-level folder name, when it had exactly one
     *   3. the studly slug, which is what it always used to be
     *
     * @param  string|null  $archiveRoot  the archive's single top-level folder, if any
     */
    protected function resolveDirectoryName(string $manifestPath, ?string $archiveRoot, string $slug): string
    {
        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($manifest)) {
            $this->warn('Could not read plugin.json; falling back to the archive or slug for the directory name.');
            $manifest = null;
        }

        $fromManifest = ExtensionDirectoryName::fromManifest($manifest, ExtensionDirectoryName::KIND_PLUGIN);
        if ($fromManifest !== null) {
            return $fromManifest;
        }

        if ($archiveRoot !== null) {
            $fromArchive = ExtensionDirectoryName::sanitise($archiveRoot, ExtensionDirectoryName::KIND_PLUGIN);
            if ($fromArchive !== null) {
                return $fromArchive;
            }
        }

        return $this->slugToName($slug);
    }

    /**
     * Convert kebab-case slug to StudlyCase directory name.
     *
     * Last resort only: it loses the case of every acronym. See
     * {@see self::resolveDirectoryName()}.
     */
    protected function slugToName(string $slug): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
    }
}
