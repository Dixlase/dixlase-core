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
 * Pins the `--all` bulk flag behaviour on `dls:plugin:symlink`.
 *
 * Regression target: on production Brand, symlinks for six of the
 * eleven installed plugins were missing (their `resources/assets/`
 * directories existed on disk but no matching link under
 * `public/assets/plugins/`), so the ExtensionCardPresenter emitted
 * thumbnail URLs that 404-ed. The pre-flag command required a plugin
 * argument, which meant reconciling the state was a manual per-plugin
 * shell loop. `--all` closes that gap and gives deploy pipelines a
 * single idempotent hook.
 */
class PluginSymlinkAllTest extends TestCase
{
    private const FIXTURE_NAMES = [
        '__test_symlink_all_with_assets__',
        '__test_symlink_all_without_assets__',
        '__test_symlink_all_stale_link__',
    ];

    protected function tearDown(): void
    {
        foreach (self::FIXTURE_NAMES as $name) {
            $link = public_path("assets/plugins/{$name}");
            if (is_link($link) || File::exists($link)) {
                @unlink($link);
            }
            $pluginDir = base_path("plugins/{$name}");
            if (File::isDirectory($pluginDir)) {
                File::deleteDirectory($pluginDir);
            }
        }

        parent::tearDown();
    }

    public function test_create_all_links_plugins_with_resources_assets_and_skips_others(): void
    {
        [$withAssets, $withoutAssets] = [
            self::FIXTURE_NAMES[0],
            self::FIXTURE_NAMES[1],
        ];

        File::ensureDirectoryExists(base_path("plugins/{$withAssets}/resources/assets"));
        File::put(
            base_path("plugins/{$withAssets}/resources/assets/thumbnail.png"),
            'fixture-png',
        );
        // Second fixture has no resources/assets subtree at all — must
        // be silently skipped, not counted as a failure.
        File::ensureDirectoryExists(base_path("plugins/{$withoutAssets}"));

        $this->artisan('dls:plugin:symlink', ['action' => 'create', '--all' => true])
            ->assertExitCode(0);

        $this->assertTrue(
            is_link(public_path("assets/plugins/{$withAssets}")),
            'plugin with resources/assets must get a symlink',
        );
        $this->assertFalse(
            is_link(public_path("assets/plugins/{$withoutAssets}")),
            'plugin without resources/assets must not get a symlink',
        );
    }

    public function test_create_all_is_idempotent(): void
    {
        $withAssets = self::FIXTURE_NAMES[0];

        File::ensureDirectoryExists(base_path("plugins/{$withAssets}/resources/assets"));

        $this->artisan('dls:plugin:symlink', ['action' => 'create', '--all' => true])
            ->assertExitCode(0);
        $firstTarget = readlink(public_path("assets/plugins/{$withAssets}"));

        $this->artisan('dls:plugin:symlink', ['action' => 'create', '--all' => true])
            ->assertExitCode(0);

        $this->assertTrue(
            is_link(public_path("assets/plugins/{$withAssets}")),
            'symlink must survive a second create --all run',
        );
        $this->assertSame(
            $firstTarget,
            readlink(public_path("assets/plugins/{$withAssets}")),
            'idempotent re-run must not re-create the symlink with a different target',
        );
    }

    public function test_remove_all_deletes_stale_links_whose_plugin_directory_is_gone(): void
    {
        $stale = self::FIXTURE_NAMES[2];

        // Fabricate a stale link pointing at a plugin directory that
        // no longer exists. `remove --all` must clear it regardless.
        File::ensureDirectoryExists(public_path('assets/plugins'));
        $link = public_path("assets/plugins/{$stale}");
        symlink('/nonexistent/plugin/path', $link);

        $this->artisan('dls:plugin:symlink', ['action' => 'remove', '--all' => true])
            ->assertExitCode(0);

        $this->assertFalse(
            is_link($link) || File::exists($link),
            'stale link with a missing target must still be removed by --all',
        );
    }

    public function test_missing_plugin_argument_without_all_flag_errors_out(): void
    {
        $this->artisan('dls:plugin:symlink', ['action' => 'create'])
            ->assertExitCode(1);
    }

    public function test_plugin_argument_together_with_all_flag_errors_out(): void
    {
        $this->artisan('dls:plugin:symlink', [
            'action' => 'create',
            'plugin' => 'AnyPluginDir',
            '--all' => true,
        ])->assertExitCode(1);
    }
}
