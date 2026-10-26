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

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Symmetric coverage for the `--all` flag on `dls:theme:symlink`.
 *
 * Themes are less prone to hitting the missing-symlink bug in
 * production because most installs run one active theme, but the
 * command shape mirrors the plugin symlink one so we pin the same
 * behaviour to keep the two implementations from drifting.
 */
class ThemeSymlinkAllTest extends TestCase
{
    private const FIXTURE_NAMES = [
        '__test_theme_symlink_all_with_assets__',
        '__test_theme_symlink_all_without_assets__',
    ];

    /**
     * Throwaway stand-ins for the themes and asset-links directories.
     *
     * The fixtures used to be written into the real ones, where core's
     * extension scan finds them if a run is interrupted before tearDown
     * (#653). The command reads both roots from config now.
     */
    private ?string $themesRoot = null;

    private ?string $linksRoot = null;

    protected function setUp(): void
    {
        parent::setUp();

        $unique = uniqid('', true);
        $this->themesRoot = storage_path('framework/testing/symlink-themes-'.$unique);
        $this->linksRoot = storage_path('framework/testing/symlink-theme-links-'.$unique);
        File::ensureDirectoryExists($this->themesRoot);
        File::ensureDirectoryExists($this->linksRoot);

        config([
            'themes.theme_directory' => $this->themesRoot,
            'themes.asset_links_directory' => $this->linksRoot,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([$this->linksRoot, $this->themesRoot] as $directory) {
            if ($directory !== null && File::isDirectory($directory)) {
                File::deleteDirectory($directory);
            }
        }

        parent::tearDown();
    }

    private function themePath(string $name): string
    {
        return $this->themesRoot.DIRECTORY_SEPARATOR.$name;
    }

    private function linkPath(string $name): string
    {
        return $this->linksRoot.DIRECTORY_SEPARATOR.$name;
    }

    public function test_create_all_links_themes_with_resources_assets_and_skips_others(): void
    {
        [$withAssets, $withoutAssets] = self::FIXTURE_NAMES;

        File::ensureDirectoryExists($this->themePath($withAssets).'/resources/assets');
        File::put(
            $this->themePath($withAssets).'/resources/assets/thumbnail.png',
            'fixture-png',
        );
        File::ensureDirectoryExists($this->themePath($withoutAssets));

        $this->artisan('dls:theme:symlink', ['action' => 'create', '--all' => true])
            ->assertExitCode(0);

        $this->assertTrue(
            is_link($this->linkPath($withAssets)),
            'theme with resources/assets must get a symlink',
        );
        $this->assertFalse(
            is_link($this->linkPath($withoutAssets)),
            'theme without resources/assets must not get a symlink',
        );
    }

    public function test_missing_theme_argument_without_all_flag_errors_out(): void
    {
        $this->artisan('dls:theme:symlink', ['action' => 'create'])
            ->assertExitCode(1);
    }

    public function test_theme_argument_together_with_all_flag_errors_out(): void
    {
        $this->artisan('dls:theme:symlink', [
            'action' => 'create',
            'theme' => 'AnyThemeDir',
            '--all' => true,
        ])->assertExitCode(1);
    }
}
