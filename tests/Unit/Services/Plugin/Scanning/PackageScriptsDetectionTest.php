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

namespace Tests\Unit\Services\Plugin\Scanning;

use App\Services\Plugin\Scanning\PatternRegistry;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Install-time npm lifecycle scripts in an extension's package.json are
 * reported as a dangerous API (security review X6). Build hooks are not.
 * The fixture lives under storage/framework/testing.
 */
class PackageScriptsDetectionTest extends TestCase
{
    private string $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = storage_path('framework/testing/package-scripts-'.uniqid());
        File::ensureDirectoryExists($this->extension);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->extension);

        parent::tearDown();
    }

    public function test_install_time_scripts_are_flagged_for_plugins_and_themes(): void
    {
        $this->writePackage(['postinstall' => 'node evil.js', 'prepare' => 'sh x.sh', 'build' => 'vite build']);

        foreach (['plugin', 'theme'] as $type) {
            $result = PatternRegistry::createDefault()->scan($this->extension, $type);

            $this->assertTrue($result['permissions']['dangerous_api.npm_lifecycle_scripts'] ?? false, $type);
            $files = array_column($result['evidence']['dangerous_api.npm_lifecycle_scripts'], 'file');
            $this->assertSame(['package.json (scripts.postinstall)', 'package.json (scripts.prepare)'], $files);
        }
    }

    public function test_build_hooks_are_not_flagged(): void
    {
        $this->writePackage(['prebuild' => 'node check.mjs', 'build' => 'vite build', 'postbuild' => 'node x.mjs', 'dev' => 'vite']);

        $result = PatternRegistry::createDefault()->scan($this->extension, 'plugin');

        $this->assertFalse($result['permissions']['dangerous_api.npm_lifecycle_scripts'] ?? false);
    }

    public function test_no_or_invalid_package_json_is_not_flagged(): void
    {
        $result = PatternRegistry::createDefault()->scan($this->extension, 'plugin');
        $this->assertFalse($result['permissions']['dangerous_api.npm_lifecycle_scripts'] ?? false);

        File::put($this->extension.'/package.json', '{not json');
        $result = PatternRegistry::createDefault()->scan($this->extension, 'plugin');
        $this->assertFalse($result['permissions']['dangerous_api.npm_lifecycle_scripts'] ?? false);
    }

    /**
     * @param  array<string, string>  $scripts
     */
    private function writePackage(array $scripts): void
    {
        File::put($this->extension.'/package.json', json_encode(['name' => 'x', 'scripts' => $scripts]));
    }
}
