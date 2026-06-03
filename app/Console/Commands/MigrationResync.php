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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Realign the `migrations` bookkeeping table to the current migration filenames.
 *
 * During the beta series (Beta 1 → GA) core migrations are re-sorted/renumbered
 * (see CLAUDE.md "Migration Editing Policy"). Renumbering renames the files, but
 * Laravel records applied migrations by filename, so an already-migrated database
 * (a live brand/demo/production site) would then see every renamed file as
 * "pending" and try to re-run it, hitting "table already exists".
 *
 * This command fixes that WITHOUT a destructive `migrate:fresh`: only the table
 * structure (not the filename) is invariant under a re-sort, so we match each
 * recorded migration to the current file by the stable suffix (everything after
 * the `YYYY_MM_DD_NNNNNN_` prefix, e.g. `create_signature_waivers_table`) and
 * UPDATE the recorded name to the current filename.
 *
 * It only ever touches the `migrations` bookkeeping table — never data tables,
 * never the schema, and it does not run any migration. After a resync, run
 * `php artisan migrate` to apply genuinely-new migrations.
 *
 * Default is a dry-run preview; pass --confirm to apply.
 */
class MigrationResync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:migration:resync
                            {--confirm : Apply the changes (default is a dry-run preview)}
                            {--json : Output the result as JSON}
                            {--base-path= : Override the scan base path (testing only; production must not set this)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realign the migrations table to current migration filenames after a re-sort (updates only the migration column; never touches data tables)';

    public function handle(): int
    {
        $table = $this->migrationsTable();

        if (! $this->migrationsTableExists($table)) {
            return $this->report([], [], [], 'no_migrations_table', $table);
        }

        $fileMap = $this->buildSuffixToNameMap();
        $recorded = DB::table($table)->orderBy('id')->get(['id', 'migration']);

        $plan = [];        // rows whose recorded name must change
        $skipped = [];     // recorded rows with no matching current file
        $existingNames = $recorded->pluck('migration')->all();

        foreach ($recorded as $row) {
            $suffix = $this->suffixOf($row->migration);

            if ($suffix === null || ! isset($fileMap[$suffix])) {
                // Not a core migration we can match (plugin/theme tracking, or
                // an orphaned record). Leave it untouched.
                $skipped[] = $row->migration;

                continue;
            }

            $newName = $fileMap[$suffix];

            if ($newName === $row->migration) {
                continue; // already aligned (idempotent)
            }

            if (in_array($newName, $existingNames, true)) {
                // The target name is already recorded — refuse to create a
                // duplicate. This should not happen in a clean re-sort.
                $skipped[] = $row->migration;

                continue;
            }

            $plan[] = [
                'id' => $row->id,
                'suffix' => $suffix,
                'from' => $row->migration,
                'to' => $newName,
            ];
        }

        // Current files that have no recorded row = genuinely pending migrations
        // (informational; `php artisan migrate` will apply them).
        $recordedSuffixes = [];
        foreach ($recorded as $row) {
            $s = $this->suffixOf($row->migration);
            if ($s !== null) {
                $recordedSuffixes[$s] = true;
            }
        }
        $pending = [];
        foreach ($fileMap as $suffix => $name) {
            if (! isset($recordedSuffixes[$suffix])) {
                $pending[] = $name;
            }
        }
        sort($pending);

        if (empty($plan)) {
            return $this->report($plan, $skipped, $pending, 'clean', $table);
        }

        if (! $this->option('confirm')) {
            return $this->report($plan, $skipped, $pending, 'dry_run', $table);
        }

        DB::transaction(function () use ($table, $plan): void {
            foreach ($plan as $change) {
                DB::table($table)
                    ->where('id', $change['id'])
                    ->update(['migration' => $change['to']]);
            }
        });

        return $this->report($plan, $skipped, $pending, 'applied', $table);
    }

    /**
     * Resolve the migrations bookkeeping table name from config
     * (Laravel 11 allows database.migrations to be an array).
     */
    protected function migrationsTable(): string
    {
        $cfg = config('database.migrations');

        if (is_array($cfg)) {
            return $cfg['table'] ?? 'migrations';
        }

        return is_string($cfg) && $cfg !== '' ? $cfg : 'migrations';
    }

    protected function migrationsTableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    /**
     * Map the stable suffix of every current core migration file to its full
     * migration name (filename without `.php`).
     *
     * @return array<string, string> suffix => migration name
     */
    protected function buildSuffixToNameMap(): array
    {
        $dir = $this->basePath().'/database/migrations';
        $map = [];

        foreach (glob($dir.'/*.php') ?: [] as $file) {
            $name = basename($file, '.php');

            // Skip underscore-prefixed backups (Laravel ignores these too).
            if (str_starts_with($name, '_')) {
                continue;
            }

            $suffix = $this->suffixOf($name);
            if ($suffix === null) {
                continue;
            }

            $map[$suffix] = $name;
        }

        return $map;
    }

    /**
     * Strip the `YYYY_MM_DD_NNNNNN_` timestamp prefix, leaving the stable
     * identifier (e.g. `create_signature_waivers_table`). The table structure
     * this refers to is invariant under a re-sort, so it is a safe match key.
     */
    protected function suffixOf(string $migrationName): ?string
    {
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $migrationName, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /**
     * Scan base path. Tests can override this via --base-path.
     */
    protected function basePath(): string
    {
        return $this->option('base-path') ?: base_path();
    }

    /**
     * Emit the result (JSON or human-readable) and return the exit code.
     *
     * @param  array<int, array{id:int, suffix:string, from:string, to:string}>  $plan
     * @param  array<int, string>  $skipped
     * @param  array<int, string>  $pending
     */
    protected function report(array $plan, array $skipped, array $pending, string $status, string $table): int
    {
        if ($this->option('json')) {
            $this->line(json_encode([
                'action' => 'resync',
                'status' => $status,
                'migrations_table' => $table,
                'changes' => array_map(fn (array $c) => ['from' => $c['from'], 'to' => $c['to']], $plan),
                'change_count' => count($plan),
                'skipped' => $skipped,
                'pending' => $pending,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        if ($status === 'no_migrations_table') {
            $this->warn(sprintf('Migrations table "%s" does not exist — nothing to resync.', $table));

            return Command::SUCCESS;
        }

        if ($status === 'clean') {
            $this->info('✓ The migrations table is already aligned with the current filenames.');
            $this->printPending($pending);

            return Command::SUCCESS;
        }

        $this->line(sprintf(
            '%s %d migration record(s) to realign:',
            $status === 'applied' ? '<fg=green>Updated</>' : '<fg=yellow>[DRY-RUN]</> Would update',
            count($plan)
        ));
        foreach ($plan as $change) {
            $this->line(sprintf('  %s', $change['from']));
            $this->line(sprintf('    <fg=green>→</> %s', $change['to']));
        }
        $this->newLine();

        if (! empty($skipped)) {
            $this->line(sprintf('<fg=gray>Skipped %d record(s) with no matching core migration file (left untouched):</>', count($skipped)));
            foreach ($skipped as $name) {
                $this->line('  <fg=gray>·</> '.$name);
            }
            $this->newLine();
        }

        $this->printPending($pending);

        if ($status === 'dry_run') {
            $this->warn('Dry-run only. Re-run with --confirm to apply, then run `php artisan migrate`.');
        } else {
            $this->info('Done. Now run `php artisan migrate` to apply any genuinely-new migrations.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param  array<int, string>  $pending
     */
    protected function printPending(array $pending): void
    {
        if (empty($pending)) {
            return;
        }

        $this->line(sprintf('<fg=cyan>%d pending migration(s) not yet applied (run `php artisan migrate`):</>', count($pending)));
        foreach ($pending as $name) {
            $this->line('  <fg=cyan>+</> '.$name);
        }
        $this->newLine();
    }
}
