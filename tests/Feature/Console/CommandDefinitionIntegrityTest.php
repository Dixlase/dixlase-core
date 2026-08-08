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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Guards against Artisan commands that register fine but blow up the moment
 * they are invoked.
 *
 * `dls:plugin:download` declared `{--version=}`, which collides with the
 * global `--version` (`-V`) that Symfony's Application registers on every
 * command. The command appeared in `artisan list`, so nothing looked wrong,
 * but ANY invocation — even `--help` — aborted with
 * "An option named \"version\" already exists.". It also stopped PHPStan from
 * completing its analysis, hiding unrelated errors behind an internal error.
 *
 * Symfony raises that failure while merging the application's global options
 * into the command's own definition, which is exactly what this test forces
 * for every command at once.
 */
class CommandDefinitionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_dixlase_command_definition_merges_cleanly(): void
    {
        $failures = [];

        foreach (Artisan::all() as $name => $command) {
            if (! str_starts_with($name, 'dls:')) {
                continue;
            }

            try {
                $command->mergeApplicationDefinition();
            } catch (\Throwable $e) {
                $failures[] = $name.': '.$e->getMessage();
            }
        }

        $this->assertSame(
            [],
            $failures,
            'Some dls: commands cannot be invoked. A command whose option name collides with a global '
            .'Symfony option (--version/-V, --help/-h, --quiet/-q, --env, --ansi, --no-interaction) still '
            ."appears in `artisan list`, so this only surfaces at call time:\n  ".implode("\n  ", $failures)
        );
    }

    public function test_at_least_one_command_was_actually_checked(): void
    {
        // Without this, the test above would pass vacuously if Artisan::all()
        // ever stopped returning the dls: commands.
        $names = array_filter(
            array_keys(Artisan::all()),
            static fn (string $name): bool => str_starts_with($name, 'dls:'),
        );

        $this->assertGreaterThan(20, count($names), 'Expected the dls: command set to be discovered');
    }

    public function test_plugin_download_exposes_its_target_version_option(): void
    {
        $command = Artisan::all()['dls:plugin:download'] ?? null;

        $this->assertNotNull($command, 'dls:plugin:download should be registered');

        $definition = $command->getDefinition();

        $this->assertTrue(
            $definition->hasOption('to'),
            'The target version option is --to (renamed from --version, which collides with the global -V)'
        );
        $this->assertFalse(
            $definition->hasOption('version'),
            'Re-declaring --version makes every invocation of this command throw'
        );
    }
}
