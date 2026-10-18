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

namespace App\Support;

/**
 * The artisan commands that manage an installed extension, and whose run
 * must not boot the installed plugins.
 *
 * A plugin that fails at boot would otherwise take down the very command
 * that disables or removes it: `AppServiceProvider` boots every enabled
 * plugin, so the broken one is loaded while `dls:plugin:disable` is still
 * starting up. These commands therefore skip the plugin loader.
 *
 * The check used to compare against `plugin:install`, `plugin:uninstall`,
 * `plugin:enable`, `plugin:disable` and `app:uninstall`. Those names were
 * replaced by their `dls:` form in 00c80f66 (2025-11-21), so from then on
 * nothing matched and every one of these commands booted the plugins it
 * was about to change.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
final class ExtensionManagementCommands
{
    /**
     * Commands that run without the installed plugins loaded.
     *
     * Every name here must be a command core itself registers;
     * ExtensionManagementCommandsTest fails when one drifts.
     *
     * @var list<string>
     */
    public const COMMANDS = [
        'dls:plugin:install',
        'dls:plugin:uninstall',
        'dls:plugin:enable',
        'dls:plugin:disable',
        'dls:app:uninstall',
    ];

    /**
     * Whether the current process is running one of these commands.
     *
     * @param  list<string>|null  $argv  Defaults to the process arguments
     */
    public static function isRunning(?array $argv = null): bool
    {
        return in_array(self::commandName($argv), self::COMMANDS, true);
    }

    /**
     * The command name of an artisan invocation, or an empty string.
     *
     * Symfony accepts global options before the command name
     * (`artisan --env=testing dls:plugin:disable Foo`), so the name is the
     * first argument that is not an option, not simply `$argv[1]`.
     *
     * @param  list<string>|null  $argv  Defaults to the process arguments
     */
    public static function commandName(?array $argv = null): string
    {
        /** @var list<string> $arguments */
        $arguments = $argv ?? ($_SERVER['argv'] ?? []);

        foreach (array_slice($arguments, 1) as $argument) {
            if (! str_starts_with($argument, '-')) {
                return $argument;
            }
        }

        return '';
    }
}
