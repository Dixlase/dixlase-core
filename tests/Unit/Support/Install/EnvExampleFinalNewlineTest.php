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

namespace Tests\Unit\Support\Install;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pins the final newline of `.env.example`.
 *
 * Every install path copies that file to `.env` and then writes settings
 * into the copy. Without the newline, a setting appended with `>>`
 * continues the last line (`HSTS_PRELOAD=falseAPP_KEY=base64:...`), so
 * `CheckInstallationReady`'s `/^APP_KEY=(.+)$/m` finds no key, generates
 * one and redirects the request to its own URL. Every official extension
 * builds its CI `.env` that way, so the first HTTP request of each run
 * checked nothing. `.editorconfig` already asks for the newline and did
 * not keep it, which is why this is a test (dixlase-core#538).
 */
class EnvExampleFinalNewlineTest extends TestCase
{
    private ?string $workDir = null;

    protected function tearDown(): void
    {
        if ($this->workDir !== null && is_dir($this->workDir)) {
            foreach (glob($this->workDir.'/{,.}*', GLOB_BRACE) ?: [] as $entry) {
                if (is_file($entry)) {
                    unlink($entry);
                }
            }
            rmdir($this->workDir);
        }

        $this->workDir = null;

        parent::tearDown();
    }

    #[Test]
    public function env_example_ends_with_a_newline(): void
    {
        $path = base_path('.env.example');

        $this->assertFileExists($path);

        $contents = (string) file_get_contents($path);

        $this->assertNotSame('', $contents, '.env.example is empty.');
        $this->assertStringEndsWith(
            "\n",
            $contents,
            '.env.example must end with a newline, so that a setting appended to a copy of it '
            .'with `>>` lands on its own line instead of continuing the last one.'
        );
    }

    #[Test]
    public function a_setting_appended_to_a_copy_lands_on_its_own_line(): void
    {
        // A throwaway directory, never the repository tree: this test writes.
        $this->workDir = storage_path('framework/testing/env-example-'.uniqid());
        $this->assertTrue(mkdir($this->workDir, 0o777, true));

        $copy = $this->workDir.'/.env';
        $this->assertTrue(copy(base_path('.env.example'), $copy));

        // The form every extension's CI workflow uses.
        $key = 'base64:'.base64_encode(str_repeat('k', 32));
        file_put_contents($copy, 'APP_KEY='.$key.PHP_EOL, FILE_APPEND);

        $contents = (string) file_get_contents($copy);

        $this->assertStringNotContainsString(
            'HSTS_PRELOAD=falseAPP_KEY=',
            $contents,
            'The appended setting was glued onto the last line of .env.example.'
        );

        // What CheckInstallationReady asks of the file.
        $this->assertSame(
            1,
            preg_match('/^APP_KEY=(.+)$/m', $contents, $matches),
            'An APP_KEY appended to the copy is not readable as its own line.'
        );
        $this->assertSame($key, $matches[1]);
    }
}
