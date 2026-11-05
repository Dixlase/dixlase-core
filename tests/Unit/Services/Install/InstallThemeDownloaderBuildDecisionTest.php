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

namespace Tests\Unit\Services\Install;

use App\Services\Install\InstallThemeDownloader;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A theme that ships its build is not rebuilt at install time.
 *
 * A theme release builds its stylesheet and scripts once and the result is
 * covered by the theme's signature, so the wizard only has to expose them
 * through the public symlink. Building again would change the bytes the
 * signature covers, and the site would report the theme as modified. A
 * theme that ships no build still has to be built here, or the pages that
 * depend on its bundle load without it.
 */
class InstallThemeDownloaderBuildDecisionTest extends TestCase
{
    private string $workDir = '';

    protected function tearDown(): void
    {
        if ($this->workDir !== '' && is_dir($this->workDir)) {
            File::deleteDirectory($this->workDir);
        }

        parent::tearDown();
    }

    private function shipsItsBuild(string $themePath): bool
    {
        $method = new ReflectionMethod(InstallThemeDownloader::class, 'themeShipsItsBuild');
        $method->setAccessible(true);

        return $method->invoke(app(InstallThemeDownloader::class), $themePath);
    }

    private function theme(): string
    {
        $this->workDir = storage_path('framework/testing/theme-'.uniqid());
        File::ensureDirectoryExists($this->workDir.'/resources/assets');

        return $this->workDir;
    }

    public function test_a_theme_with_a_vite_manifest_ships_its_build(): void
    {
        $path = $this->theme();
        File::put($path.'/resources/assets/manifest.json', '{"resources/src/front/js/app.js":{"file":"js/app-abc.js"}}');

        $this->assertTrue($this->shipsItsBuild($path));
    }

    public function test_a_theme_with_only_a_thumbnail_does_not(): void
    {
        $path = $this->theme();
        File::put($path.'/resources/assets/thumbnail.png', 'not an image');

        $this->assertFalse(
            $this->shipsItsBuild($path),
            'A non-empty resources/assets is not the signal; the vite manifest is.'
        );
    }

    public function test_a_theme_with_no_assets_directory_does_not(): void
    {
        $this->workDir = storage_path('framework/testing/theme-'.uniqid());
        File::ensureDirectoryExists($this->workDir.'/resources');

        $this->assertFalse($this->shipsItsBuild($this->workDir));
    }

    public function test_the_decision_gates_the_build_call(): void
    {
        $source = (string) file_get_contents(
            app_path('Services/Install/InstallThemeDownloader.php')
        );

        $decision = strpos($source, '$this->themeShipsItsBuild($targetPath)');
        $symlink = strpos($source, "Artisan::call('dls:theme:symlink'");
        $build = strpos($source, "Artisan::call('dls:theme:build'");

        $this->assertNotFalse($decision, 'The install path no longer asks whether the theme ships its build.');
        $this->assertNotFalse($symlink, 'The symlink-only branch is gone.');
        $this->assertNotFalse($build, 'The build branch is gone; a theme without a build would ship broken.');
        $this->assertLessThan($symlink, $decision);
        $this->assertLessThan($build, $decision);
    }

    /**
     * The release neither builds a bundled theme nor links its assets.
     *
     * Building would rewrite the bytes the theme's signature covers. Linking
     * looked harmless and was not: `zip` follows symlinks, so the archive
     * carried a dereferenced copy of the build, and after a theme update the
     * relinker keeps such a copy rather than linking -- the site then serves
     * the previous version's stylesheet silently (#898). The link is created
     * on the consuming side, by the install and by the core update.
     */
    public function test_the_release_workflow_neither_builds_nor_links_the_bundled_theme(): void
    {
        $workflow = (string) file_get_contents(base_path('.github/workflows/release.yml'));

        $this->assertStringNotContainsString(
            'npm ci && npm run build',
            $workflow,
            'The release must not rebuild a bundled theme: that changes the bytes its signature covers.'
        );
        $this->assertStringNotContainsString(
            'ln -sfn "../../../themes/$theme_name/resources/assets"',
            $workflow,
            'The release must not write the public asset link: zip dereferences it into a copy that outlives the theme version it came from.'
        );
        $this->assertStringContainsString(
            'resources/assets/manifest.json',
            $workflow,
            'The release must refuse a bundled theme that ships no build.'
        );
        $this->assertStringContainsString(
            'public/assets/themes/, which must stay empty',
            $workflow,
            'The release must fail if a file ships under public/assets/themes/.'
        );
    }
}
