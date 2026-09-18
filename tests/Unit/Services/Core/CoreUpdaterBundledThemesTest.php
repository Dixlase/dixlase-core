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

namespace Tests\Unit\Services\Core;

use App\Models\Theme;
use App\Services\Core\CoreUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

/**
 * Pins the bootstrap-only contract of CoreUpdater::applyBundledThemes().
 *
 * A release manifest may declare bundled themes, but a core update
 * never changes a theme that is already installed: no file is
 * overwritten and the DB Theme.version stays where it was, even when
 * the bundled copy is newer. Only a theme whose directory is absent is
 * copied into place (fresh install-from-release). Installed themes are
 * moved exclusively through dls:theme:update / dls:theme:rollback.
 *
 * The fake "live" theme tree lives under storage/framework/testing/,
 * never under the real themes/ directory, so a failing test can never
 * touch a tracked theme on the developer's machine. The method resolves
 * the live path from the manifest entry's `path` through base_path(),
 * so the tests pass a repo-relative path into that throwaway tree.
 */
class CoreUpdaterBundledThemesTest extends TestCase
{
    use RefreshDatabase;

    private string $stagingRoot;

    /** Repo-relative throwaway "themes/" root, e.g. storage/framework/testing/bundled-themes-xxx */
    private string $liveRootRelative;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stagingRoot = sys_get_temp_dir().'/core-updater-bundled-themes-staging-'.uniqid();
        File::ensureDirectoryExists($this->stagingRoot);

