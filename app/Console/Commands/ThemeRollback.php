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
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Roll a theme back to the state captured before its last update.
 *
 * Counterpart to dls:core:update's rollback flow for themes. Restores the
 * source tree AND the prebuilt resources/assets from the automatic
 * pre-update backup ({@see TakesExtensionBackup}), refreshes the public
 * asset symlink, and delegates the schema half to dls:theme:migrate:rollback.
 * No npm is run — the backed-up assets are restored verbatim.
 */
class ThemeRollback extends Command
{
    use TakesExtensionBackup;

    protected $signature = 'dls:theme:rollback
        {slug : Theme slug to roll back}
        {--to= : Restore a specific backup timestamp (default: the newest)}
        {--force : Skip confirmation}';

    protected $description = 'Roll a theme back to the state captured before its last update';

    public function handle(): int
    {
        $slug = $this->argument('slug');

        $theme = Theme::query()->where('slug', $slug)->first();
        if (! $theme) {
            $this->error("Theme '{$slug}' is not installed.");

            return self::FAILURE;
        }

        $kind = ExtensionSourceSnapshot::KIND_THEME;
        $dir = $theme->directory;

        $to = (string) $this->option('to');
        $backupPath = $this->resolveExtensionBackupPath($kind, $dir, $to);
        if ($backupPath === null) {
            $this->error($to !== ''
                ? "No backup '{$to}' found for theme '{$slug}'."
                : "No backup found for theme '{$slug}'. Nothing to roll back to.");
            $available = $this->listExtensionBackups($kind, $dir);
            if ($available !== []) {
                $this->line('Available backups: '.implode(', ', $available));
            }

            return self::FAILURE;
        }

        $livePath = base_path("themes/{$dir}");

        $this->warn('Rollback is a DESTRUCTIVE operation that replaces the current theme tree.');
        $this->line("Theme:  {$slug} ({$dir})");
        $this->line('Backup: '.basename($backupPath));

        if (! $this->option('force') && ! $this->confirm('Continue with rollback?', false)) {
            $this->info('Rollback cancelled.');

            return self::SUCCESS;
        }

        try {
            $asidePath = $this->restoreExtensionBackupInto($backupPath, $livePath);
            $this->info('Theme source + prebuilt assets restored from backup (no npm run).');

            // Point the public asset symlink at the restored resources/assets.
            Artisan::call('dls:theme:symlink', ['action' => 'create', 'theme' => $dir]);

            // Delegate the schema half so the operator does not have to run
            // a second command; best-effort, mirrors dls:theme:update.
            Artisan::call('dls:theme:migrate:rollback', ['theme' => $dir, '--force' => true]);

            // Keep the recorded version in step with the restored theme.json.
            $this->syncThemeVersion($theme, $livePath);

            Artisan::call('view:clear');

            $this->info("Theme '{$slug}' rolled back to backup ".basename($backupPath).'.');
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
     * theme.json so a subsequent update detects the correct baseline.
     */
    private function syncThemeVersion(Theme $theme, string $livePath): void
    {
        $jsonPath = $livePath.'/theme.json';
        if (! is_file($jsonPath)) {
            return;
        }

        $data = json_decode((string) File::get($jsonPath), true);
        $version = is_array($data) ? ($data['version'] ?? null) : null;
        if (is_string($version) && $version !== '') {
            $theme->update(['version' => $version, 'available_version' => null]);
        }
    }
}
