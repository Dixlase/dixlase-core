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
 * Plugin/Theme counterpart of {@see CoreRollbackOrderingTest}.
 *
 * Round 8 sandbox verification found the same HIGH-severity ordering bug
 * that core PR #209 fixed for `CoreRollback`, still present in
 * `PluginRollback::handle()` and `ThemeRollback::handle()`. The pre-fix
 * sequence was
 *
 *     $this->restoreExtensionBackupInto(...);          ← removes new migration files
 *     $this->rollbackSchemaFromBackupMetadata(...);    ← finds nothing on disk to run
 *
 * `dls:{plugin,theme}:migrate:rollback` loads each migration from
 * `{plugins,themes}/<Dir>/database/migrations` to instantiate the class and
 * call `down()`. Because the backup restore had already swapped the tree back
 * to the pre-update version, the new migration files were GONE by the time
 * the schema rollback ran — so `down()` silent-skipped (rolled_back_count: 0),
 * the `dls_{plugin,theme}_migrations` row stayed, and the added table/column
 * survived while the command reported success. Confirmed live on
 * dixlase-cookie v0.1.2 → v0.1.1 rollback.
 *
 * The fix: run `rollbackSchemaFromBackupMetadata()` first (while the new
 * migration files are still on disk) and only THEN restore the old source.
 * This test pins the order for both commands so a future refactor cannot
 * re-swap them. Source-inspection style, matching CoreRollbackOrderingTest.
 */
class ExtensionRollbackOrderingTest extends TestCase
{
    public function test_plugin_rollback_schema_runs_before_source_restore(): void
    {
        $this->assertSchemaBeforeRestore('Console/Commands/PluginRollback.php', 'dls_plugin_migrations');
    }

    public function test_theme_rollback_schema_runs_before_source_restore(): void
    {
        $this->assertSchemaBeforeRestore('Console/Commands/ThemeRollback.php', 'dls_theme_migrations');
    }

    private function assertSchemaBeforeRestore(string $relativePath, string $ledgerTable): void
    {
        $source = (string) file_get_contents(app_path($relativePath));

        $schemaPos = strpos($source, '$this->rollbackSchemaFromBackupMetadata(');
        $restorePos = strpos($source, '$this->restoreExtensionBackupInto(');

        $this->assertNotFalse(
            $schemaPos,
            "{$relativePath} must call `\$this->rollbackSchemaFromBackupMetadata(...)` — "
            .'that is the schema-reversal step. Missing entirely means no schema '
            .'rollback will ever happen for the extension.'
        );
        $this->assertNotFalse(
            $restorePos,
            "{$relativePath} must call `\$this->restoreExtensionBackupInto(...)` — "
            .'that is the source-tree restore step.'
        );

        $this->assertLessThan(
            $restorePos,
            $schemaPos,
            'Round 8 fix invariant: `$this->rollbackSchemaFromBackupMetadata(...)` MUST run '
            .'BEFORE `$this->restoreExtensionBackupInto(...)`. `migrate:rollback` loads its '
            .'target migrations from disk to call `down()`, and the source restore removes '
            .'the new-version migration files that were about to be reversed. Restoring first '
            ."means `down()` finds nothing to run, silent-skips, and {$ledgerTable} plus the "
            .'added table/column stay while the command reports success. Do not swap these back.'
        );
    }
}
