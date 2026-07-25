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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Realign the migration bookkeeping tables to the current migration filenames.
 *
 * During the beta series (Beta 1 → GA) core, plugin, and theme migrations
 * are re-sorted/renumbered (see CLAUDE.md "Migration Editing Policy").
 * Renumbering renames the files, but Laravel records applied migrations
 * by filename, so an already-migrated database (a live brand/demo/production
 * site) would then see every renamed file as "pending" and try to re-run
 * it, hitting "table already exists".
 *
 * This command fixes that WITHOUT a destructive `migrate:fresh`: only the
 * table structure (not the filename) is invariant under a re-sort, so we
 * match each recorded migration to the current file by the stable suffix
 * (everything after the `YYYY_MM_DD_NNNNNN_` prefix, e.g.
 * `create_signature_waivers_table`) and UPDATE the recorded name to the
 * current filename.
 *
 * Three independent scopes are walked, each against its own bookkeeping
 * table:
 *
 *   - **core**     — files in `database/migrations/`, ledger
 *                    `migrations` (Laravel default; resolved via
 *                    `config('database.migrations')`).
 *   - **plugin**   — files in `plugins/<dir>/database/migrations/`,
 *                    ledger `plugin_migrations` filtered by the
 *                    `plugin` column on the slug declared in
 *                    `plugins/<dir>/plugin.json`. One walk per
 *                    installed plugin.
 *   - **theme**    — files in `themes/<dir>/database/migrations/`,
 *                    ledger `theme_migrations` filtered by the
 *                    `theme` column on the slug declared in
 *                    `themes/<dir>/theme.json`. One walk per
 *                    installed theme.
 *
 * The Plugin/ThemeMigrator constructors hard-code those table names
 * (`plugin_migrations`, `theme_migrations`); they are the contract
 * the rest of the codebase depends on (see `PluginInstall`,
 * `PluginUninstall`, `PluginUpdate`, `ThemeInstall`, etc.), so this
 * command treats them as constants too.
 *
 * Within a single scope, two files shipping the same suffix are
 * dropped from the realignment and reported as a collision — leaving
 * the ledger row untouched is safer than guessing. Cross-scope
 * same-suffix is no longer a collision: each scope has its own
 * ledger table, so a core migration named `create_foo_table` cannot
 * be confused with a plugin migration named the same.
 *
 * It only ever touches migration bookkeeping rows — never data
 * tables, never the schema, and it does not run any migration. After
 * a resync, run `php artisan migrate` to apply genuinely-new
 * migrations.
 *
 * Default is a dry-run preview; pass --confirm to apply.
 *
 * **--prune** opts in to deleting the "skipped" rows whose migration
 * file no longer exists on disk. Without this flag, skipped rows are
 * preserved (the safe default — orphaned ledger entries are usually
 * historical state worth keeping). With it, the same rows are reported
 * as delete candidates and, under --confirm, removed within the same
 * transaction as the realign UPDATEs. The DELETE is keyed on the
 * row's primary key (with the scope's filter re-applied as belt-and-
 * suspenders) so a future row whose name happens to collide with a
 * pruned one cannot be removed by accident.
 */
class MigrationResync extends Command
{
    /**
     * Plugin/theme bookkeeping tables are hard-coded in the matching
     * `PluginMigrator` / `ThemeMigrator` constructor defaults and
     * referenced verbatim by every install/uninstall/update command.
     * Keep these in sync with those constructors if they ever change.
     */
    protected const PLUGIN_MIGRATIONS_TABLE = 'plugin_migrations';

