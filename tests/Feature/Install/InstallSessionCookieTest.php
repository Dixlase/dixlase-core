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

namespace Tests\Feature\Install;

use App\Http\Controllers\Install\InstallCompleteController;
use ReflectionMethod;
use Tests\TestCase;

/**
 * An installation gets a session cookie name of its own, so two Dixlase
 * sites on one hostname cannot overwrite each other's session.
 *
 * That name must not be chosen while the wizard is running. The confirm
 * step writes .env and then works for another minute, and every later
 * request reads the new name while the browser still holds the old one:
 * the session comes back empty and the step middleware sends the operator
 * back to step 1 with every field cleared. So the name is chosen in
 * finalize(), and only when .env does not already carry one.
 */
class InstallSessionCookieTest extends TestCase
{
    private string $workDir = '';

    protected function tearDown(): void
    {
        if ($this->workDir !== '' && is_dir($this->workDir)) {
            foreach (glob($this->workDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->workDir);
        }

        parent::tearDown();
    }

    public function test_a_generated_name_is_derived_from_the_site_name(): void
    {
        $name = $this->generateName('My Test Site');

        $this->assertMatchesRegularExpression('/^my-test-site_[a-z0-9]{4}_session$/', $name);
    }

    public function test_each_installation_gets_its_own_name(): void
    {
        $this->assertNotSame($this->generateName('Same Site'), $this->generateName('Same Site'));
    }

    public function test_a_site_without_a_usable_name_still_gets_one(): void
    {
        $this->assertMatchesRegularExpression('/^dixlase_[a-z0-9]{4}_session$/', $this->generateName(''));
        $this->assertMatchesRegularExpression('/^dixlase_[a-z0-9]{4}_session$/', $this->generateName(null));
        // A name that slugs to nothing (Japanese, punctuation) must not
        // produce a cookie name starting with an underscore.
        $this->assertMatchesRegularExpression('/^dixlase_[a-z0-9]{4}_session$/', $this->generateName('！？'));
    }

    public function test_an_existing_value_is_read_back_from_the_env_file(): void
    {
        $envPath = $this->writeEnv("APP_NAME=Dixlase\nSESSION_COOKIE=\"kept_ab12_session\"\nINSTALLED=false\n");

        $this->assertSame('kept_ab12_session', $this->readValue('SESSION_COOKIE', $envPath));
    }

    public function test_a_blank_entry_reads_as_empty(): void
    {
        $envPath = $this->writeEnv("APP_NAME=Dixlase\nSESSION_COOKIE=\nINSTALLED=false\n");

        $this->assertSame('', $this->readValue('SESSION_COOKIE', $envPath));
    }

    public function test_a_missing_entry_reads_as_empty(): void
    {
        $envPath = $this->writeEnv("APP_NAME=Dixlase\nINSTALLED=false\n");

        $this->assertSame('', $this->readValue('SESSION_COOKIE', $envPath));
    }

    public function test_a_missing_env_file_reads_as_empty(): void
    {
        $this->assertSame('', $this->readValue('SESSION_COOKIE', storage_path('framework/testing/absent-'.uniqid().'.env')));
    }

    private function generateName(?string $siteName): string
    {
        $method = new ReflectionMethod(InstallCompleteController::class, 'generateSessionCookieName');
        $method->setAccessible(true);

        return $method->invoke(new InstallCompleteController(), $siteName);
    }

    private function readValue(string $key, string $envPath): string
    {
        $method = new ReflectionMethod(InstallCompleteController::class, 'readEnvValue');
        $method->setAccessible(true);

        return $method->invoke(new InstallCompleteController(), $key, $envPath);
    }

    private function writeEnv(string $contents): string
    {
        $this->workDir = storage_path('framework/testing/env-'.uniqid());
        mkdir($this->workDir, 0775, true);
        $envPath = $this->workDir.'/.env';
        file_put_contents($envPath, $contents);

        return $envPath;
    }
}
