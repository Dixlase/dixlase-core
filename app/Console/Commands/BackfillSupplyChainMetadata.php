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

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill supply-chain metadata for plugins and themes installed before
 * v0.1.0 carried these columns.
 *
 * Reads `author_id`, `authority_key_id`, and `signing.key_id` from each
 * `plugin.json` / `theme.json` on disk and writes them onto the matching
 * Plugin / Theme row only when the row's value is currently null. Existing
 * non-null values are left untouched so re-running the command is safe.
 *
 * Run this once on any environment that was seeded before the supply-chain
 * columns landed; otherwise the first user-driven update on such a record
 * looks like a fresh install and the `signing_key_changed` / `author_id_changed`
 * flags can never fire.
 */
class BackfillSupplyChainMetadata extends Command
{
    protected $signature = 'dls:plugin:backfill-supply-chain
                            {--dry-run : Show what would be updated without writing}
                            {--themes-only : Skip plugins and only process themes}
                            {--plugins-only : Skip themes and only process plugins}';

    protected $description = 'Populate supply-chain columns (author_id, authority_key_id, signing_key_id) on plugins and themes from on-disk manifest files. Idempotent: only fills null columns.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $skipThemes = (bool) $this->option('plugins-only');
        $skipPlugins = (bool) $this->option('themes-only');

        if ($skipThemes && $skipPlugins) {
            $this->error('--themes-only and --plugins-only are mutually exclusive.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Dry run mode: no rows will be updated.');
        }

        $pluginsTouched = $skipPlugins ? 0 : $this->backfillPlugins($dryRun);
        $themesTouched = $skipThemes ? 0 : $this->backfillThemes($dryRun);

        $this->newLine();
        $this->info(sprintf(
            '%s plugin row(s), %s theme row(s) %s.',
            $pluginsTouched,
            $themesTouched,
            $dryRun ? 'would be updated' : 'updated'
        ));

        return self::SUCCESS;
    }

    private function backfillPlugins(bool $dryRun): int
    {
        $this->line('Scanning plugins…');

        $touched = 0;
        $plugins = Plugin::query()
            ->where(function ($q) {
                $q->whereNull('author_id')
                    ->orWhereNull('authority_key_id')
                    ->orWhereNull('signing_key_id');
            })
            ->get();

        foreach ($plugins as $plugin) {
            $jsonPath = base_path("plugins/{$plugin->directory}/plugin.json");
            $data = $this->readManifest($jsonPath);
            if ($data === null) {
                $this->line("  - {$plugin->slug}: plugin.json missing or invalid, skipped.");

                continue;
            }

            $updates = $this->buildUpdates($plugin, $data);
            if ($updates === []) {
                continue;
            }

            $this->line(sprintf(
                '  + %s: %s',
                $plugin->slug,
                implode(', ', array_keys($updates))
            ));

            if (! $dryRun) {
                $plugin->update($updates);
            }
            $touched++;
        }

        return $touched;
    }

    private function backfillThemes(bool $dryRun): int
    {
        $this->line('Scanning themes…');

        // Pre-v0.1.0 environments may not yet carry the supply-chain columns
        // on the themes table. Skip gracefully so plugins still backfill.
        if (! Schema::hasColumn('themes', 'author_id')) {
            $this->warn('  themes table missing supply-chain columns; run migrate:fresh first. Skipped.');

            return 0;
        }

        $touched = 0;
        $themes = Theme::query()
            ->where(function ($q) {
                $q->whereNull('author_id')
                    ->orWhereNull('authority_key_id')
                    ->orWhereNull('signing_key_id');
            })
            ->get();

        foreach ($themes as $theme) {
            $jsonPath = base_path("themes/{$theme->directory}/theme.json");
            $data = $this->readManifest($jsonPath);
            if ($data === null) {
                $this->line("  - {$theme->slug}: theme.json missing or invalid, skipped.");

                continue;
            }

            $updates = $this->buildUpdates($theme, $data);
            if ($updates === []) {
                continue;
            }

            $this->line(sprintf(
                '  + %s: %s',
                $theme->slug,
                implode(', ', array_keys($updates))
            ));

            if (! $dryRun) {
                $theme->update($updates);
            }
            $touched++;
        }

        return $touched;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readManifest(string $path): ?array
    {
        if (! File::exists($path)) {
            return null;
        }

        $data = json_decode(File::get($path), true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Build an Eloquent update payload that only touches columns currently null.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildUpdates(Plugin|Theme $record, array $data): array
    {
        $candidates = [
            'author_id' => $data['author_id'] ?? null,
            'authority_key_id' => $data['authority_key_id'] ?? null,
            'signing_key_id' => $data['signing']['key_id'] ?? null,
        ];

        $updates = [];
        foreach ($candidates as $column => $value) {
            if ($value === null) {
                continue;
            }
            if ($record->getAttribute($column) !== null) {
                continue;
            }
            $updates[$column] = $value;
        }

        return $updates;
    }
}
