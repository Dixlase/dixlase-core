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

namespace Tests\Unit\Helpers;

use App\Helpers\ComposerLocalHelper;
use ReflectionMethod;
use Tests\TestCase;

/**
 * How core starts composer when it regenerates the autoload map.
 *
 * Relying on composer's `#!/usr/bin/env php` shebang means the child needs
 * php on its PATH; a web SAPI that hands the child no PATH turns that into
 * exit 127 and a stale autoload map, which surfaces later as "class not
 * found" for a plugin installed from the admin panel.
 */
class ComposerLocalHelperProcessTest extends TestCase
{
    private string $workDir = '';

    private ?string $originalPath = null;

    private ?string $originalComposerBinary = null;

    protected function setUp(): void
    {
        parent::setUp();
        $path = getenv('PATH');
        $this->originalPath = $path === false ? null : $path;

        // Composer sets COMPOSER_BINARY when it runs a script, and the
        // resolver prefers it — clear it so the test sees its own fixture.
        $composerBinary = getenv('COMPOSER_BINARY');
        $this->originalComposerBinary = $composerBinary === false ? null : $composerBinary;
        putenv('COMPOSER_BINARY');
        unset($_SERVER['COMPOSER_BINARY']);
        $this->workDir = storage_path('framework/testing/composer-'.uniqid());
        mkdir($this->workDir, 0775, true);
    }

    protected function tearDown(): void
    {
        if ($this->originalPath === null) {
            putenv('PATH');
        } else {
            putenv('PATH='.$this->originalPath);
        }

        if ($this->originalComposerBinary === null) {
            putenv('COMPOSER_BINARY');
        } else {
            putenv('COMPOSER_BINARY='.$this->originalComposerBinary);
        }

        if ($this->workDir !== '' && is_dir($this->workDir)) {
            foreach (glob($this->workDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->workDir);
        }

        parent::tearDown();
    }

    public function test_a_php_composer_is_started_with_this_interpreter(): void
    {
        if (is_file(base_path('composer.phar'))) {
            $this->markTestSkipped('This checkout has its own composer.phar, which the resolver prefers.');
        }

        $this->writeComposer("#!/usr/bin/env php\n<?php // composer\n");

        $command = $this->composerCommand(['dump-autoload', '--optimize']);

        $this->assertSame(PHP_BINARY, $command[0]);
        $this->assertSame($this->workDir.'/composer', $command[1]);
        $this->assertSame(['dump-autoload', '--optimize'], array_slice($command, 2));
    }

    public function test_a_phar_style_composer_is_recognised(): void
    {
        if (is_file(base_path('composer.phar'))) {
            $this->markTestSkipped('This checkout has its own composer.phar, which the resolver prefers.');
        }

        $this->writeComposer("<?php // composer.phar stub\n", 'composer.phar');

        $command = $this->composerCommand(['dump-autoload']);

        $this->assertSame(PHP_BINARY, $command[0]);
        $this->assertSame($this->workDir.'/composer.phar', $command[1]);
    }

    public function test_a_shell_wrapper_is_left_to_the_shebang(): void
    {
        if (is_file(base_path('composer.phar'))) {
            $this->markTestSkipped('This checkout has its own composer.phar, which the resolver prefers.');
        }

        // A docker/asdf shim is not something PHP_BINARY can run.
        $this->writeComposer("#!/bin/sh\nexec docker run composer \"\$@\"\n");

        $this->assertSame(['composer', 'dump-autoload'], $this->composerCommand(['dump-autoload']));
    }

    public function test_the_composer_environment_carries_a_path(): void
    {
        $method = new ReflectionMethod(ComposerLocalHelper::class, 'composerEnvironment');
        $method->setAccessible(true);

        $env = $method->invoke(null);

        $this->assertArrayHasKey('PATH', $env);
        $this->assertNotSame('', $env['PATH']);
    }

    private function writeComposer(string $contents, string $name = 'composer'): void
    {
        file_put_contents($this->workDir.'/'.$name, $contents);
        chmod($this->workDir.'/'.$name, 0755);
        putenv('PATH='.$this->workDir);
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function composerCommand(array $arguments): array
    {
        $method = new ReflectionMethod(ComposerLocalHelper::class, 'composerCommand');
        $method->setAccessible(true);

        return $method->invoke(null, $arguments);
    }
}
