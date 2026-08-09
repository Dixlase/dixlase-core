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
 * `dls:theme:migrate:rollback` used to delegate to the stock
 * `Artisan::call('migrate:rollback', ...)`, which operates on the core
 * `migrations` ledger — NOT `theme_migrations`. It therefore reversed nothing
 * a theme migration had recorded: the rollback silently no-opped and left the
 * added table/column plus the `dls_theme_migrations` row in place (drift
 * observed in Round 8). The fix routes the rollback through `ThemeMigrator`,
 * which reverses rows in the theme ledger under the canonical slug.
 *
 * Source-inspection style (matching ExtensionRollbackOrderingTest): a full
 * DB + on-disk-theme integration run is disproportionate for pinning "which
 * migrator does the command use", and the invariant is textual.
 */
class ThemeMigrateRollbackLedgerTest extends TestCase
{
    public function test_command_uses_theme_migrator_not_the_stock_rollback(): void
    {
        $source = (string) file_get_contents(app_path('Console/Commands/ThemeMigrateRollback.php'));

        $this->assertStringContainsString(
            'new ThemeMigrator(',
            $source,
            'dls:theme:migrate:rollback must reverse via ThemeMigrator so rows in '
            .'dls_theme_migrations are actually rolled back.'
        );
        $this->assertStringContainsString('->rollback(', $source);

        $this->assertStringNotContainsString(
            "Artisan::call('migrate:rollback'",
            $source,
            'Must NOT delegate to the stock migrate:rollback — that targets the '
            .'core `migrations` ledger and reverses nothing recorded by ThemeMigrator.'
        );
    }

    public function test_command_resolves_the_canonical_slug(): void
    {
        $source = (string) file_get_contents(app_path('Console/Commands/ThemeMigrateRollback.php'));

        // Must resolve the ledger slug from the model/manifest, not re-derive
        // it from the directory name.
        $this->assertStringContainsString('Theme::resolveSlug(', $source);
    }
}