        $this->liveRootRelative = 'storage/framework/testing/bundled-themes-'.uniqid();
        File::ensureDirectoryExists(base_path($this->liveRootRelative));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->stagingRoot)) {
            File::deleteDirectory($this->stagingRoot);
        }
        if (is_dir(base_path($this->liveRootRelative))) {
            File::deleteDirectory(base_path($this->liveRootRelative));
        }
        parent::tearDown();
    }

    public function test_leaves_an_installed_theme_untouched(): void
    {
        $slug = $this->newSlug();
        $livePath = $this->livePath($slug);
        File::ensureDirectoryExists($livePath);
        file_put_contents($livePath.'/theme.json', json_encode(['version' => '1.0.0', 'directory' => $slug]));
        file_put_contents($livePath.'/custom.txt', 'operator-customized');
        $before = $this->fingerprint($livePath);
        $this->createThemeRow($slug, '1.0.0');
        $this->stageTheme($slug, '2.0.0', ['hello.txt' => 'from-staging']);

        $lines = [];
        $bootstrapped = $this->applyBundledThemes([$this->entry($slug, '2.0.0')], $lines);

        // Files are byte-identical, nothing was added, nothing removed.
        $this->assertSame($before, $this->fingerprint($livePath));
        $this->assertFileDoesNotExist($livePath.'/hello.txt');
        // The DB row still says the operator's version.
        $this->assertSame('1.0.0', Theme::where('directory', $slug)->value('version'));
        // Nothing to undo on rollback.
        $this->assertSame([], $bootstrapped);
        $this->assertStringContainsString("Theme '{$slug}' is already installed; leaving it untouched", implode("\n", $lines));
    }

    public function test_leaves_an_installed_theme_untouched_even_when_it_is_newer_than_the_bundle(): void
    {
        // No version comparison in either direction: the operator
        // decides when a theme moves, so a bundle that is older than
        // the installed copy must not downgrade it either.
        $slug = $this->newSlug();
        $livePath = $this->livePath($slug);
        File::ensureDirectoryExists($livePath);
        file_put_contents($livePath.'/theme.json', json_encode(['version' => '3.0.0', 'directory' => $slug]));
        $before = $this->fingerprint($livePath);
        $this->createThemeRow($slug, '3.0.0');
        $this->stageTheme($slug, '2.0.0');

        $bootstrapped = $this->applyBundledThemes([$this->entry($slug, '2.0.0')]);

        $this->assertSame($before, $this->fingerprint($livePath));
        $this->assertSame('3.0.0', Theme::where('directory', $slug)->value('version'));
        $this->assertSame([], $bootstrapped);
    }

    public function test_bootstraps_a_theme_that_is_not_installed(): void
    {
        $slug = $this->newSlug();
        $this->stageTheme($slug, '2.0.0', ['hello.txt' => 'from-staging']);

        $lines = [];
        $bootstrapped = $this->applyBundledThemes([$this->entry($slug, '2.0.0')], $lines);

        $livePath = $this->livePath($slug);
        $this->assertFileExists($livePath.'/theme.json');
        $this->assertSame('from-staging', file_get_contents($livePath.'/hello.txt'));
        $this->assertSame([$slug], $bootstrapped);
        $this->assertStringContainsString("Bootstrapped themes/{$slug} at v2.0.0.", implode("\n", $lines));
    }

    public function test_bootstrapping_sets_the_db_version_when_a_row_already_exists(): void
    {
        // A stale row (directory removed earlier) would otherwise keep
        // advertising the old version after the code is bootstrapped.
        $slug = $this->newSlug();
        $this->createThemeRow($slug, '1.0.0');
        $this->stageTheme($slug, '2.0.0');

        $this->applyBundledThemes([$this->entry($slug, '2.0.0')]);

        $this->assertSame('2.0.0', Theme::where('directory', $slug)->value('version'));
    }

    public function test_bootstrapping_does_not_create_a_db_row(): void
    {
        // The row is created by the theme's normal activation flow.
        $slug = $this->newSlug();
        $this->stageTheme($slug, '2.0.0');

        $this->applyBundledThemes([$this->entry($slug, '2.0.0')]);

        $this->assertNull(Theme::where('directory', $slug)->first());
        $this->assertFileExists($this->livePath($slug).'/theme.json');
    }

    public function test_handles_a_mixed_manifest_per_theme(): void
    {
        $installed = $this->newSlug();
        $absent = $this->newSlug();
        File::ensureDirectoryExists($this->livePath($installed));
        file_put_contents($this->livePath($installed).'/keep.txt', 'keep');
        $this->stageTheme($installed, '2.0.0');
        $this->stageTheme($absent, '2.0.0');

        $bootstrapped = $this->applyBundledThemes([
            $this->entry($installed, '2.0.0'),
            $this->entry($absent, '2.0.0'),
        ]);

        $this->assertSame([$absent], $bootstrapped);
        $this->assertFileDoesNotExist($this->livePath($installed).'/theme.json');
        $this->assertFileExists($this->livePath($absent).'/theme.json');
    }

    public function test_throws_when_the_release_declares_a_theme_that_the_staged_payload_does_not_carry(): void
    {
        $slug = $this->newSlug();
        // No stageTheme() call — the payload is empty.

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("bundled theme '{$slug}'");

        $this->applyBundledThemes([$this->entry($slug, '2.0.0')]);
    }

    private function newSlug(): string
    {
        return 'TestBundle_'.uniqid();
    }

    private function livePath(string $slug): string
    {
        return base_path($this->liveRootRelative."/themes/{$slug}");
    }

    /**
     * @return array{slug: string, version: string, path: string}
     */
    private function entry(string $slug, string $version): array
    {
        return [
            'slug' => $slug,
            'version' => $version,
            'path' => $this->liveRootRelative."/themes/{$slug}",
        ];
    }

    private function createThemeRow(string $slug, string $version): void
    {
        Theme::create([
            'name' => 'Test Bundled Theme',
            'slug' => strtolower($slug),
            'directory' => $slug,
            'package_name' => "dixlase/{$slug}",
            'version' => $version,
            'has_settings' => false,
        ]);
    }

    /**
     * Relative path => sha1 of contents for every file under $dir.
     *
     * @return array<string, string>
     */
    private function fingerprint(string $dir): array
    {
        $out = [];
        foreach (File::allFiles($dir) as $file) {
            $out[$file->getRelativePathname()] = sha1_file($file->getPathname());
        }
        ksort($out);

        return $out;
    }

    /**
     * Create a minimal staged <path>/ directory under the test's
     * staging root, including a theme.json plus any extra files.
     *
     * @param  array<string, string>  $extraFiles  filename => contents
     */
    private function stageTheme(string $slug, string $version, array $extraFiles = []): void
    {
        $stagedTheme = $this->stagingRoot.'/'.$this->entry($slug, $version)['path'];
        File::ensureDirectoryExists($stagedTheme);
        file_put_contents(
            $stagedTheme.'/theme.json',
            json_encode(['version' => $version, 'directory' => $slug]),
        );
        foreach ($extraFiles as $name => $contents) {
            file_put_contents($stagedTheme.'/'.$name, $contents);
        }
    }

    /**
     * Reflectively invoke CoreUpdater::applyBundledThemes with the
     * shared staging root, collecting log lines into $lines.
     *
     * @param  list<array{slug: string, version: string, path: string}>  $themes
     * @param  list<string>  $lines
     * @return list<string> slugs bootstrapped by the call
     */
    private function applyBundledThemes(array $themes, array &$lines = []): array
    {
        $updater = app(CoreUpdater::class);
        $method = (new ReflectionClass(CoreUpdater::class))->getMethod('applyBundledThemes');
        $method->setAccessible(true);

        return $method->invoke($updater, $this->stagingRoot, $themes, function (string $line) use (&$lines): void {
            $lines[] = $line;
        });
    }
}
