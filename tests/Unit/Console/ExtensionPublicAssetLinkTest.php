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

declare(strict_types=1);

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * An extension's compiled assets are served through a symlink in the web
 * root. The link is only made for an extension that has something to serve,
 * so an extension which gains compiled assets in an update needs the link
 * made at that point -- nothing else will do it, and the symptom is a front
 * view without its stylesheet rather than an error.
 */
class ExtensionPublicAssetLinkTest extends TestCase
{
    private string $pluginsRoot;

    private string $linksRoot;

    protected function setUp(): void
    {
        parent::setUp();

        // Throwaway roots: pointed at the real directories, this test would
        // remove the checkout's own links, silently, because they are
        // gitignored.
        $unique = uniqid('', true);
        $this->pluginsRoot = storage_path("framework/testing/asset-link-plugins-{$unique}");
        $this->linksRoot = storage_path("framework/testing/asset-link-public-{$unique}");

        File::ensureDirectoryExists($this->pluginsRoot);
        File::ensureDirectoryExists($this->linksRoot);

        config([
            'plugins.plugins_directory' => $this->pluginsRoot,
            'plugins.asset_links_directory' => $this->linksRoot,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pluginsRoot);
        File::deleteDirectory($this->linksRoot);

        parent::tearDown();
    }

    public function test_a_plugin_with_no_compiled_assets_gets_no_link(): void
    {
        File::ensureDirectoryExists($this->pluginsRoot.'/Example/app');

        $this->artisan('dls:plugin:symlink', ['action' => 'create', 'plugin' => 'Example'])
            ->assertExitCode(0);

        $this->assertFalse(
            is_link($this->linksRoot.'/Example'),
            'A plugin with no resources/assets has nothing to serve, so no link is made.'
        );
    }

    public function test_a_plugin_that_has_compiled_assets_gets_a_link(): void
    {
        File::ensureDirectoryExists($this->pluginsRoot.'/Example/resources/assets');
        File::put($this->pluginsRoot.'/Example/resources/assets/manifest.json', '{}');

        $this->artisan('dls:plugin:symlink', ['action' => 'create', 'plugin' => 'Example'])
            ->assertExitCode(0);

        $link = $this->linksRoot.'/Example';

        $this->assertTrue(is_link($link), 'The link to the plugin assets was not created.');
        $this->assertFileExists($link.'/manifest.json');
    }

    /**
     * The regression this test exists for: the link was made when a plugin
     * was enabled and refreshed by a theme update, but a plugin update left
     * it alone. A plugin whose new version is the first to carry compiled
     * assets therefore had no link at all.
     */
    public function test_both_update_commands_link_the_assets_they_just_placed(): void
    {
        $commands = [
            'PluginUpdate' => 'dls:plugin:symlink',
            'ThemeUpdate' => 'dls:theme:symlink',
        ];

        foreach ($commands as $command => $symlinkCommand) {
            $source = (string) file_get_contents(app_path("Console/Commands/{$command}.php"));

            $build = strpos($source, 'buildExtensionAssets($livePath');
            $link = strpos($source, $symlinkCommand);

            $this->assertNotFalse($build, "{$command}: the asset build step moved.");
            $this->assertNotFalse(
                $link,
                "{$command} must create the public asset link, or an extension that gains "
                .'compiled assets in the update has none.'
            );
            $this->assertLessThan(
                $link,
                $build,
                "{$command}: the link must be made after the assets it points at are in place."
            );
        }
    }
}
