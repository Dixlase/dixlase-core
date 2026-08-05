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

namespace Tests\Unit\Console;

use Tests\TestCase;

/**
 * Round 6 sandbox verification found a HIGH-severity ordering bug in
 * `CoreRollback::handle()`: the pre-fix sequence was
 *
 *     Artisan::call('down', …)
 *     $snapshotter->restore($snapshotPath);       ← removes new migration files
 *     $this->rollbackSchema($meta);               ← now finds nothing on disk to run
 *
 * `Artisan::call('migrate:rollback', …)` loads each migration from
 * disk to instantiate the class and call `down()`. Because
 * `snapshotter->restore()` had already swapped the tree back to the
 * pre-update version, the new migration files were GONE by the time
 * `migrate:rollback` tried to open them — so `down()` silent-skipped,
 * the `dls_migrations` row stayed, and the schema stayed changed
 * while the command reported "Schema rollback complete". Silent
 * data-model corruption with a false success.
 *
 * The fix: run `rollbackSchema()` first (while the new migration
 * files are still on disk) and only THEN restore the old source.
 * This test pins the order so a future refactor cannot re-swap them.
 *
 * Source-inspection style is appropriate here: reproducing the
 * mid-rollback file-swap behaviour in PHPUnit would require full DB
 * + filesystem orchestration, and the invariant we actually care
 * about is textual (which call comes first). If someone edits the
 * flow they will see this pin update as part of the same diff.
 */
class CoreRollbackOrderingTest extends TestCase
{
    public function test_rollback_schema_runs_before_source_restore(): void
    {
        $source = (string) file_get_contents(app_path('Console/Commands/CoreRollback.php'));

        $schemaPos = strpos($source, '$this->rollbackSchema($meta)');
        $restorePos = strpos($source, '$snapshotter->restore($snapshotPath)');

        $this->assertNotFalse(
            $schemaPos,
            '`CoreRollback::handle()` must call `$this->rollbackSchema($meta)` — '
            .'that is the entire schema-reversal step. Missing entirely means '
            .'no rollback will ever happen for migrations.'
        );
        $this->assertNotFalse(
            $restorePos,
            '`CoreRollback::handle()` must call `$snapshotter->restore($snapshotPath)` — '
            .'that is the source-tree restore step.'
        );

        $this->assertLessThan(
            $restorePos,
            $schemaPos,
            'Round 6 fix invariant: `$this->rollbackSchema($meta)` MUST run '
            .'BEFORE `$snapshotter->restore($snapshotPath)`. `migrate:rollback` '
            .'loads its target migrations from disk to call `down()`, and the '
            .'source restore removes the new-version migration files that were '
            .'about to be reversed. Restoring first means `down()` finds nothing '
            .'to run, silent-skips, and the schema stays applied while the '
            .'command reports success. Do not swap these back.'
        );
    }
}
