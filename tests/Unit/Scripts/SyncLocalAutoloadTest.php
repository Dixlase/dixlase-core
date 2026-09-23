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

namespace Tests\Unit\Scripts;

use PHPUnit\Framework\TestCase;

/**
 * `scripts/sync-local-autoload.php` must not register a move-aside copy of
 * an extension.
 *
 * Why this is worth a test of its own: each registered extension's
 * `autoload.files` is merged into `composer.local.json`, and Composer
 * `require`s those paths unconditionally at bootstrap. A copy that was
 * registered and then deleted therefore takes down every request and every
 * artisan command with "Failed opening required ..." — a white screen, not a
 * missing helper. Brand hit this on 2026-09-23 when a deploy change pruned
 * old `themes/*.stale.*` directories, and stayed up only because OPcache
 * still held the compiled autoload file.
 *
 * The script runs before the framework autoloader exists, so it is exercised
 * the way Composer runs it: as a subprocess, against a throwaway tree that
 * the script sees as its own Core root (it derives that from its own
 * location, `dirname(__DIR__)`).
 */
class SyncLocalAutoloadTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = \dirname(__DIR__, 3).'/storage/framework/testing/sync-autoload-'.uniqid('', true);

        mkdir($this->root.'/scripts', 0777, true);
        mkdir($this->root.'/app/Support', 0777, true);

        copy(
            \dirname(__DIR__, 3).'/scripts/sync-local-autoload.php',
            $this->root.'/scripts/sync-local-autoload.php'
        );
        copy(
            \dirname(__DIR__, 3).'/app/Support/ExtensionDirectories.php',
            $this->root.'/app/Support/ExtensionDirectories.php'
        );
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);

        parent::tearDown();
    }

    /**
     * Create an extension directory complete enough to be mistaken for an
     * installed one: manifest, `app/`, and a `composer.json` declaring a
     * helper under `autoload.files`.
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

    /**
     * @return array{psr4: array<string, string>, files: list<string>}
     */
    private function runScript(): array
    {
        exec(
            escapeshellarg(PHP_BINARY).' '.escapeshellarg($this->root.'/scripts/sync-local-autoload.php').' 2>&1',
            $output,
            $exitCode
        );

        $this->assertSame(0, $exitCode, "script failed:\n".implode("\n", $output));

        $generated = $this->root.'/composer.local.json';
        $this->assertFileExists($generated);

        $decoded = json_decode((string) file_get_contents($generated), true);
        $this->assertIsArray($decoded);

        return [
            'psr4' => $decoded['autoload']['psr-4'] ?? [],
            'files' => $decoded['autoload']['files'] ?? [],
        ];
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

    public function test_an_installed_extension_is_registered(): void
    {
        $this->makeExtension('plugins', 'DixlaseSEO', 'plugin.json');
        $this->makeExtension('themes', 'DixlaseOnePage', 'theme.json');

        $result = $this->runScript();

        $this->assertArrayHasKey('Plugins\\DixlaseSEO\\App\\', $result['psr4']);
        $this->assertArrayHasKey('Themes\\DixlaseOnePage\\App\\', $result['psr4']);
        $this->assertContains('plugins/DixlaseSEO/app/Helpers/Helpers.php', $result['files']);
        $this->assertContains('themes/DixlaseOnePage/app/Helpers/Helpers.php', $result['files']);
    }

    /**
     * The regression: a deploy backup is a complete copy, so it passes every
     * structural check. Only its name gives it away.
     */
    public function test_a_move_aside_copy_is_not_registered(): void
    {
        $this->makeExtension('plugins', 'DixlaseDeploy', 'plugin.json');
        $this->makeExtension('plugins', 'DixlaseDeploy.stale.20260710-221107', 'plugin.json');
        $this->makeExtension('themes', 'DixlaseOnePage', 'theme.json');
        $this->makeExtension('themes', 'DixlaseOnePage.stale.20260817-211837', 'theme.json');

        $result = $this->runScript();

        foreach (array_keys($result['psr4']) as $namespace) {
            $this->assertStringNotContainsString('.stale.', $namespace);
        }

        foreach ($result['files'] as $file) {
            $this->assertStringNotContainsString('.stale.', $file);
        }

        // The live extensions are still registered.
        $this->assertArrayHasKey('Plugins\\DixlaseDeploy\\App\\', $result['psr4']);
        $this->assertArrayHasKey('Themes\\DixlaseOnePage\\App\\', $result['psr4']);
        $this->assertCount(2, $result['files']);
    }

    /**
     * `Foo.bak`, `_disabled`, `.hidden` — the same rule covers every naming
     * habit that produces a set-aside copy, without enumerating them.
     */
    public function test_other_set_aside_names_are_not_registered(): void
    {
        $this->makeExtension('plugins', 'DixlaseSEO', 'plugin.json');
        $this->makeExtension('plugins', 'DixlaseSEO.bak', 'plugin.json');
        $this->makeExtension('plugins', '_DixlaseOld', 'plugin.json');
        $this->makeExtension('plugins', '.DixlaseHidden', 'plugin.json');

        $result = $this->runScript();

        $appNamespaces = array_values(array_filter(
            array_keys($result['psr4']),
            fn (string $ns): bool => str_ends_with($ns, 'App\\')
        ));
        sort($appNamespaces);

        $this->assertSame(
            ['Custom\\Plugins\\DixlaseSEO\\App\\', 'Plugins\\DixlaseSEO\\App\\'],
            $appNamespaces
        );
        $this->assertSame(['plugins/DixlaseSEO/app/Helpers/Helpers.php'], $result['files']);
    }

    /**
     * The script is run by Composer before the framework autoloader exists,
     * so it requires App\Support\ExtensionDirectories by path. If that file
     * is not there, the inline fallback has to enforce the same rule rather
     * than let the hook fail or silently register the copies again.
     */
    public function test_the_rule_still_holds_without_the_shared_class(): void
    {
        unlink($this->root.'/app/Support/ExtensionDirectories.php');

        $this->makeExtension('plugins', 'DixlaseSEO', 'plugin.json');
        $this->makeExtension('plugins', 'DixlaseSEO.stale.20260710-221107', 'plugin.json');

        $result = $this->runScript();

        $this->assertArrayHasKey('Plugins\\DixlaseSEO\\App\\', $result['psr4']);
        $this->assertSame(['plugins/DixlaseSEO/app/Helpers/Helpers.php'], $result['files']);
    }

    /**
     * A directory that merely happens to contain `app/` is not an extension.
     * An installed one always declares itself with a manifest.
     */
    public function test_a_directory_without_a_manifest_is_not_registered(): void
    {
        $this->makeExtension('plugins', 'DixlaseSEO', 'plugin.json');

        mkdir($this->root.'/plugins/scratch/app', 0777, true);
        file_put_contents(
            $this->root.'/plugins/scratch/composer.json',
            '{"autoload":{"files":["app/Boot.php"]}}'
        );
        file_put_contents($this->root.'/plugins/scratch/app/Boot.php', "<?php\n");

        $result = $this->runScript();

        $this->assertArrayNotHasKey('Plugins\\scratch\\App\\', $result['psr4']);
        $this->assertSame(['plugins/DixlaseSEO/app/Helpers/Helpers.php'], $result['files']);
    }
}
