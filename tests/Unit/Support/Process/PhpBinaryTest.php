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

use App\Support\Process\PhpBinary;
use Tests\TestCase;

/**
 * Core starts composer through an interpreter it resolves itself. Using
 * PHP_BINARY instead is only correct under CLI: on PHP-FPM it is the FPM
 * binary, which ignores the script it is handed, prints its usage on stdout
 * and exits 64 — the reason every `composer dump-autoload` fired from a web
 * request was a silent no-op there.
 */
class PhpBinaryTest extends TestCase
{
    public function test_it_resolves_an_executable_interpreter(): void
    {
        $php = PhpBinary::cli();

        $this->assertNotNull($php, 'No CLI interpreter was found on this machine.');
        $this->assertFileExists($php);
        $this->assertTrue(is_executable($php), "{$php} is not executable.");
    }

    public function test_the_resolved_binary_is_not_an_fpm_or_cgi_sapi(): void
    {
        $name = basename((string) PhpBinary::cli());

        $this->assertStringNotContainsString('fpm', $name);
        $this->assertStringNotContainsString('cgi', $name);
    }

    public function test_it_actually_runs_a_script(): void
    {
        $php = (string) PhpBinary::cli();

        $output = [];
        $exitCode = 1;
        exec(escapeshellarg($php).' -r '.escapeshellarg('echo PHP_SAPI;').' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, 'The resolved interpreter did not run a script: '.implode("\n", $output));
        $this->assertSame('cli', trim(implode('', $output)));
    }

    public function test_composer_runs_are_not_started_with_php_binary(): void
    {
        // PHP_SAPI is cli under PHPUnit, so PHP_BINARY happens to be right
        // here and no behavioural test can tell the two apart. Guard the
        // source instead: this is exactly the line that regressed.
        $code = $this->codeWithoutComments(app_path('Helpers/ComposerLocalHelper.php'));

        $this->assertStringNotContainsString(
            'PHP_BINARY',
            $code,
            'ComposerLocalHelper must resolve an interpreter through PhpBinary, not use PHP_BINARY.'
        );
        $this->assertStringContainsString('PhpBinary::cli()', $code);
    }

    /**
     * The file's code with comments removed: the doc block explains why
     * PHP_BINARY is wrong here, and naming it there must not trip the check.
     */
    private function codeWithoutComments(string $path): string
    {
        $code = '';

        foreach (token_get_all((string) file_get_contents($path)) as $token) {
            if (! is_array($token)) {
                $code .= $token;

                continue;
            }

            if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                continue;
            }

            $code .= $token[1];
        }

        return $code;
    }
}
