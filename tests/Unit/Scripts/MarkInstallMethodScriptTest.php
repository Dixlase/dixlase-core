<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace Tests\Unit\Scripts;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * scripts/mark-install-method.php records the install method in .env for
 * `composer create-project` (dixlase-core#883), without overriding a value
 * an installer already wrote.
 */
class MarkInstallMethodScriptTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('framework/testing/mark-install-method-'.uniqid());
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_it_appends_the_method_when_missing(): void
    {
        $env = $this->env("APP_KEY=x\n");

        $this->runScript('composer', $env);

        $this->assertMatchesRegularExpression('/^DIXLASE_INSTALL_METHOD=composer$/m', (string) file_get_contents($env));
        $this->assertStringStartsWith("APP_KEY=x\n", (string) file_get_contents($env));
    }

    public function test_it_fills_an_empty_placeholder(): void
    {
        $env = $this->env("A=1\nDIXLASE_INSTALL_METHOD=\nB=2\n");

        $this->runScript('composer', $env);

        $this->assertSame("A=1\nDIXLASE_INSTALL_METHOD=composer\nB=2\n", file_get_contents($env));
    }

    public function test_it_keeps_a_value_already_set(): void
    {
        $env = $this->env("DIXLASE_INSTALL_METHOD=oneliner\n");

        $this->runScript('composer', $env);

        $this->assertSame("DIXLASE_INSTALL_METHOD=oneliner\n", file_get_contents($env));
    }

    public function test_it_refuses_an_unknown_method(): void
    {
        $env = $this->env("A=1\n");

        $this->assertSame(1, $this->runScript('zip', $env));
        $this->assertSame("A=1\n", file_get_contents($env));
    }

    private function env(string $contents): string
    {
        $path = $this->dir.'/.env';
        file_put_contents($path, $contents);

        return $path;
    }

    private function runScript(string $method, string $envFile): int
    {
        exec(
            escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('scripts/mark-install-method.php'))
                .' '.escapeshellarg($method).' '.escapeshellarg($envFile).' 2>/dev/null',
            $output,
            $code
        );

        return $code;
    }
}
