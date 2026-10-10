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
 * Every dls: command must describe itself in `artisan list` and `--help`.
 *
 * Several commands showed a translation key that exists in no language file
 * (`command.theme_install.description`), the `make:command` placeholder
 * (`Command description`), or nothing at all: the last group assigned
 * `$this->description` after `parent::__construct()`, when Symfony had
 * already read it.
 */
class CommandDescriptionTest extends TestCase
{
    use RefreshDatabase;

    private const PLACEHOLDER = 'Command description';

    /** A dotted lower-case identifier such as `command.theme_install.description`. */
    private const KEY_PATTERN = '/^[a-z0-9_-]+(\.[a-z0-9_-]+)+$/';

    public function test_every_dixlase_command_has_a_real_description(): void
    {
        $failures = [];

        foreach ($this->dixlaseCommands() as $name => $command) {
            $description = trim($command->getDescription());

            if ($description === '' || $description === self::PLACEHOLDER || preg_match(self::KEY_PATTERN, $description)) {
                $failures[] = "{$name}: \"{$description}\"";
            }
        }

        $this->assertSame([], $failures, "These dls: commands have no usable description:\n  ".implode("\n  ", $failures));
    }

    public function test_no_argument_or_option_help_is_a_translation_key(): void
    {
        $failures = [];

        foreach ($this->dixlaseCommands() as $name => $command) {
            $definition = $command->getDefinition();

            foreach ($definition->getArguments() as $argument) {
                if (preg_match(self::KEY_PATTERN, trim($argument->getDescription()))) {
                    $failures[] = "{$name} <{$argument->getName()}>: {$argument->getDescription()}";
                }
            }

            foreach ($definition->getOptions() as $option) {
                if (preg_match(self::KEY_PATTERN, trim($option->getDescription()))) {
                    $failures[] = "{$name} --{$option->getName()}: {$option->getDescription()}";
                }
            }
        }

        $this->assertSame([], $failures, "These dls: arguments or options show a translation key:\n  ".implode("\n  ", $failures));
    }

    /**
     * @return array<string, \Symfony\Component\Console\Command\Command>
     */
    private function dixlaseCommands(): array
    {
        $commands = array_filter(
            Artisan::all(),
            static fn ($command, string $name): bool => str_starts_with($name, 'dls:'),
            ARRAY_FILTER_USE_BOTH,
        );

        $this->assertGreaterThan(20, count($commands), 'Expected the dls: command set to be discovered');

        return $commands;
    }
}
