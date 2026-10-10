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

namespace Tests\Feature\Console;

use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Removing an extension source nulls the extensions' source_id (the foreign
 * key is nullOnDelete), and the update commands then report "no linked
 * source". That message used to name dls:theme:download, which does not
 * exist; it must name a command that links the extension again.
 *
 * A source_id can still name a missing row when extension_sources was
 * rebuilt outside the foreign key (a restore, a table recreated with
 * foreign-key checks off). The update commands used to pass the resulting
 * null to ExtensionSourceManager::makeProvider() and stop with a TypeError;
 * they must refuse with the same kind of actionable error.
 */
class ExtensionUpdateMissingSourceTest extends TestCase
{
    use RefreshDatabase;

    private const MISSING_SOURCE_ID = 999999;

    public function test_theme_update_refuses_when_the_linked_source_row_is_missing(): void
    {
        $this->withoutForeignKeys(fn () => $this->createTheme(['source_id' => self::MISSING_SOURCE_ID]));

        $this->artisan('dls:theme:update', ['slug' => 'test-theme', '--force' => true])
            // One expectation per line: each one consumes the line it matches.
            ->expectsOutputToContain('which no longer exists. Link it to a registered source with `dls:theme:install TestTheme --force --source=<id>`')
            ->assertExitCode(1);
    }

    public function test_plugin_update_refuses_when_the_linked_source_row_is_missing(): void
    {
        $this->withoutForeignKeys(fn () => Plugin::create([
            'name' => 'TestPlugin',
            'package_name' => 'dixlase/test-plugin',
            'directory' => 'TestPlugin',
            'namespace' => 'Plugins\\TestPlugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'installed_at' => now(),
            'source_id' => self::MISSING_SOURCE_ID,
        ]));

        $this->artisan('dls:plugin:update', ['slug' => 'test-plugin', '--force' => true])
            ->expectsOutputToContain('which no longer exists. Link it to a registered source with `dls:plugin:install TestPlugin --source=<id>`')
            ->assertExitCode(1);
    }

    public function test_theme_without_a_source_is_pointed_at_a_command_that_exists(): void
    {
        $this->createTheme(['source_id' => null]);

        $this->artisan('dls:theme:update', ['slug' => 'test-theme', '--force' => true])
            ->expectsOutputToContain('dls:theme:install TestTheme --force --source=<id>')
            ->doesntExpectOutputToContain('dls:theme:download')
            ->assertExitCode(1);
    }

    /**
     * Write a row whose source_id names no extension_sources row, the state
     * an out-of-band rebuild of that table leaves behind. RefreshDatabase
     * keeps each test inside a transaction, where SQLite ignores
     * `PRAGMA foreign_keys`; deferring the check to a commit that never
     * comes lets the dangling row in.
     */
    private function withoutForeignKeys(callable $write): void
    {
        DB::statement('PRAGMA defer_foreign_keys = ON');
        $write();
    }

    private function createTheme(array $overrides): Theme
    {
        return Theme::create(array_merge([
            'name' => 'Test Theme',
            'package_name' => 'dixlase/test-theme',
            'directory' => 'TestTheme',
            'slug' => 'test-theme',
            'namespace' => 'Themes\\TestTheme',
            'version' => '1.0.0',
            'installed_at' => now(),
        ], $overrides));
    }
}
