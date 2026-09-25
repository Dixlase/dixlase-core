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

namespace Tests\Unit\Support\Process;

use App\Support\Process\SubprocessEnvironment;
use Tests\TestCase;

/**
 * Symfony's Process derives a child environment from the intersection of
 * getenv() and $_SERVER. Laravel fills $_SERVER with the .env entries, so on
 * the built-in server and on PHP-FPM with clear_env=yes that intersection can
 * lose PATH — and composer's `#!/usr/bin/env php` shebang then fails with
 * exit 127, silently leaving the autoload map stale.
 */
class SubprocessEnvironmentTest extends TestCase
{
    private ?string $originalPath = null;

    private bool $hadServerPath = false;

    private bool $hadEnvPath = false;

    protected function setUp(): void
    {
        parent::setUp();
        $path = getenv('PATH');
        $this->originalPath = $path === false ? null : $path;
        $this->hadServerPath = array_key_exists('PATH', $_SERVER);
        $this->hadEnvPath = array_key_exists('PATH', $_ENV);
    }

    protected function tearDown(): void
    {
        if ($this->originalPath === null) {
            putenv('PATH');
        } else {
            putenv('PATH='.$this->originalPath);
            if ($this->hadServerPath) {
                $_SERVER['PATH'] = $this->originalPath;
            }
            if ($this->hadEnvPath) {
                $_ENV['PATH'] = $this->originalPath;
            }
        }

        parent::tearDown();
    }

    public function test_inherit_always_carries_a_path(): void
    {
        $env = SubprocessEnvironment::inherit();

        $this->assertArrayHasKey('PATH', $env);
        $this->assertNotSame('', $env['PATH']);
    }

    public function test_inherit_keeps_the_caller_s_own_variables(): void
    {
        $env = SubprocessEnvironment::inherit(['NPM_CONFIG_CACHE' => '/tmp/npm']);

        $this->assertSame('/tmp/npm', $env['NPM_CONFIG_CACHE']);
        $this->assertArrayHasKey('PATH', $env);
    }

    public function test_a_caller_may_override_the_path(): void
    {
        $env = SubprocessEnvironment::inherit(['PATH' => '/opt/bin']);

        $this->assertSame('/opt/bin', $env['PATH']);
    }

    public function test_the_process_path_is_used_when_present(): void
    {
        putenv('PATH=/opt/tools:/usr/bin');

        $this->assertSame('/opt/tools:/usr/bin', SubprocessEnvironment::path());
    }

    public function test_server_superglobal_is_used_when_the_process_has_no_path(): void
    {
        putenv('PATH');
        $_SERVER['PATH'] = '/from/server';

        $this->assertSame('/from/server', SubprocessEnvironment::path());
    }

    public function test_a_usable_default_is_returned_when_nothing_carries_a_path(): void
    {
        putenv('PATH');
        unset($_SERVER['PATH'], $_ENV['PATH']);

        $this->assertSame(SubprocessEnvironment::FALLBACK_PATH, SubprocessEnvironment::path());
        $this->assertStringContainsString('/usr/bin', SubprocessEnvironment::path());
    }

    public function test_path_directories_are_split_and_trimmed(): void
    {
        putenv('PATH=/opt/tools/'.PATH_SEPARATOR.PATH_SEPARATOR.' /usr/bin ');

        $this->assertSame(['/opt/tools', '/usr/bin'], SubprocessEnvironment::pathDirectories());
    }
}
