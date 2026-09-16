<?php

/**
 * This file is part of Dixlase Core.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase Core is licensed under the GNU Affero General Public License
 * version 3 or later, as published by the Free Software Foundation.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Tests\Unit\Helpers;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Guards the contract of `resolve_manifest_asset_url` (in
 * `app/Helpers/AssetHelper.php`), the helper that resolves a Vite
 * manifest source key (e.g. `resources/src/common/scss/style.scss`)
 * to its currently-live hashed public URL. This helper is what
 * `load_assets` and `load_front_assets` fall back to for the bundled
 * `common.css` after the migration from stable `css/common.css` URLs
 * to Vite content-hashed `css/common-<hash>.css` URLs.
 *
 * Without a working resolver, front-page requests would 404 on the
 * Tailwind + commons stylesheet on every deploy where the CSS bytes
 * changed — exactly the failure this class of tests exists to catch
 * before it reaches production.
 */
class ResolveManifestAssetUrlTest extends TestCase
{
    private string $tempDir;

    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Throwaway directory under storage/framework/testing per the
        // root CLAUDE.md "Tests Must Not Touch Tracked Working-Tree
        // Files" rule. Never point the resolver at the real
        // public/assets/build/manifest.json — the resolver reads the
        // file, so pointing it at the tracked path would work but
        // couples the test to whatever the current build happens to
        // have produced.
        $this->tempDir = storage_path('framework/testing/manifest-'.uniqid());
        File::makeDirectory($this->tempDir, 0755, true);
        $this->manifestPath = $this->tempDir.'/manifest.json';
    }

    protected function tearDown(): void
    {
        if (isset($this->tempDir) && File::isDirectory($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    public function test_returns_hashed_asset_url_for_a_known_source_key(): void
    {
        File::put($this->manifestPath, (string) json_encode([
            'resources/src/common/scss/style.scss' => [
                'file' => 'css/common-a1b2c3d4.css',
                'src' => 'resources/src/common/scss/style.scss',
                'isEntry' => true,
            ],
        ]));

        $url = resolve_manifest_asset_url(
            'resources/src/common/scss/style.scss',
            $this->manifestPath,
            'assets/build/'
        );

        // Full URL prefix depends on APP_URL, so anchor on the suffix.
        $this->assertNotNull($url, 'The helper must resolve a manifest-listed source to a non-null URL.');
        $this->assertStringEndsWith('/assets/build/css/common-a1b2c3d4.css', $url);
    }

    public function test_returns_null_when_the_manifest_file_is_missing(): void
    {
        $missing = $this->tempDir.'/does-not-exist.json';

        $url = resolve_manifest_asset_url(
            'resources/src/common/scss/style.scss',
            $missing,
            'assets/build/'
        );

        // A missing manifest is not an error condition — Core startup
        // may run before the build has been produced. Return null so
        // callers can suppress the tag without try/catch churn.
        $this->assertNull($url);
    }

    public function test_returns_null_when_the_source_key_is_absent_from_the_manifest(): void
    {
        File::put($this->manifestPath, (string) json_encode([
            'resources/src/admin/js/app.js' => [
                'file' => 'js/admin-abc.js',
            ],
        ]));

        $url = resolve_manifest_asset_url(
            'resources/src/common/scss/style.scss',
            $this->manifestPath,
            'assets/build/'
        );

        // Missing key must not throw — a plugin/theme that removed one
        // of its entries should degrade gracefully, not crash the page.
        $this->assertNull($url);
    }

    public function test_returns_null_when_the_manifest_entry_has_no_file_key(): void
    {
        File::put($this->manifestPath, (string) json_encode([
            'resources/src/common/scss/style.scss' => [
                // 'file' deliberately omitted — a malformed manifest
                // entry (e.g. produced by a broken build) must not
                // crash the helper.
                'src' => 'resources/src/common/scss/style.scss',
            ],
        ]));

        $url = resolve_manifest_asset_url(
            'resources/src/common/scss/style.scss',
            $this->manifestPath,
            'assets/build/'
        );

        $this->assertNull($url);
    }
}
