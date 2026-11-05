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

declare(strict_types=1);

namespace Tests\Unit\Console;

use Tests\TestCase;

/**
 * `composer.local.json` is generated from what `plugins/` and `themes/` hold
 * when it is written, so an update has to rewrite it: otherwise it keeps
 * describing the version that was replaced. For an extension that declares
 * `autoload.files`, a stale entry is a hard fatal rather than a missing
 * feature -- Composer requires those paths on every request.
 *
 * Where the call sits matters as much as that it happens, so these tests pin
 * the order rather than only its presence.
 */
class ExtensionUpdateAutoloadSyncTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function updateCommands(): array
    {
        return [
            'plugin' => ['PluginUpdate', '$plugin->directory'],
            'theme' => ['ThemeUpdate', '$theme->directory'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('updateCommands')]
    public function test_the_update_rewrites_the_local_autoload(string $command, string $migrateArgument): void
    {
        $source = (string) file_get_contents(app_path("Console/Commands/{$command}.php"));

        $this->assertStringContainsString(
            'ComposerLocalHelper::syncAutoload()',
            $source,
            "{$command} must rewrite composer.local.json, or it keeps describing the version it replaced."
        );
    }

    /**
     * After the gate that can still refuse the new version, so a refused
     * update -- which restores the previous tree -- does not regenerate
     * against a version that was rejected. Before the migrations, because a
     * migration can only reference the new version's classes once the map
     * knows about them, which is what the comment at the migration step
     * already assumes.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('updateCommands')]
    public function test_it_runs_after_the_gate_and_before_the_migrations(string $command, string $migrateArgument): void
    {
        $source = (string) file_get_contents(app_path("Console/Commands/{$command}.php"));

        $gate = strpos($source, 'refuseBlockedUpdate(');
        $sync = strpos($source, 'ComposerLocalHelper::syncAutoload()');
        $migrate = strpos($source, "migrate({$migrateArgument})");

        $this->assertNotFalse($gate, "{$command}: the health gate moved.");
        $this->assertNotFalse($sync, "{$command}: the autoload sync is gone.");
        $this->assertNotFalse($migrate, "{$command}: the migration step moved.");

        $this->assertLessThan(
            $sync,
            $gate,
            "{$command}: the autoload must be rewritten after the gate, so a refused version is not the one described."
        );
        $this->assertLessThan(
            $migrate,
            $sync,
            "{$command}: the autoload must be rewritten before the migrations, which may reference the new classes."
        );
    }

    /**
     * A failed regeneration leaves the files in place and working for
     * everything that does not need the map, so it must not fail the update.
     * The recovery one-liner is printed instead, as the install path does.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('updateCommands')]
    public function test_a_failed_sync_warns_rather_than_failing_the_update(string $command, string $migrateArgument): void
    {
        $source = (string) file_get_contents(app_path("Console/Commands/{$command}.php"));

        $this->assertStringContainsString(
            'ComposerLocalHelper::RECOVERY_COMMAND',
            $source,
            "{$command}: a failed sync must print the recovery one-liner."
        );
        $this->assertStringNotContainsString(
            'syncAutoload(true, true)',
            $source,
            "{$command}: the update must not require the dump to succeed -- the files are already in place."
        );
    }
}
