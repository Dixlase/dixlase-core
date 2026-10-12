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

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\ComposerLocalManifest;
use PHPUnit\Framework\TestCase;

/**
 * A plugin that is not enabled keeps its PSR-4 roots in composer.local.json
 * but contributes no `autoload.files`: Composer requires those on every
 * request, whatever the plugin's state.
 *
 * Pure filesystem: a throwaway tree under storage/framework/testing/, never
 * the real plugins/ directory.
 */
class ComposerLocalManifestDisabledPluginsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = \dirname(__DIR__, 3).'/storage/framework/testing/composer-local-disabled-'.uniqid('', true);
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);

        parent::tearDown();
    }

    public function test_a_disabled_plugin_keeps_psr4_but_contributes_no_files(): void
    {
        $this->makePlugin('Enabled');
        $this->makePlugin('Disabled');
        ComposerLocalManifest::writeDisabledPlugins($this->root, ['Disabled']);

        $autoload = ComposerLocalManifest::build($this->root)['autoload'];

        $this->assertArrayHasKey('Plugins\\Disabled\\App\\', $autoload['psr-4']);
        $this->assertSame(['plugins/Enabled/helpers.php'], $autoload['files']);
    }

    public function test_without_a_state_file_every_plugin_contributes_its_files(): void
    {
        $this->makePlugin('Alpha');
        $this->makePlugin('Beta');

        $files = ComposerLocalManifest::build($this->root)['autoload']['files'];

        $this->assertSame(['plugins/Alpha/helpers.php', 'plugins/Beta/helpers.php'], $files);
    }

    public function test_an_unreadable_state_file_withholds_nothing(): void
    {
        $this->makePlugin('Alpha');
        $path = $this->root.'/'.ComposerLocalManifest::DISABLED_PLUGINS_FILE;
        mkdir(\dirname($path), 0777, true);
        file_put_contents($path, '{not json');

        $this->assertSame([], ComposerLocalManifest::disabledPlugins($this->root));
        $this->assertSame(
            ['plugins/Alpha/helpers.php'],
            ComposerLocalManifest::build($this->root)['autoload']['files']
        );
    }

    public function test_the_state_file_round_trips_sorted_and_unique(): void
    {
        $this->assertTrue(ComposerLocalManifest::writeDisabledPlugins($this->root, ['Zeta', 'Alpha', 'Zeta', '']));

        $this->assertSame(['Alpha', 'Zeta'], ComposerLocalManifest::disabledPlugins($this->root));
    }

    public function test_when_every_plugin_is_disabled_the_files_section_is_omitted(): void
    {
        $this->makePlugin('Only');
        ComposerLocalManifest::writeDisabledPlugins($this->root, ['Only']);

        $this->assertArrayNotHasKey('files', ComposerLocalManifest::build($this->root)['autoload']);
    }

    public function test_an_entry_naming_a_missing_file_is_dropped(): void
    {
        $this->makePlugin('Partial');
        file_put_contents(
            $this->root.'/plugins/Partial/composer.json',
            '{"autoload":{"files":["helpers.php","gone.php"]}}'
        );

        $this->assertSame(
            ['plugins/Partial/helpers.php'],
            ComposerLocalManifest::build($this->root)['autoload']['files']
        );
    }

    public function test_a_theme_is_not_affected_by_the_plugin_list(): void
    {
        $dir = $this->root.'/themes/Same';
        mkdir($dir.'/app', 0777, true);
        file_put_contents($dir.'/theme.json', '{}');
        file_put_contents($dir.'/helpers.php', "<?php\n");
        file_put_contents($dir.'/composer.json', '{"autoload":{"files":["helpers.php"]}}');
        ComposerLocalManifest::writeDisabledPlugins($this->root, ['Same']);

        $this->assertSame(
            ['themes/Same/helpers.php'],
            ComposerLocalManifest::build($this->root)['autoload']['files']
        );
    }

    private function makePlugin(string $name): void
    {
        $dir = $this->root.'/plugins/'.$name;
        mkdir($dir.'/app', 0777, true);
        file_put_contents($dir.'/plugin.json', '{"name":"'.$name.'"}');
        file_put_contents($dir.'/helpers.php', "<?php\n");
        file_put_contents($dir.'/composer.json', '{"autoload":{"files":["helpers.php"]}}');
    }

    private function deleteTree(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $child = $path.'/'.$entry;
            is_dir($child) && ! is_link($child) ? $this->deleteTree($child) : @unlink($child);
        }

        @rmdir($path);
    }
}
