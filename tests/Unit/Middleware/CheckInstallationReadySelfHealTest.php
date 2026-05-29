<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckInstallationReady;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Targets the self-heal logic for the INSTALLED env flag.
 *
 * When the database clearly indicates a completed install (migrations +
 * required core tables + at least one admin user + site_settings.site_name)
 * but the .env INSTALLED flag is missing or false, CheckInstallationReady
 * restores the flag rather than redirecting admins to /install/complete
 * on every fresh session. These tests pin the .env-side behaviour of
 * that restoration.
 */
class CheckInstallationReadySelfHealTest extends TestCase
{
    private CheckInstallationReady $middleware;

    private ReflectionMethod $selfHeal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new CheckInstallationReady();
        $this->selfHeal = new ReflectionMethod($this->middleware, 'selfHealInstalledFlag');
        $this->selfHeal->setAccessible(true);
    }

    public function test_replaces_existing_installed_false_line_in_place(): void
    {
        $envPath = $this->makeTempEnv("APP_NAME=Dixlase\nINSTALLED=false\nAPP_ENV=local\n");

        $this->selfHeal->invoke($this->middleware, $envPath);

        $contents = file_get_contents($envPath);
        $this->assertStringContainsString("\nINSTALLED=true\n", "\n".$contents);
        $this->assertStringNotContainsString('INSTALLED=false', $contents);
        // Surrounding lines are preserved (no clobber of adjacent config).
        $this->assertStringContainsString('APP_NAME=Dixlase', $contents);
        $this->assertStringContainsString('APP_ENV=local', $contents);

        @unlink($envPath);
    }

    public function test_appends_installed_line_when_missing(): void
    {
        $envPath = $this->makeTempEnv("APP_NAME=Dixlase\nAPP_ENV=local\n");

        $this->selfHeal->invoke($this->middleware, $envPath);

        $contents = file_get_contents($envPath);
        $this->assertStringContainsString('INSTALLED=true', $contents);
        $this->assertStringContainsString('APP_NAME=Dixlase', $contents);

        @unlink($envPath);
    }

    public function test_propagates_value_to_process_environment(): void
    {
        $envPath = $this->makeTempEnv("INSTALLED=false\n");
        putenv('INSTALLED');
        unset($_ENV['INSTALLED'], $_SERVER['INSTALLED']);

        $this->selfHeal->invoke($this->middleware, $envPath);

        $this->assertSame('true', getenv('INSTALLED'));
        $this->assertSame('true', $_ENV['INSTALLED'] ?? null);
        $this->assertSame('true', $_SERVER['INSTALLED'] ?? null);

        @unlink($envPath);
    }

    public function test_tolerates_missing_env_file_and_still_updates_process_env(): void
    {
        $envPath = sys_get_temp_dir().'/dixlase-nonexistent-'.uniqid().'.env';
        $this->assertFileDoesNotExist($envPath);
        putenv('INSTALLED');
        unset($_ENV['INSTALLED'], $_SERVER['INSTALLED']);

        // Should not throw; process env should still be updated so the
        // current request can continue.
        $this->selfHeal->invoke($this->middleware, $envPath);

        $this->assertSame('true', getenv('INSTALLED'));
    }

    public function test_replaces_only_first_installed_line_and_leaves_no_extras(): void
    {
        // A real .env should never have duplicates, but guard against
        // accidental duplication producing a third INSTALLED line.
        $envPath = $this->makeTempEnv("INSTALLED=false\nFOO=bar\nINSTALLED=false\n");

        $this->selfHeal->invoke($this->middleware, $envPath);

        $contents = file_get_contents($envPath);
        $matches = preg_match_all('/^INSTALLED=true$/m', $contents);
        $this->assertSame(2, $matches);
        $this->assertStringNotContainsString('INSTALLED=false', $contents);

        @unlink($envPath);
    }

    private function makeTempEnv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dixlase-env-');
        file_put_contents($path, $contents);

        return $path;
    }
}
