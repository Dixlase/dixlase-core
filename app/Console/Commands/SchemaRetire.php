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

use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop schema objects that earlier beta releases created and no longer use.
 *
 * During the beta series core migrations are edited in place (see CLAUDE.md
 * "Migration Editing Policy"). Editing a migration changes what a fresh
 * install creates, but an existing site keeps whatever the old version of the
 * file created — nothing ever removes it. This command is the non-destructive
 * clean-up for exactly those leftovers, and only those: every object it may
 * touch is listed below by name. It never discovers targets on its own.
 *
 * What is retired, and why:
 *
 *   - **`webauthn_credentials`** — the laragear/webauthn table. The passkeys
 *     migration (#350) moved admin members to `members_passkeys` and left the
 *     old table in place, unused. Its rows are not converted: members
 *     re-register their passkeys, so dropping the table loses nothing that is
 *     still read.
 *   - **`core_migration_smoke`** and **`sites.migration_smoke_flag`** — the
 *     table and column created by the core-update verification fixture
 *     `0001_01_01_000900_add_core_migration_smoke_table_and_column`.
 *   - **Marker rows in `global_settings`** — written by the fixtures
 *     `000901`–`000906` and by the fixture `UpdateSeeder`.
 *
 * The fixtures were moved off `main` onto `test/core-update-sandbox-fixtures`
 * (#370), and on production sites they were never applied, so on those sites
 * only `webauthn_credentials` is expected to be present. The fixture objects
 * turn up on dev and sandbox databases that ran a verification round. When a
 * new round adds another marker, append its name to {@see self::MARKER_SETTINGS}.
 *
 * Out of scope: the theme's own fixture (`theme_update_migration_marker` in the
 * theme's settings table) belongs to the theme repository.
 *
 * Default is a dry-run preview; pass --confirm to apply. Running it again after
 * a successful --confirm reports "Nothing to retire" and changes nothing.
 */
class SchemaRetire extends Command
{
    /**
     * Retired tables, unprefixed (the connection prefix is applied by the
     * schema builder). Dropped in this order.
     *
     * @var array<string, string>
     */
    protected const TABLES = [
        'core_migration_smoke' => 'core-update verification fixture 000900',
        'webauthn_credentials' => 'laragear/webauthn, replaced by members_passkeys (#350)',
    ];

    /**
     * Retired columns as [table, column, reason]. Dropped before the tables,
     * mirroring the fixture's own down().
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    protected const COLUMNS = [
        ['sites', 'migration_smoke_flag', 'core-update verification fixture 000900'],
    ];

    /**
     * `global_settings.name` values written by the verification fixtures.
     *
     * @var list<string>
     */
    protected const MARKER_SETTINGS = [
        'core_update_migration_marker',
        'core_update_round2_migration_marker',
        'core_update_round3_migration_marker',
        'core_update_round4_migration_marker',
        'core_update_round5_migration_marker',
        'core_update_round6_migration_marker',
        'core_update_seeder_marker',
    ];

    protected const SETTINGS_TABLE = 'global_settings';

    /**
     * The table whose rows are real user data. Its row count is surfaced as a
     * warning, because dropping it deletes the members' old passkeys.
     */
    protected const USER_DATA_TABLE = 'webauthn_credentials';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:schema:retire
                            {--confirm : Drop and delete the listed objects (default is a dry-run preview)}
                            {--json : Output the result as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop schema objects retired by earlier beta releases (the laragear/webauthn table and core-update verification fixtures); dry-run by default';

    public function handle(): int
    {
        $targets = $this->inspect();
        $present = array_values(array_filter($targets, fn (array $t) => $t['present']));

        if ($present === []) {
            return $this->report($targets, 'clean');
        }

        if (! $this->option('confirm')) {
            return $this->report($targets, 'dry_run');
        }

        $this->apply($present);
        $this->audit($present);

        return $this->report($targets, 'applied');
    }

    /**
     * Describe every retire target and whether it exists on this database.
     *
     * @return list<array{kind: string, name: string, physical: string, present: bool, rows: int|null, reason: string}>
     */
    protected function inspect(): array
    {
        $prefix = DB::getTablePrefix();
        $targets = [];

        foreach (self::COLUMNS as [$table, $column, $reason]) {
            $present = Schema::hasTable($table) && Schema::hasColumn($table, $column);
            $targets[] = [
                'kind' => 'column',
                'name' => "{$table}.{$column}",
                'physical' => "{$prefix}{$table}.{$column}",
                'present' => $present,
                'rows' => null,
                'reason' => $reason,
            ];
        }

        foreach (self::TABLES as $table => $reason) {
            $present = Schema::hasTable($table);
            $targets[] = [
                'kind' => 'table',
                'name' => $table,
                'physical' => $prefix.$table,
                'present' => $present,
                'rows' => $present ? DB::table($table)->count() : null,
                'reason' => $reason,
            ];
        }

        $markerRows = Schema::hasTable(self::SETTINGS_TABLE)
            ? DB::table(self::SETTINGS_TABLE)->whereIn('name', self::MARKER_SETTINGS)->count()
            : 0;
        $targets[] = [
            'kind' => 'rows',
            'name' => self::SETTINGS_TABLE.' (verification markers)',
            'physical' => $prefix.self::SETTINGS_TABLE,
            'present' => $markerRows > 0,
            'rows' => $markerRows,
            'reason' => 'core-update verification fixtures 000901-000906 and UpdateSeeder',
        ];

        return $targets;
    }

    /**
     * Drop / delete the present targets. DDL runs outside a transaction
     * because MySQL commits implicitly around it; the row delete is the only
     * part a transaction can protect.
     *
     * @param  list<array{kind: string, name: string, physical: string, present: bool, rows: int|null, reason: string}>  $present
     */
    protected function apply(array $present): void
    {
        $kinds = array_column($present, 'kind');
        $names = array_column($present, 'name');

        if (in_array('column', $kinds, true)) {
            foreach (self::COLUMNS as [$table, $column]) {
                if (in_array("{$table}.{$column}", $names, true)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                        $blueprint->dropColumn($column);
                    });
                }
            }
        }

        foreach (array_keys(self::TABLES) as $table) {
            if (in_array($table, $names, true)) {
                Schema::dropIfExists($table);
            }
        }

        if (in_array('rows', $kinds, true)) {
            DB::transaction(function (): void {
                DB::table(self::SETTINGS_TABLE)->whereIn('name', self::MARKER_SETTINGS)->delete();
            });
        }
    }

    /**
     * Record what was removed. The audit trail is the only lasting evidence
     * that a table disappeared on purpose rather than by accident.
     *
     * @param  list<array{kind: string, name: string, physical: string, present: bool, rows: int|null, reason: string}>  $present
     */
    protected function audit(array $present): void
    {
        app(AuditService::class)->log([
            'action' => AuditLog::ACTION_SCHEMA_RETIRED,
            'category' => AuditLog::CATEGORY_SYSTEM,
            'severity' => AuditLog::SEVERITY_NOTICE,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'target_label' => implode(', ', array_column($present, 'physical')),
            'context' => [
                'retired' => array_map(fn (array $t) => [
                    'kind' => $t['kind'],
                    'name' => $t['physical'],
                    'rows' => $t['rows'],
                ], $present),
            ],
        ]);
    }

    /**
     * @param  list<array{kind: string, name: string, physical: string, present: bool, rows: int|null, reason: string}>  $targets
     */
    protected function report(array $targets, string $status): int
    {
        $warnings = $this->warnings($targets, $status);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'action' => 'retire',
                'status' => $status,
                'database' => $this->databaseName(),
                'targets' => array_map(fn (array $t) => [
                    'kind' => $t['kind'],
                    'name' => $t['name'],
                    'physical' => $t['physical'],
                    'present' => $t['present'],
                    'rows' => $t['rows'],
                ], $targets),
                'warnings' => $warnings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $headline = match ($status) {
            'dry_run' => '<fg=yellow>[DRY-RUN]</> Retired schema objects on this database',
            'applied' => '<fg=green>[APPLIED]</> Retired schema objects removed',
            default => 'Retired schema objects on this database',
        };
        $this->line($headline);
        $this->line("  database: {$this->databaseName()}");
        $this->newLine();

        foreach ($targets as $t) {
            $state = match (true) {
                ! $t['present'] => '<fg=gray>absent</>',
                $status === 'applied' => '<fg=green>removed</>',
                default => '<fg=yellow>present</>',
            };
            $rows = $t['present'] && $t['rows'] !== null ? " ({$t['rows']} rows)" : '';
            $this->line(sprintf('  %-6s %-48s %s%s', $t['kind'], $t['physical'], $state, $rows));
            $this->line("         <fg=gray>{$t['reason']}</>");
        }

        foreach ($warnings as $warning) {
            $this->newLine();
            $this->line("<fg=red>!</> {$warning}");
        }

        $this->newLine();

        match ($status) {
            'clean' => $this->info('✓ Nothing to retire.'),
            'dry_run' => $this->warn('Re-run with --confirm to apply.'),
            default => $this->info('✓ Done. Running this command again is a no-op.'),
        };

        return self::SUCCESS;
    }

    /**
     * @param  list<array{kind: string, name: string, physical: string, present: bool, rows: int|null, reason: string}>  $targets
     * @return list<string>
     */
    protected function warnings(array $targets, string $status): array
    {
        foreach ($targets as $t) {
            if ($t['kind'] === 'table' && $t['name'] === self::USER_DATA_TABLE && $t['present'] && $t['rows'] > 0) {
                return [$status === 'applied'
                    ? "{$t['rows']} legacy passkey row(s) were deleted. Affected members must register their passkeys again."
                    : "{$t['rows']} legacy passkey row(s) will be deleted. They are no longer read (members_passkeys replaced them), but affected members must register their passkeys again."];
            }
        }

        return [];
    }

    protected function databaseName(): string
    {
        $connection = (string) config('database.default');

        return $connection.' / '.(string) config("database.connections.{$connection}.database");
    }
}
