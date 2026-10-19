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

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ExtensionManagementCommands;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A command that disables, removes or installs an extension must run
 * without the installed plugins loaded, so that a plugin which fails at
 * boot cannot take down the command that would fix it (#646).
 *
 * The list of such commands was compared against the pre-`dls:` names
 * (`plugin:disable`, `app:uninstall`, ...) from 00c80f66 until #646, so it
 * matched nothing for almost a year. The first test below is what makes
 * that drift fail instead of going quiet: every name must be a command
 * that is actually registered.
 */
class ExtensionManagementCommandsTest extends TestCase
{
    /**
     * @return list<array{0: string}>
     */
    public static function commandProvider(): array
    {
        return array_map(
            static fn (string $command): array => [$command],
            ExtensionManagementCommands::COMMANDS
        );
    }

    #[DataProvider('commandProvider')]
    public function test_every_listed_command_is_registered(string $command): void
    {
        $this->assertArrayHasKey(
            $command,
            Artisan::all(),
            "{$command} is in ExtensionManagementCommands::COMMANDS but no command of that name is registered. ".
            'Either the command was renamed — in which case the constant has to follow it — or the name is a typo.'
        );
    }

    #[DataProvider('commandProvider')]
    public function test_a_listed_command_skips_the_plugin_loader(string $command): void
    {
        $this->assertTrue(ExtensionManagementCommands::isRunning(['artisan', $command, 'AcmeBlog']));
    }

    public function test_another_command_does_not_skip_the_plugin_loader(): void
    {
        // These need the plugins: dls:plugin:list reads them, and a web
        // request has no argv command at all.
        $this->assertFalse(ExtensionManagementCommands::isRunning(['artisan', 'dls:plugin:list']));
        $this->assertFalse(ExtensionManagementCommands::isRunning(['artisan', 'migrate']));
        $this->assertFalse(ExtensionManagementCommands::isRunning(['artisan']));
        $this->assertFalse(ExtensionManagementCommands::isRunning([]));
    }

    public function test_a_global_option_before_the_command_does_not_hide_it(): void
    {
        // Symfony accepts options ahead of the command name, and the old
        // check only ever looked at $argv[1].
        $this->assertTrue(ExtensionManagementCommands::isRunning(
            ['artisan', '--env=testing', 'dls:plugin:disable', 'AcmeBlog']
        ));
    }

    public function test_the_name_is_not_matched_in_an_argument(): void
    {
        // The pre-#646 app:uninstall check scanned the whole of argv, so a
        // value that happened to equal a command name matched.
        $this->assertFalse(ExtensionManagementCommands::isRunning(
            ['artisan', 'dls:plugin:list', '--filter=dls:plugin:disable']
        ));
    }

    public function test_the_uninstall_commands_are_covered(): void
    {
        // The two that matter most: the plugin is removed while the
        // command runs, and the whole site is torn down.
        $this->assertContains('dls:plugin:uninstall', ExtensionManagementCommands::COMMANDS);
        $this->assertContains('dls:app:uninstall', ExtensionManagementCommands::COMMANDS);
    }
}
