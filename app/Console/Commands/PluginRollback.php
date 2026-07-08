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

use App\Console\Traits\TakesExtensionBackup;
use App\Models\Plugin;
use App\Services\Extension\ExtensionSourceSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Roll a plugin back to the state captured before its last update.
 *
 * Plugin counterpart of {@see ThemeRollback}. Restores the source tree AND
 * the prebuilt resources/assets from the automatic pre-update backup
 * ({@see TakesExtensionBackup}), refreshes the public asset symlink, and
 * delegates the schema half to dls:plugin:migrate:rollback. No npm is run.
 */
class PluginRollback extends Command
{
    use TakesExtensionBackup;

    protected $signature = 'dls:plugin:rollback
        {slug : Plugin slug to roll back}
        {--to= : Restore a specific backup timestamp (default: the newest)}
        {--force : Skip confirmation}';

    protected $description = 'Roll a plugin back to the state captured before its last update';

    public function handle(): int
    {
        $slug = $this->argument('slug');

        $plugin = Plugin::query()->where('slug', $slug)->first();
        if (! $plugin) {
            $this->error("Plugin '{$slug}' is not installed.");

            return self::FAILURE;
        }

        $kind = ExtensionSourceSnapshot::KIND_PLUGIN;
        $dir = $plugin->directory;

        $to = (string) $this->option('to');
        $backupPath = $this->resolveExtensionBackupPath($kind, $dir, $to);
        if ($backupPath === null) {
            $this->error($to !== ''
                ? "No backup '{$to}' found for plugin '{$slug}'."
                : "No backup found for plugin '{$slug}'. Nothing to roll back to.");
            $available = $this->listExtensionBackups($kind, $dir);
            if ($available !== []) {
                $this->line('Available backups: '.implode(', ', $available));
            }

            return self::FAILURE;
        }

        $livePath = base_path("plugins/{$dir}");

        $this->warn('Rollback is a DESTRUCTIVE operation that replaces the current plugin tree.');
        $this->line("Plugin: {$slug} ({$dir})");
        $this->line('Backup: '.basename($backupPath));

        if (! $this->option('force') && ! $this->confirm('Continue with rollback?', false)) {
            $this->info('Rollback cancelled.');

            return self::SUCCESS;
        }

        try {
            $asidePath = $this->restoreExtensionBackupInto($backupPath, $livePath);
            $this->info('Plugin source + prebuilt assets restored from backup (no npm run).');

            // Point the public asset symlink at the restored resources/assets.
            Artisan::call('dls:plugin:symlink', ['action' => 'create', 'plugin' => $dir]);

            // Delegate the schema half so the operator does not have to run
            // a second command; best-effort, mirrors dls:plugin:update.
            Artisan::call('dls:plugin:migrate:rollback', ['plugin' => $dir, '--force' => true]);

            // Keep the recorded version in step with the restored plugin.json.
            $this->syncPluginVersion($plugin, $livePath);

            Artisan::call('view:clear');

            $this->info("Plugin '{$slug}' rolled back to backup ".basename($backupPath).'.');
            $this->line("Previous (pre-rollback) tree kept at: {$asidePath}");
            $this->line('To undo this rollback, move that directory back into place.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Rollback failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Sync the DB version column to the version declared in the restored
     * plugin.json so a subsequent update detects the correct baseline.
     */
    private function syncPluginVersion(Plugin $plugin, string $livePath): void
    {
        $jsonPath = $livePath.'/plugin.json';
        if (! is_file($jsonPath)) {
            return;
        }

        $data = json_decode((string) File::get($jsonPath), true);
        $version = is_array($data) ? ($data['version'] ?? null) : null;
        if (is_string($version) && $version !== '') {
            $plugin->update(['version' => $version, 'available_version' => null]);
        }
    }
}
