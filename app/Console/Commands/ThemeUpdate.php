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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
use App\Services\Extension\ExtensionSourceSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ThemeUpdate extends Command
{
    protected $signature = 'dls:theme:update
        {slug : Theme slug to update}
        {--force : Skip confirmation}';

    protected $description = 'Update a theme to the latest version from its source';

    public function handle(ExtensionSourceManager $manager, ExtensionSourceSnapshot $snapshotter): int
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

        $snapshotPath = null;

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

            // Capture a snapshot of the live theme tree so we can roll back
            // a partially-extracted update on failure.
            $livePath = base_path("themes/{$theme->directory}");
            if (is_dir($livePath)) {
                $snapshotPath = $snapshotter->capture(
                    ExtensionSourceSnapshot::KIND_THEME,
                    $theme->directory,
                    $livePath,
                );
                $this->info("Snapshot captured at {$snapshotPath}");
            }

            $this->extractUpdate($zipPath, $theme);

            $theme->update([
                'version' => $release->version,
                'available_version' => null,
                'last_version_check' => now(),
                'update_failed_at' => null,
                'update_failure_reason' => null,
            ]);

            // Successful update — discard the snapshot to free disk space.
            if ($snapshotPath !== null) {
                $snapshotter->discard($snapshotPath);
                $snapshotPath = null;
            }

            $this->info("Theme '{$slug}' updated to v{$release->version} successfully.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Roll back the theme tree from the snapshot so the user is not
            // stranded on a half-extracted directory.
            if ($snapshotPath !== null) {
                try {
                    $livePath = base_path("themes/{$theme->directory}");
                    $snapshotter->restore($snapshotPath, $livePath);
                    $this->warn('Theme source rolled back from snapshot.');
                    $snapshotter->discard($snapshotPath);
                } catch (\Throwable $restoreError) {
                    $this->error("ROLLBACK FAILED: {$restoreError->getMessage()}");
                    $this->error("Manual recovery required. Snapshot retained at: {$snapshotPath}");
                }
            }

            $theme->update([
                'update_failed_at' => now(),
                'update_failure_reason' => $this->truncateReason($e->getMessage()),
            ]);

            $this->error("Update failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Cap stored failure reasons so a verbose stack-trace string does not
     * bloat the row. Display surfaces will truncate further as needed.
     */
    protected function truncateReason(string $message): string
    {
        $message = trim($message);
        if (mb_strlen($message) <= 1000) {
            return $message;
        }

        return mb_substr($message, 0, 997).'...';
    }

    protected function extractUpdate(string $zipPath, Theme $theme): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Failed to open downloaded ZIP file.');
        }

        // Themes are placed in resource_path("views/themes/{directory}")
        $themeDir = resource_path("views/themes/{$theme->directory}");
        File::ensureDirectoryExists($themeDir);

        $zip->extractTo($themeDir);
        $zip->close();
    }
}
