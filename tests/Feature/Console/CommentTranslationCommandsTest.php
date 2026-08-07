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

use App\Console\Commands\CommentBuildCommand;
use App\Console\Commands\CommentStatusCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * Core-side coverage for `dls:comment:build` and `dls:comment:status`.
 *
 * Both commands live in Core but their only tests used to live in
 * DixlaseCoreDevKit's suite, which runs in a separate CI job (`plugin-tests`,
 * gated on `needs: core-tests`) and needs the plugin to be checked out at all.
 * A Core-only change could therefore break them without Core Tests noticing —
 * and did: the commands imported their services from
 * `Plugins\DixlaseCoreDevKit\...`, so every release build, which checks out
 * without plugins, aborted with "Target class ... does not exist".
 *
 * These tests stay read-only on purpose. `--dry-run` computes substitutions
 * without writing, and `dls:comment:status` only reads. Neither may write to
 * the working tree.
 */
class CommentTranslationCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_status_runs(): void
    {
        $this->artisan('dls:comment:status')->assertSuccessful();
    }

    public function test_comment_status_rejects_an_unknown_filter(): void
    {
        $this->artisan('dls:comment:status', ['--filter' => 'not-a-status'])
            ->expectsOutputToContain('Unknown filter')
            ->assertSuccessful();
    }

    public function test_comment_build_dry_run_runs(): void
    {
        $this->artisan('dls:comment:build', [
            '--locale' => 'ja',
            '--dry-run' => true,
        ])->assertSuccessful();
    }

    public function test_comment_build_fails_for_a_locale_with_no_dictionary(): void
    {
        $this->artisan('dls:comment:build', [
            '--locale' => 'zz-no-such-locale',
            '--dry-run' => true,
        ])->assertFailed();
    }

    /**
     * The regression guard for the release-build outage: both commands must
     * resolve every injected dependency from Core. If a service is moved back
     * into a plugin namespace, this fails immediately instead of surfacing
     * months later as a broken `v*` tag build.
     */
    public function test_commands_depend_only_on_core_classes(): void
    {
        foreach ([CommentBuildCommand::class, CommentStatusCommand::class] as $command) {
            $parameters = (new ReflectionClass($command))->getMethod('handle')->getParameters();

            $this->assertNotEmpty($parameters, "{$command}::handle() should inject its services");

            foreach ($parameters as $parameter) {
                $type = (string) $parameter->getType();

                $this->assertStringStartsWith(
                    'App\\',
                    $type,
                    "{$command}::handle() must not depend on {$type}: Core commands cannot rely on "
                    .'classes that are absent from a plugin-less checkout.'
                );
            }
        }
    }
}
