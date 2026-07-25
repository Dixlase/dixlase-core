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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Move plugin/theme migrations that were stranded in the core ledger.
 *
 * Installs from before the stock migrator stopped running extension
 * migrations recorded each plugin/theme migration in the core
 * `migrations` table instead of `plugin_migrations` / `theme_migrations`.
 * Those ledgers are then empty, so the first dls:*:update makes the
 * extension's migrator treat every migration as pending and re-run
 * `CREATE TABLE` over an existing table — SQLSTATE[42S01], and the whole
 * update rolls back (the symptom: an "update" that leaves the version
 * unchanged).
 *
 * For each installed extension, this moves the rows whose migration
 * filename is BOTH present in the core ledger AND on disk under the
 * extension's own migrations directory: insert into the extension
 * ledger (keyed by slug), then delete the stranded core-ledger row.
 * The filename match keeps it from touching genuine core migrations.
 * It is a no-op on a correctly-installed site (the extension ledgers
 * already hold these rows and the core ledger does not).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfill('themes', 'theme_migrations', 'theme');
        $this->backfill('plugins', 'plugin_migrations', 'plugin');
    }

    /**
     * @param  string  $table          installed-extension table (themes|plugins)
     * @param  string  $ledger         per-extension ledger (theme_migrations|plugin_migrations)
     * @param  string  $slugColumn     the ledger's slug column (theme|plugin)
     */
    private function backfill(string $table, string $ledger, string $slugColumn): void
    {
        $extensions = DB::table($table)
            ->whereNotNull('installed_at')
            ->get(['slug', 'directory']);

        foreach ($extensions as $ext) {
            if (empty($ext->directory) || empty($ext->slug)) {
                continue;
            }

            $dir = base_path("{$table}/{$ext->directory}/database/migrations");
            if (! is_dir($dir)) {
                continue;
            }

            foreach (glob($dir.'/*.php') ?: [] as $file) {
                $name = basename($file, '.php');

                // Only touch a name that is actually stranded in the core
                // ledger (and therefore missing from the per-extension one).
                $core = DB::table('migrations')->where('migration', $name)->first();
                if ($core === null) {
                    continue;
                }

                $alreadyTracked = DB::table($ledger)
                    ->where('migration', $name)
                    ->where($slugColumn, $ext->slug)
                    ->exists();

                if (! $alreadyTracked) {
                    DB::table($ledger)->insert([
                        'migration' => $name,
                        $slugColumn => $ext->slug,
                        'batch' => $core->batch ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('migrations')->where('migration', $name)->delete();
            }
        }
    }

    public function down(): void
    {
        // Irreversible bookkeeping repair: the pre-fix state (rows in the
        // wrong ledger) is not worth reconstructing on rollback.
    }
};
