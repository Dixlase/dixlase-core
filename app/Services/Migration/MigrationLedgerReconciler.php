<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace App\Services\Migration;

use Illuminate\Database\ConnectionInterface;

/**
 * Realign a plugin/theme migration ledger to a set of migration files
 * by their stable suffix, so a migration the new release re-sorted
 * (same `create_*_table` suffix, different timestamp prefix) is
 * recognised as already applied instead of being re-run.
 *
 * This is the same suffix-matching `dls:migration:resync` performs,
 * applied automatically against the updated source at update time:
 * without it, a renumbered migration looks pending and its CREATE
 * collides with the existing table (SQLSTATE[42S01]), rolling back the
 * whole extension update. Scoped to one extension via its slug column.
 */
class MigrationLedgerReconciler
{
    /**
     * Strip the `YYYY_MM_DD_NNNNNN_` timestamp prefix, leaving the
     * stable identifier (e.g. `create_inquiries_table`). The table the
     * migration touches is invariant under a re-sort, so it is a safe
     * match key. Returns null for names without that prefix.
     */
    public static function suffixOf(string $migrationName): ?string
    {
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+)$/', $migrationName, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /**
     * Rename ledger rows for $slug whose stable suffix matches a file in
     * $migrationDir under a different name. Returns the number of rows
     * updated. No-op when the names already match (the normal case), so
     * it is safe to call before every migrate.
     */
    public static function reconcile(
        ConnectionInterface $db,
        string $ledgerTable,
        string $slugColumn,
        ?string $slug,
        string $migrationDir,
    ): int {
        if ($slug === null || ! is_dir($migrationDir)) {
            return 0;
        }

        $fileMap = self::buildSuffixMap($migrationDir);
        if ($fileMap === []) {
            return 0;
        }

        $updated = 0;

        $rows = $db->table($ledgerTable)
            ->where($slugColumn, $slug)
            ->get(['id', 'migration']);

        foreach ($rows as $row) {
            $suffix = self::suffixOf($row->migration);
            if ($suffix === null || ! isset($fileMap[$suffix])) {
                continue;
            }

            $newName = $fileMap[$suffix];
            if ($newName === $row->migration) {
                continue;
            }

            // Never create a duplicate: if the target name is already
            // recorded for this slug, drop the stale row instead.
            $targetExists = $db->table($ledgerTable)
                ->where($slugColumn, $slug)
                ->where('migration', $newName)
                ->exists();

            if ($targetExists) {
                $db->table($ledgerTable)->where('id', $row->id)->delete();
            } else {
                $db->table($ledgerTable)->where('id', $row->id)->update(['migration' => $newName]);
            }

            $updated++;
        }

        return $updated;
    }

    /**
     * Map stable suffix => current migration name for every file in
     * $dir. A suffix appearing on more than one file is ambiguous and
     * dropped (we cannot tell which row it belongs to).
     *
     * @return array<string, string>
     */
    private static function buildSuffixMap(string $dir): array
    {
        $map = [];
        $collisions = [];

        foreach (glob($dir.'/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            if (str_starts_with($name, '_')) {
                continue;
            }

            $suffix = self::suffixOf($name);
            if ($suffix === null) {
                continue;
            }

            if (isset($collisions[$suffix])) {
                continue;
            }

            if (isset($map[$suffix])) {
                unset($map[$suffix]);
                $collisions[$suffix] = true;

                continue;
            }

            $map[$suffix] = $name;
        }

        return $map;
    }
}