    protected const THEME_MIGRATIONS_TABLE = 'theme_migrations';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:migration:resync
                            {--confirm : Apply the changes (default is a dry-run preview)}
                            {--prune : Also delete "skipped" rows whose migration file is gone from disk (default is to leave them untouched)}
                            {--json : Output the result as JSON}
                            {--base-path= : Override the scan base path (testing only; production must not set this)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realign the core / plugin / theme migration bookkeeping tables to current migration filenames after a re-sort (updates only the migration column; never touches data tables)';

    public function handle(): int
    {
        $scopes = $this->collectScopeResults();
        $prune  = (bool) $this->option('prune');

        $totalChanges = array_sum(array_map(fn (array $s) => count($s['changes']), $scopes));
        $totalPrunes  = $prune
            ? array_sum(array_map(fn (array $s) => count($s['skipped']), $scopes))
            : 0;

        if ($totalChanges === 0 && $totalPrunes === 0) {
            return $this->report($scopes, 'clean');
        }

        if (! $this->option('confirm')) {
            return $this->report($scopes, 'dry_run');
        }

        DB::transaction(function () use ($scopes, $prune): void {
            foreach ($scopes as $scope) {
                foreach ($scope['changes'] as $change) {
                    $query = DB::table($scope['table'])->where('id', $change['id']);

                    if ($scope['filter_column'] !== null) {
                        $query->where($scope['filter_column'], $scope['filter_value']);
                    }

                    $query->update(['migration' => $change['to']]);
                }

                if (! $prune) {
                    continue;
                }

                // Delete by id (carried in the skipped entry) so a
                // future row whose name collides with a deleted name
                // cannot be removed by accident; the filter column is
                // re-applied for the same belt-and-suspenders reason
                // the rename branch above uses it.
                foreach ($scope['skipped'] as $row) {
                    $query = DB::table($scope['table'])->where('id', $row['id']);

                    if ($scope['filter_column'] !== null) {
                        $query->where($scope['filter_column'], $scope['filter_value']);
                    }

                    $query->delete();
                }
            }
        });

        return $this->report($scopes, 'applied');
    }

    /**
     * Walk every scope and assemble its resync result. Each entry has
     * a uniform shape regardless of whether it represents core,
     * a single plugin, or a single theme.
     *
     * Skipped rows carry their id alongside the migration name so
     * `--prune` can DELETE by primary key (safer than DELETE-by-name
     * in the presence of historical name reuse).
     *
     * @return list<array{
     *     scope: string,
     *     table: string,
     *     filter_column: ?string,
     *     filter_value: ?string,
     *     present: bool,
     *     changes: list<array{id:int, suffix:string, from:string, to:string}>,
     *     skipped: list<array{id:int, migration:string}>,
     *     pending: list<string>,
     *     collisions: array<string, list<string>>,
     * }>
     */
    protected function collectScopeResults(): array
    {
        $results = [];
        $results[] = $this->resyncScope(
            scope: 'core',
            table: $this->coreLedgerTable(),
            filterColumn: null,
            filterValue: null,
            migrationsDir: $this->basePath().'/database/migrations',
        );

        foreach ($this->discoverExtensions('plugins', 'plugin.json') as $extension) {
            $results[] = $this->resyncScope(
                scope: 'plugin:'.$extension['slug'],
                table: self::PLUGIN_MIGRATIONS_TABLE,
                filterColumn: 'plugin',
                filterValue: $extension['slug'],
                migrationsDir: $extension['migrations_dir'],
            );
        }

        foreach ($this->discoverExtensions('themes', 'theme.json') as $extension) {
            $results[] = $this->resyncScope(
                scope: 'theme:'.$extension['slug'],
                table: self::THEME_MIGRATIONS_TABLE,
                filterColumn: 'theme',
                filterValue: $extension['slug'],
                migrationsDir: $extension['migrations_dir'],
            );
        }

        return $results;
    }

    /**
     * Resync a single scope. A scope is the pair of:
     *   - a migrations directory on disk, and
     *   - a (table, optional filter column/value) ledger query.
     *
     * For core, filter_column is null and every row in the table is
     * considered. For plugin / theme scopes the filter pins us to one
     * extension's slug so a different plugin's rename cannot pollute
     * our matching.
     *
     * @return array{
     *     scope: string,
     *     table: string,
     *     filter_column: ?string,
     *     filter_value: ?string,
     *     present: bool,
     *     changes: list<array{id:int, suffix:string, from:string, to:string}>,
     *     skipped: list<array{id:int, migration:string}>,
     *     pending: list<string>,
     *     collisions: array<string, list<string>>,
     * }
     */
    protected function resyncScope(
        string $scope,
        string $table,
        ?string $filterColumn,
        ?string $filterValue,
        string $migrationsDir,
    ): array {
        $base = [
            'scope' => $scope,
            'table' => $table,
            'filter_column' => $filterColumn,
            'filter_value' => $filterValue,
            'present' => false,
            'changes' => [],
            'skipped' => [],
            'pending' => [],
            'collisions' => [],
        ];

        if (! DB::getSchemaBuilder()->hasTable($table)) {
            // Table is absent (very fresh install before plugin / theme
            // migrations have ever been recorded, or a deliberately
            // stripped schema). Nothing to resync.
            return $base;
        }

        $base['present'] = true;

        $collisions = [];
        $fileMap = $this->buildSuffixMap($migrationsDir, $collisions);
        $base['collisions'] = $collisions;

        $query = DB::table($table)->orderBy('id');
        if ($filterColumn !== null) {
            $query->where($filterColumn, $filterValue);
        }
        $recorded = $query->get(['id', 'migration']);

        $existingNames = $recorded->pluck('migration')->all();

        foreach ($recorded as $row) {
            $suffix = $this->suffixOf($row->migration);

            if ($suffix === null || ! isset($fileMap[$suffix])) {
                // No matching current file in this scope's directory
                // (orphaned record from a deleted file, or a suffix
                // dropped because of a within-scope collision).
                $base['skipped'][] = ['id' => (int) $row->id, 'migration' => $row->migration];

                continue;
            }

            $newName = $fileMap[$suffix];

            if ($newName === $row->migration) {
                continue; // already aligned (idempotent)
            }

            if (in_array($newName, $existingNames, true)) {
                // The target name is already recorded — refuse to create
                // a duplicate. Should not happen in a clean re-sort.
                $base['skipped'][] = ['id' => (int) $row->id, 'migration' => $row->migration];

                continue;
            }

            $base['changes'][] = [
                'id' => (int) $row->id,
                'suffix' => $suffix,
                'from' => $row->migration,
                'to' => $newName,
            ];
        }

        // Files that have no recorded row for this scope = genuinely
        // pending migrations (informational; `php artisan migrate`
        // applies core ones, `dls:plugin:install/update` etc. apply
        // plugin/theme ones).
        $recordedSuffixes = [];
        foreach ($recorded as $row) {
            $s = $this->suffixOf($row->migration);
            if ($s !== null) {
                $recordedSuffixes[$s] = true;
            }
        }
        foreach ($fileMap as $suffix => $name) {
            if (! isset($recordedSuffixes[$suffix])) {
                $base['pending'][] = $name;
            }
        }
        sort($base['pending']);

        return $base;
    }

    /**
     * Resolve the core migrations bookkeeping table name from config
     * (Laravel 11 allows database.migrations to be an array).
     */
    protected function coreLedgerTable(): string
    {
        $cfg = config('database.migrations');

        if (is_array($cfg)) {
            return $cfg['table'] ?? 'migrations';
        }

        return is_string($cfg) && $cfg !== '' ? $cfg : 'migrations';
    }

    /**
     * Discover every installed extension of one kind (plugin or theme).
     *
     * Each entry carries the slug (read from the manifest's `slug`
     * field, falling back to the directory basename if the manifest
     * is missing or has no slug) and the absolute path to that
     * extension's migrations directory. Extensions without a
     * `database/migrations/` directory are excluded — there is nothing
     * to resync for them.
     *
     * @return list<array{slug: string, migrations_dir: string}>
     */
    protected function discoverExtensions(string $rootSubdir, string $manifestName): array
    {
        $base = $this->basePath();
        $extensions = [];

        foreach (glob($base.'/'.$rootSubdir.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $migrationsDir = $dir.'/database/migrations';
            if (! is_dir($migrationsDir)) {
                continue;
            }

            $slug = $this->slugFromManifest($dir.'/'.$manifestName)
                ?? basename($dir);

            $extensions[] = [
                'slug' => $slug,
                'migrations_dir' => $migrationsDir,
            ];
        }

        // Sort by slug so the output is stable across runs (otherwise
        // glob() returns filesystem-order which is unstable on case-
        // insensitive volumes etc.).
        usort($extensions, fn (array $a, array $b) => strcmp($a['slug'], $b['slug']));

        return $extensions;
    }

    /**
     * Read the `slug` field from a plugin.json or theme.json manifest.
     * Returns null on missing / unparseable / no-slug.
     */
    protected function slugFromManifest(string $manifestPath): ?string
    {
        if (! is_file($manifestPath)) {
            return null;
        }

        $raw = @file_get_contents($manifestPath);
        if ($raw === false) {
            return null;
        }

        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return null;
        }

        $slug = $data['slug'] ?? null;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    /**
     * Map the stable suffix of every migration file in `$dir` to its
     * full migration name (filename without `.php`). Suffix
     * collisions within the directory are dropped from the map and
     * recorded in `$collisions` so the report can warn the operator.
     *
     * @param  array<string, list<string>>  $collisions
     * @return array<string, string> suffix => migration name
     */
    protected function buildSuffixMap(string $dir, array &$collisions): array
    {
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

            if (isset($map[$suffix])) {
                $collisions[$suffix] = array_values(array_unique(array_merge(
                    $collisions[$suffix] ?? [$map[$suffix]],
                    [$name],
                )));
                unset($map[$suffix]);

                continue;
            }

            if (isset($collisions[$suffix])) {
                $collisions[$suffix][] = $name;
                $collisions[$suffix] = array_values(array_unique($collisions[$suffix]));

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
     * @param  list<array{
     *     scope: string,
     *     table: string,
     *     filter_column: ?string,
     *     filter_value: ?string,
     *     present: bool,
     *     changes: list<array{id:int, suffix:string, from:string, to:string}>,
     *     skipped: list<array{id:int, migration:string}>,
     *     pending: list<string>,
     *     collisions: array<string, list<string>>,
     * }>  $scopes
     */
    protected function report(array $scopes, string $status): int
    {
        $totalChanges = array_sum(array_map(fn (array $s) => count($s['changes']), $scopes));
        $prune        = (bool) $this->option('prune');
        $totalPrunes  = $prune
            ? array_sum(array_map(fn (array $s) => count($s['skipped']), $scopes))
            : 0;

        if ($this->option('json')) {
            $this->line(json_encode([
                'action' => 'resync',
                'status' => $status,
                'prune' => $prune,
                'total_changes' => $totalChanges,
                'total_prunes' => $totalPrunes,
                'scopes' => array_map(fn (array $s) => [
                    'scope' => $s['scope'],
                    'table' => $s['table'],
                    'present' => $s['present'],
                    'changes' => array_map(fn (array $c) => ['from' => $c['from'], 'to' => $c['to']], $s['changes']),
                    'change_count' => count($s['changes']),
                    // Preserve the historical `skipped: list<string>` shape
                    // for JSON consumers — the id is an implementation
                    // detail of --prune, not part of the public contract.
                    'skipped' => array_map(fn (array $r) => $r['migration'], $s['skipped']),
                    'skipped_count' => count($s['skipped']),
                    'pending' => $s['pending'],
                    'collisions' => $s['collisions'],
                ], $scopes),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        if ($status === 'clean') {
            $this->info('✓ Every migration bookkeeping table is already aligned with the current filenames.');
            foreach ($scopes as $scope) {
                if (empty($scope['pending']) && $scope['collisions'] === []) {
                    continue;
                }

                $this->newLine();
                $this->line(sprintf('<fg=cyan>[scope]</> %s <fg=gray>(table: %s)</>', $scope['scope'], $scope['table']));
                $this->printScopePending($scope);
                $this->printScopeCollisions($scope);
            }

            return Command::SUCCESS;
        }

        $this->line($this->buildHeadline($status, $totalChanges, $totalPrunes, $prune));
        $this->newLine();

        foreach ($scopes as $scope) {
            if (empty($scope['changes']) && empty($scope['skipped']) && empty($scope['pending']) && empty($scope['collisions'])) {
                continue;
            }

            $this->line(sprintf('<fg=cyan>[scope]</> %s <fg=gray>(table: %s)</>', $scope['scope'], $scope['table']));

            if (! empty($scope['changes'])) {
                foreach ($scope['changes'] as $change) {
                    $this->line(sprintf('  %s', $change['from']));
                    $this->line(sprintf('    <fg=green>→</> %s', $change['to']));
                }
            }

            if (! empty($scope['skipped'])) {
                $this->line($this->buildSkippedSectionHeader(count($scope['skipped']), $status, $prune));
                foreach ($scope['skipped'] as $row) {
                    $this->line('    <fg=gray>·</> '.$row['migration']);
                }
            }

            $this->printScopePending($scope);
            $this->printScopeCollisions($scope);

            $this->newLine();
        }

        if ($status === 'dry_run') {
            $hint = $prune
                ? 'Dry-run only. Re-run with --prune --confirm to apply the prunes (and any renames), then run `php artisan migrate` (and any extension install/update commands) to apply genuinely-new migrations.'
                : 'Dry-run only. Re-run with --confirm to apply, then run `php artisan migrate` (and any extension install/update commands) to apply genuinely-new migrations.';
            $this->warn($hint);
        } else {
            $this->info('Done. Now run `php artisan migrate` and re-run extension install/update commands to apply genuinely-new migrations.');
        }

        return Command::SUCCESS;
    }

    /**
     * Build the top-of-report headline. Renders the action verb (Updated
     * / Would update / Pruned / Would prune) plus, when --prune is on,
     * a "+ N pruned" tail so the operator sees both counts in one line.
     */
    protected function buildHeadline(string $status, int $totalChanges, int $totalPrunes, bool $prune): string
    {
        $isApplied = $status === 'applied';

        if ($totalChanges > 0 && (! $prune || $totalPrunes === 0)) {
            return sprintf(
                '%s %d migration record(s) to realign across all scopes:',
                $isApplied ? '<fg=green>Updated</>' : '<fg=yellow>[DRY-RUN]</> Would update',
                $totalChanges,
            );
        }

        if ($totalChanges === 0 && $prune && $totalPrunes > 0) {
            return sprintf(
                '%s %d orphan ledger row(s) across all scopes:',
                $isApplied ? '<fg=green>Pruned</>' : '<fg=yellow>[DRY-RUN]</> Would prune',
                $totalPrunes,
            );
        }

        return sprintf(
            '%s %d migration record(s) and %s %d orphan ledger row(s) across all scopes:',
            $isApplied ? '<fg=green>Updated</>' : '<fg=yellow>[DRY-RUN]</> Would update',
            $totalChanges,
            $isApplied ? '<fg=green>pruned</>' : 'would prune',
            $totalPrunes,
        );
    }

    /**
     * Format the per-scope header for the "skipped" section. The header
     * changes wording depending on whether the operator opted in to
     * --prune (default leaves the rows in place; --prune treats them
     * as delete candidates).
     */
    protected function buildSkippedSectionHeader(int $count, string $status, bool $prune): string
    {
        if (! $prune) {
            return sprintf('  <fg=gray>Skipped %d record(s) with no matching file (left untouched):</>', $count);
        }

        $verb = $status === 'applied' ? '<fg=red>Pruned</>' : '<fg=yellow>Would prune</>';

        return sprintf('  %s %d record(s) with no matching file:', $verb, $count);
    }

    /**
     * @param  array{scope: string, pending: list<string>}  $scope
     */
    protected function printScopePending(array $scope): void
    {
        if (empty($scope['pending'])) {
            return;
        }

        $this->line(sprintf(
            '  <fg=cyan>%d pending migration(s) in this scope:</>',
            count($scope['pending']),
        ));
        foreach ($scope['pending'] as $name) {
            $this->line('    <fg=cyan>+</> '.$name);
        }
    }

    /**
     * @param  array{scope: string, collisions: array<string, list<string>>}  $scope
     */
    protected function printScopeCollisions(array $scope): void
    {
        if ($scope['collisions'] === []) {
            return;
        }

        $this->warn(sprintf(
            '  %d suffix(es) collided within this scope — those ledger rows were left untouched.',
            count($scope['collisions']),
        ));
        $this->line('  <fg=yellow>Rename one of the colliding files so each suffix is unique, then re-run:</>');
        foreach ($scope['collisions'] as $suffix => $files) {
            $this->line(sprintf('    <fg=yellow>·</> %s', $suffix));
            foreach ($files as $f) {
                $this->line('        '.$f);
            }
        }
    }
}
