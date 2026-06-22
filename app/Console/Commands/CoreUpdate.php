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

use App\Console\Traits\BuildsExtensionAssets;
use App\Models\CoreRelease;
use App\Models\CoreVersionHistory;
use App\Services\Core\CoreUpdater;
use Illuminate\Console\Command;

/**
 * Apply a Dixlase Core upgrade from the registered source.
 *
 * Designed for CLI execution only — running this from a web request would
 * replace the running code mid-flight. UI-driven execution is handled
 * separately via maintenance mode + queued job (see B-2 backlog).
 */
class CoreUpdate extends Command
{
    use BuildsExtensionAssets;

    protected $signature = 'dls:core:update
        {--to= : Target version to install (defaults to core_releases.available_version)}
        {--force : Skip confirmation prompt}
        {--dry-run : Resolve target version and exit without changes}
        {--build : Force a front-end asset rebuild even when compiled assets already exist}
        {--skip-build : Skip the npm install / build step entirely}
        {--applied-by= : Member id to record on core_version_history.applied_by_id (defaults to null for direct CLI runs)}';

    protected $description = 'Update the Dixlase Core to the latest available release';

    public function handle(CoreUpdater $updater): int
    {
        $coreState = CoreRelease::singleton();
        $current = (string) (CoreVersionHistory::currentVersion() ?? config('app.version', '0.0.0'));
        $target = $this->option('to') ?: $coreState->available_version;

        if ($target === null) {
            $this->info('No core update is currently available.');
            $this->line('Run `php artisan dls:source:check` first to refresh the available version.');

            return self::SUCCESS;
        }

        if (version_compare($target, $current, '<=')) {
            $this->info("Already at v{$current} (target v{$target} is not newer).");

            return self::SUCCESS;
        }

        $this->line("Current version: v{$current}");
        $this->line("Target version:  v{$target}");

        if ($this->option('dry-run')) {
            $this->info('Dry run — no changes will be applied.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Proceed with core update?', false)) {
            $this->info('Update cancelled.');

            return self::SUCCESS;
        }

        // Direct CLI runs leave applied_by_id null (no authenticated session
        // to attribute to). The admin UI's applyCore() handler passes the
        // signed-in member id via --applied-by so web-triggered upgrades
        // are still attributable on the version history row.
        $appliedBy = $this->option('applied-by');
        $appliedById = ($appliedBy !== null && $appliedBy !== '') ? (int) $appliedBy : null;

        try {
            $result = $updater->update(
                version: $target,
                appliedById: $appliedById,
                log: fn (string $line) => $this->line('[core-update] '.$line),
            );

            $this->newLine();
            $this->info("✓ Core updated: v{$result['from']} -> v{$result['to']}");
            $this->line("  history id: {$result['history_id']}");
            $this->line("  snapshot:   {$result['snapshot']}");
            if (! empty($result['backup_record_id'])) {
                $this->line("  db backup:  record #{$result['backup_record_id']} (kept for manual restore)");
            }

            // Front-end assets ship prebuilt in the release ZIP
            // (public/assets/build is applied with the rest of public/),
            // and production installs are not guaranteed to have Node. The
            // default 'auto' mode therefore skips the build when prebuilt
            // assets are already present (the release case) and only builds
            // when they are absent (a source/dev install). --build forces a
            // rebuild; --skip-build never builds.
            $this->newLine();
            $this->buildExtensionAssets(base_path(), $this->extensionAssetMode());

            // PHP dependencies (vendor/) are applied automatically from the
            // release on a dependency update — no `composer install` needed.
            $this->newLine();
            $this->line('Dependencies and prebuilt assets were applied from the release.');
            $this->line('If PHP opcache uses validate_timestamps=0, reload PHP-FPM to pick up the new vendor/.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("✗ Core update failed: {$e->getMessage()}");
            $this->line('Source files were rolled back from snapshot if possible. Inspect the output above for details.');

            return self::FAILURE;
        }
    }
}
