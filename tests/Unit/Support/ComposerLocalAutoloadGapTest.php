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

namespace Tests\Unit\Support;

use App\Support\ComposerLocalManifest;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * composer-merge-plugin reads composer.local.json when Composer initialises,
 * before core's pre-autoload-dump hook writes it. A tree without the file —
 * a release ZIP, which ships one only since v0.3.56 — therefore dumps an
 * autoloader with no extension PSR-4 roots, and nothing dumps again: the
 * bundled theme's ServiceProvider is unresolvable and the front page is a
 * 500. scripts/verify-local-autoload.php detects that and dumps once more.
 */
class ComposerLocalAutoloadGapTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/autoload-gap-'.uniqid());
        File::ensureDirectoryExists($this->dir.'/vendor/composer');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    private function writeLocalManifest(string ...$prefixes): void
    {
        $psr4 = [];
        foreach ($prefixes as $prefix) {
            $psr4[$prefix] = 'themes/Example/app';
        }

        File::put($this->dir.'/composer.local.json', json_encode(['autoload' => ['psr-4' => $psr4]]));
    }

    private function writeDumpedMap(string ...$prefixes): void
    {
        $lines = array_map(
            static fn (string $p): string => '    '.var_export($p, true).' => array($baseDir),',
            $prefixes
        );

        File::put(
            $this->dir.'/vendor/composer/autoload_psr4.php',
            "<?php\n\$baseDir = '/tmp';\n\nreturn array(\n".implode("\n", $lines)."\n);\n"
        );
    }

    public function test_it_reports_the_roots_the_dump_left_out(): void
    {
        $this->writeLocalManifest('Themes\\Example\\App\\', 'Plugins\\Example\\App\\');
        $this->writeDumpedMap('Plugins\\Example\\App\\');

        $this->assertSame(
            ['Themes\\Example\\App\\'],
            ComposerLocalManifest::missingPsr4Roots($this->dir)
        );
    }

    public function test_a_complete_dump_reports_nothing(): void
    {
        $this->writeLocalManifest('Themes\\Example\\App\\');
        $this->writeDumpedMap('Themes\\Example\\App\\', 'Illuminate\\Support\\');

        $this->assertSame([], ComposerLocalManifest::missingPsr4Roots($this->dir));
    }

    public function test_it_is_silent_when_there_is_nothing_to_compare(): void
    {
        // No composer.local.json: a core-only tree, nothing is expected.
        $this->writeDumpedMap('Illuminate\\Support\\');
        $this->assertSame([], ComposerLocalManifest::missingPsr4Roots($this->dir));

        // A manifest but no dump yet: composer has not run, so this is not
        // the situation the check exists for.
        File::delete($this->dir.'/vendor/composer/autoload_psr4.php');
        $this->writeLocalManifest('Themes\\Example\\App\\');
        $this->assertSame([], ComposerLocalManifest::missingPsr4Roots($this->dir));
    }

    public function test_the_verify_script_is_wired_into_composer(): void
    {
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

        $this->assertContains(
            '@php scripts/verify-local-autoload.php',
            $composer['scripts']['post-autoload-dump'] ?? [],
            'The check must run after every dump, or a ZIP install keeps its incomplete map.'
        );
        $this->assertFileExists(base_path('scripts/verify-local-autoload.php'));
    }

    public function test_the_release_zip_ships_composer_local_json(): void
    {
        $excluded = (string) file_get_contents(base_path('.github/release-dist-exclude.txt'));

        $patterns = array_filter(
            array_map('trim', explode("\n", $excluded)),
            static fn (string $line): bool => $line !== '' && ! str_starts_with($line, '#')
        );

        $this->assertNotContains(
            'composer.local.json',
            $patterns,
            'Excluding it makes the first dump of a ZIP install drop every extension PSR-4 root.'
        );
    }
}
