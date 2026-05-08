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
    protected $signature = 'dls:core:update
        {--to= : Target version to install (defaults to core_releases.available_version)}
        {--force : Skip confirmation prompt}
        {--dry-run : Resolve target version and exit without changes}';

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

        try {
            $result = $updater->update(
                version: $target,
                appliedById: null, // CLI executions are unattributed for now
                log: fn (string $line) => $this->line('[core-update] '.$line),
            );

            $this->newLine();
            $this->info("✓ Core updated: v{$result['from']} -> v{$result['to']}");
            $this->line("  history id: {$result['history_id']}");
            $this->line("  snapshot:   {$result['snapshot']}");
            if (! empty($result['backup_record_id'])) {
                $this->line("  db backup:  record #{$result['backup_record_id']} (kept for manual restore)");
            }
            $this->newLine();
            $this->warn('Next steps (manual):');
            $this->line('  - composer install --no-dev (if composer.json changed)');
            $this->line('  - npm install && npm run build (if package.json or assets changed)');
            $this->line('  - Restart PHP-FPM / queue workers');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("✗ Core update failed: {$e->getMessage()}");
            $this->line('Source files were rolled back from snapshot if possible. Inspect the output above for details.');

            return self::FAILURE;
        }
    }
}
