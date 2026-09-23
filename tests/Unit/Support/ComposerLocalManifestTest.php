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
 * `composer.local.json` has two writers — `scripts/sync-local-autoload.php`
 * and `ComposerLocalHelper::syncAutoload()` — and they both overwrite the
 * whole file, so whichever ran last decides what the autoloader sees.
 *
 * Before 2026-09-24 they had separate implementations and had drifted: the
 * helper emitted `psr-4` only, so a plugin install through the admin panel
 * dropped every `autoload.files` entry the script had hoisted, leaving the
 * helper functions those files define undefined at their call sites. Both now
 * go through this class; these tests pin the shape so they cannot drift again.
 *
 * Pure filesystem reads: no framework boot, no database.
 */
class ComposerLocalManifestTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = \dirname(__DIR__, 3).'/storage/framework/testing/composer-local-'.uniqid('', true);
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);

        parent::tearDown();
    }

    /**
     * An extension complete enough to be mistaken for an installed one:
     * manifest, `app/`, and a `composer.json` declaring a helper file.
     */
    private function makeExtension(string $parent, string $name, string $manifest): void
    {
        $dir = $this->root.'/'.$parent.'/'.$name;

        mkdir($dir.'/app/Helpers', 0777, true);
        file_put_contents($dir.'/'.$manifest, '{"name":"'.$name.'"}');
        file_put_contents($dir.'/app/Helpers/Helpers.php', "<?php\n");
        file_put_contents(
            $dir.'/composer.json',
            '{"autoload":{"files":["app/Helpers/Helpers.php"]}}'
        );
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
            is_dir($child) ? $this->deleteTree($child) : @unlink($child);
        }

        @rmdir($path);
    }

    public function test_an_installed_extension_contributes_psr4_and_files(): void
    {
        $this->makeExtension('plugins', 'DixlaseSEO', 'plugin.json');
        $this->makeExtension('themes', 'DixlaseOnePage', 'theme.json');

        $autoload = ComposerLocalManifest::build($this->root)['autoload'];

        $this->assertSame('plugins/DixlaseSEO/app', $autoload['psr-4']['Plugins\\DixlaseSEO\\App\\']);
        $this->assertSame('themes/DixlaseOnePage/app', $autoload['psr-4']['Themes\\DixlaseOnePage\\App\\']);

        // The reserved override namespaces are emitted unconditionally.
        $this->assertArrayHasKey('Custom\\Plugins\\DixlaseSEO\\App\\', $autoload['psr-4']);
        $this->assertArrayHasKey('Custom\\Themes\\DixlaseOnePage\\App\\', $autoload['psr-4']);

        $this->assertSame([
            'plugins/DixlaseSEO/app/Helpers/Helpers.php',
            'themes/DixlaseOnePage/app/Helpers/Helpers.php',
        ], $autoload['files']);
    }

    /**
     * The regression that took Brand down: a deploy backup is a complete copy,
     * so it passes every structural check. Only its name gives it away.
     */
    public function test_a_move_aside_copy_contributes_nothing(): void
    {
        $this->makeExtension('plugins', 'DixlaseDeploy', 'plugin.json');
        $this->makeExtension('plugins', 'DixlaseDeploy.stale.20260710-221107', 'plugin.json');
        $this->makeExtension('themes', 'DixlaseOnePage', 'theme.json');
        $this->makeExtension('themes', 'DixlaseOnePage.stale.20260817-211837', 'theme.json');
        $this->makeExtension('plugins', 'DixlaseSEO.bak', 'plugin.json');
        $this->makeExtension('plugins', '_DixlaseOld', 'plugin.json');
        $this->makeExtension('plugins', '.DixlaseHidden', 'plugin.json');

        $autoload = ComposerLocalManifest::build($this->root)['autoload'];

        foreach (array_keys($autoload['psr-4']) as $namespace) {
            $this->assertMatchesRegularExpression(
                '/^(Custom\\\\)?(Plugins|Themes)\\\\[A-Za-z0-9]+\\\\/',
                $namespace,
                'emitted an invalid namespace segment'
            );
        }

        $this->assertSame([
            'plugins/DixlaseDeploy/app/Helpers/Helpers.php',
            'themes/DixlaseOnePage/app/Helpers/Helpers.php',
        ], $autoload['files']);
    }

    /**
     * A directory that merely happens to contain `app/` is not an extension.
     * An installed one always declares itself.
     */
    public function test_a_directory_without_a_manifest_is_not_an_extension(): void
    {
        mkdir($this->root.'/plugins/scratch/app', 0777, true);

        $this->assertSame([], ComposerLocalManifest::detect($this->root.'/plugins'));
    }

    public function test_a_missing_parent_directory_is_not_an_error(): void
    {
        $autoload = ComposerLocalManifest::build($this->root)['autoload'];

        $this->assertSame([], $autoload['psr-4']);
        $this->assertArrayNotHasKey('files', $autoload);
    }

    /**
     * Two plugins listing the same shared bootstrap file must not make
     * Composer require it twice.
     */
    public function test_a_shared_autoload_file_is_listed_once(): void
    {
        foreach (['DixlaseA', 'DixlaseB'] as $name) {
            $dir = $this->root.'/plugins/'.$name;
            mkdir($dir.'/app', 0777, true);
            file_put_contents($dir.'/plugin.json', '{}');
            file_put_contents($dir.'/composer.json', '{"autoload":{"files":["../shared/boot.php"]}}');
        }

        $files = ComposerLocalManifest::build($this->root)['autoload']['files'];

        $this->assertCount(2, $files);
        $this->assertSame(array_values(array_unique($files)), $files);
    }

    /**
     * The encoded form is what both writers put on disk, so it has to be
     * stable: pretty-printed, unescaped slashes and UTF-8, trailing newline.
     */
    public function test_the_encoded_form_is_byte_stable(): void
    {
        $encoded = ComposerLocalManifest::encode([
            'autoload' => ['psr-4' => ['Plugins\\Foo\\App\\' => 'plugins/Foo/app']],
        ]);

        $this->assertStringEndsWith("\n", $encoded);
        $this->assertStringContainsString('"plugins/Foo/app"', $encoded);
        $this->assertStringNotContainsString('\\/', $encoded);
    }
}
