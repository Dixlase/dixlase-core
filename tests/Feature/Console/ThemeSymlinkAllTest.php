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

    protected function tearDown(): void
    {
        foreach (self::FIXTURE_NAMES as $name) {
            $link = public_path("assets/themes/{$name}");
            if (is_link($link) || File::exists($link)) {
                @unlink($link);
            }
            $themeDir = base_path("themes/{$name}");
            if (File::isDirectory($themeDir)) {
                File::deleteDirectory($themeDir);
            }
        }

        parent::tearDown();
    }

    public function test_create_all_links_themes_with_resources_assets_and_skips_others(): void
    {
        [$withAssets, $withoutAssets] = self::FIXTURE_NAMES;

        File::ensureDirectoryExists(base_path("themes/{$withAssets}/resources/assets"));
        File::put(
            base_path("themes/{$withAssets}/resources/assets/thumbnail.png"),
            'fixture-png',
        );
        File::ensureDirectoryExists(base_path("themes/{$withoutAssets}"));

        $this->artisan('dls:theme:symlink', ['action' => 'create', '--all' => true])
            ->assertExitCode(0);

        $this->assertTrue(
            is_link(public_path("assets/themes/{$withAssets}")),
            'theme with resources/assets must get a symlink',
        );
        $this->assertFalse(
            is_link(public_path("assets/themes/{$withoutAssets}")),
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
