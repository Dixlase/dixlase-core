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

namespace Tests\Unit\Services\Core;

use App\Services\Core\ReleaseManifest;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

/**
 * Pins the release manifest reader's contract. The manifest is the
 * opt-in signal a release ZIP uses to tell the core updater "apply
 * these theme directories in addition to the usual source + vendor" —
 * absence is the normal case, presence must be validated tightly so a
 * hostile ZIP cannot direct the updater at paths outside a theme
 * directory the operator would recognise.
 */
class ReleaseManifestTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempRoot = sys_get_temp_dir().'/release-manifest-test-'.uniqid();
        File::ensureDirectoryExists($this->tempRoot);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempRoot)) {
            File::deleteDirectory($this->tempRoot);
        }
        parent::tearDown();
    }

    public function test_returns_null_when_manifest_is_missing(): void
    {
        $this->assertNull(ReleaseManifest::readFromPayload($this->tempRoot));
    }

    public function test_parses_a_valid_manifest_with_one_bundled_theme(): void
    {
        $this->writeManifest([
            'version' => '0.2.8',
            'bundled_themes' => [
                ['slug' => 'DixlaseOnePage', 'version' => '2.0.0', 'path' => 'themes/DixlaseOnePage'],
            ],
        ]);

        $manifest = ReleaseManifest::readFromPayload($this->tempRoot);

        $this->assertNotNull($manifest);
        $this->assertSame('0.2.8', $manifest->version);
        $this->assertTrue($manifest->hasBundledThemes());
        $this->assertSame([
            ['slug' => 'DixlaseOnePage', 'version' => '2.0.0', 'path' => 'themes/DixlaseOnePage'],
        ], $manifest->bundledThemes);
    }

    public function test_parses_a_manifest_without_bundled_themes(): void
    {
        $this->writeManifest(['version' => '0.2.7']);

        $manifest = ReleaseManifest::readFromPayload($this->tempRoot);

        $this->assertNotNull($manifest);
        $this->assertSame('0.2.7', $manifest->version);
        $this->assertFalse($manifest->hasBundledThemes());
        $this->assertSame([], $manifest->bundledThemes);
    }

    public function test_throws_when_manifest_is_not_valid_json(): void
    {
        file_put_contents($this->tempRoot.'/'.ReleaseManifest::FILENAME, 'not-json-at-all');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not valid JSON');

        ReleaseManifest::readFromPayload($this->tempRoot);
    }

    public function test_rejects_bundled_theme_entry_missing_required_fields(): void
    {
        $this->writeManifest([
            'bundled_themes' => [
                ['slug' => 'DixlaseOnePage', 'version' => '2.0.0'], // no path
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("required string field 'path'");

        ReleaseManifest::readFromPayload($this->tempRoot);
    }

    public function test_rejects_bundled_theme_path_that_leaves_themes_directory(): void
    {
        $this->writeManifest([
            'bundled_themes' => [
                ['slug' => 'DixlaseOnePage', 'version' => '2.0.0', 'path' => 'themes/../vendor/evil'],
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("must match 'themes/<slug>'");

        ReleaseManifest::readFromPayload($this->tempRoot);
    }

    public function test_rejects_bundled_theme_path_whose_basename_does_not_match_the_slug(): void
    {
        $this->writeManifest([
            'bundled_themes' => [
                ['slug' => 'DixlaseOnePage', 'version' => '2.0.0', 'path' => 'themes/SomeOtherTheme'],
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('basename must equal the slug');

        ReleaseManifest::readFromPayload($this->tempRoot);
    }

    public function test_rejects_bundled_themes_top_level_value_that_is_not_an_array(): void
    {
        $this->writeManifest(['bundled_themes' => 'DixlaseOnePage']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bundled_themes must be an array');

        ReleaseManifest::readFromPayload($this->tempRoot);
    }

    private function writeManifest(array $data): void
    {
        file_put_contents(
            $this->tempRoot.'/'.ReleaseManifest::FILENAME,
            json_encode($data, JSON_PRETTY_PRINT),
        );
    }
}
