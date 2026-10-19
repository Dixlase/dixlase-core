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
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Hints that name an Artisan command must name one that runs.
 *
 * Commands were renamed into the `dls:` namespace, but several translated
 * hints still told the user to run the old name (`plugin:delete`,
 * `theme:delete`, ...), which fails with "command not found". A hint can
 * also name a `dls:` command that core does not have at all: either one
 * that was removed (`dls:theme:disable`) or one that only exists when the
 * developer tools are installed. This test reads the language files only;
 * it writes nothing.
 */
class CommandHintPrefixTest extends TestCase
{
    use RefreshDatabase;

    /** A command name after `artisan ` or an opening backtick. */
    private const HINT_PATTERN = '/(?:artisan\s+|`)([a-z][a-z0-9-]*(?::[a-z0-9-]+)+)/';

    public function test_no_hint_names_a_command_without_its_dls_prefix(): void
    {
        $registered = Artisan::all();
        $this->assertArrayHasKey('dls:plugin:delete', $registered, 'Expected the dls: command set to be discovered');

        $failures = [];

        foreach (['en', 'ja'] as $locale) {
            foreach (File::allFiles(lang_path($locale)) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                preg_match_all(self::HINT_PATTERN, $file->getContents(), $matches);

                foreach (array_unique($matches[1]) as $name) {
                    if (str_starts_with($name, 'dls:') || isset($registered[$name])) {
                        continue;
                    }

                    if (isset($registered['dls:'.$name])) {
                        $failures[] = "{$locale}/{$file->getRelativePathname()}: `{$name}` should be `dls:{$name}`";
                    }
                }
            }
        }

        $this->assertSame([], $failures, "These hints name a command without its dls: prefix:\n  ".implode("\n  ", $failures));
    }

    public function test_no_hint_names_a_command_core_does_not_have(): void
    {
        // Only core's own commands count. A hint in core's language files
        // must work on a plain install, so it may not name a command that
        // an extension registers -- the audit commands say "with the
        // developer tools installed" in their own output instead.
        $core = [];
        foreach (Artisan::all() as $name => $command) {
            if (str_starts_with($command::class, 'App\\')) {
                $core[$name] = true;
            }
        }
        $this->assertArrayHasKey('dls:theme:switch', $core, 'Expected core commands to be discovered');

        $failures = [];

        foreach (['en', 'ja'] as $locale) {
            foreach (File::allFiles(lang_path($locale)) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                preg_match_all(self::HINT_PATTERN, $file->getContents(), $matches);

                foreach (array_unique($matches[1]) as $name) {
                    if (! str_starts_with($name, 'dls:') || isset($core[$name])) {
                        continue;
                    }

                    $failures[] = "{$locale}/{$file->getRelativePathname()}: `{$name}` is not a core command";
                }
            }
        }

        $this->assertSame([], $failures, "These hints name a command core does not register:\n  ".implode("\n  ", $failures));
    }

    public function test_plugin_uninstall_prints_no_debug_lines(): void
    {
        $this->artisan('dls:plugin:uninstall', ['pluginName' => 'NoSuchPluginForTest', '--no-interaction' => true])
            ->doesntExpectOutputToContain('DEBUG:')
            ->assertExitCode(1);

        // The command sets this process-wide flag in initialize(); do not leak it.
        putenv('PLUGIN_UNINSTALLING');
    }

    public function test_delete_hints_name_the_dls_commands(): void
    {
        foreach (['en', 'ja'] as $locale) {
            $this->assertStringContainsString('dls:plugin:delete', __('admin/command/plugin-uninstall.delete_hint', [], $locale));
            $this->assertStringContainsString('dls:theme:delete', __('admin/command/theme-uninstall.delete_hint', [], $locale));
        }
    }
}
