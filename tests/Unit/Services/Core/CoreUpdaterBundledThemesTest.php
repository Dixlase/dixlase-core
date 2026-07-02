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
 * Pins CoreUpdater::applyBundledThemes(). The method is only called
 * for a release whose ReleaseManifest declares bundled themes; when it
 * runs it copies each declared themes/<slug>/ from staging over the
 * live tree, retains a pre-apply copy under storage/ so the catch
 * block can restore it on rollback, and refreshes the DB Theme.version
 * so operator-facing metadata lines up on the release's version.
 *
 * Tests use slugs prefixed with `TestBundle_` so they cannot collide
 * with a real theme directory on the developer's machine, and the
 * tearDown removes both the live theme dir and the storage snapshots
 * root even when a test fails.
 */
class CoreUpdaterBundledThemesTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $liveThemeSlugsToCleanup = [];

    private string $stagingRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stagingRoot = sys_get_temp_dir().'/core-updater-bundled-themes-staging-'.uniqid();
        File::ensureDirectoryExists($this->stagingRoot);
    }

    protected function tearDown(): void
    {
        foreach ($this->liveThemeSlugsToCleanup as $slug) {
            $livePath = base_path("themes/{$slug}");
            if (is_dir($livePath)) {
                File::deleteDirectory($livePath);
            }
        }
        if (is_dir($this->stagingRoot)) {
            File::deleteDirectory($this->stagingRoot);
        }
        $snapshotsRoot = storage_path('app/private/core-update/theme-snapshots');
        if (is_dir($snapshotsRoot)) {
            File::deleteDirectory($snapshotsRoot);
        }
        parent::tearDown();
    }

    public function test_copies_staged_theme_dir_over_the_live_tree(): void
    {
        $slug = $this->registerTestSlug();
        $this->stageTheme($slug, '2.0.0', ['hello.txt' => 'from-staging']);

        $this->applyBundledThemes([
            ['slug' => $slug, 'version' => '2.0.0', 'path' => "themes/{$slug}"],
        ]);

        $this->assertFileExists(base_path("themes/{$slug}/hello.txt"));
        $this->assertSame('from-staging', file_get_contents(base_path("themes/{$slug}/hello.txt")));
    }

    public function test_updates_the_theme_db_version_when_the_theme_is_installed(): void
    {
        $slug = $this->registerTestSlug();
        Theme::create([
            'name' => 'Test Bundled Theme',
            'slug' => strtolower($slug),
            'directory' => $slug,
            'package_name' => "dixlase/{$slug}",
            'version' => '1.0.0',
            'has_settings' => false,
        ]);
        $this->stageTheme($slug, '2.0.0');

        $this->applyBundledThemes([
            ['slug' => $slug, 'version' => '2.0.0', 'path' => "themes/{$slug}"],
        ]);

        $this->assertSame('2.0.0', Theme::where('directory', $slug)->value('version'));
    }

    public function test_is_a_noop_on_the_db_when_the_theme_is_not_installed(): void
    {
        // Operator has never activated this theme (no DB row); the
        // update should still land the code, and simply skip the DB
        // update rather than throw. The row is created later by the
        // theme's normal activation flow.
        $slug = $this->registerTestSlug();
        $this->stageTheme($slug, '2.0.0');

        $this->applyBundledThemes([
            ['slug' => $slug, 'version' => '2.0.0', 'path' => "themes/{$slug}"],
        ]);

        $this->assertNull(Theme::where('directory', $slug)->first());
        $this->assertFileExists(base_path("themes/{$slug}/theme.json"));
    }

    public function test_retains_the_pre_apply_copy_of_an_existing_live_theme_for_rollback(): void
    {
        $slug = $this->registerTestSlug();
        $livePath = base_path("themes/{$slug}");
        File::ensureDirectoryExists($livePath);
        file_put_contents($livePath.'/old.txt', 'live-version');
        $this->stageTheme($slug, '2.0.0');

        $snapshots = $this->applyBundledThemes([
            ['slug' => $slug, 'version' => '2.0.0', 'path' => "themes/{$slug}"],
        ]);

        // Live now has the staged copy.
        $this->assertFileExists($livePath.'/theme.json');
        $this->assertFalse(is_file($livePath.'/old.txt'));

        // Snapshot has the pre-apply copy so a rollback can restore it.
        $this->assertArrayHasKey($slug, $snapshots);
        $this->assertFileExists($snapshots[$slug].'/old.txt');
        $this->assertSame('live-version', file_get_contents($snapshots[$slug].'/old.txt'));
    }

    public function test_returns_no_snapshot_when_the_theme_is_a_fresh_install(): void
    {
        // No live directory means there is nothing to snapshot; the
        // rollback path relies on an empty slot to mean "delete the
        // fresh-copied dir on rollback" (verified elsewhere).
        $slug = $this->registerTestSlug();
        $this->stageTheme($slug, '2.0.0');

        $snapshots = $this->applyBundledThemes([
            ['slug' => $slug, 'version' => '2.0.0', 'path' => "themes/{$slug}"],
        ]);

        $this->assertSame([], $snapshots);
        $this->assertFileExists(base_path("themes/{$slug}/theme.json"));
    }

    public function test_throws_when_the_release_declares_a_theme_that_the_staged_payload_does_not_carry(): void
    {
        $slug = $this->registerTestSlug();
        // No stageTheme() call — the payload is empty.

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("bundled theme '{$slug}'");

        $this->applyBundledThemes([
            ['slug' => $slug, 'version' => '2.0.0', 'path' => "themes/{$slug}"],
        ]);
    }

    /**
     * Register a slug for tearDown cleanup and return it. Prefix
     * guarantees the slug can never collide with a real theme
     * directory on the developer's machine.
     */
    private function registerTestSlug(): string
    {
        $slug = 'TestBundle_'.uniqid();
        $this->liveThemeSlugsToCleanup[] = $slug;

        return $slug;
    }

    /**
     * Create a minimal staged themes/<slug>/ directory under the
     * test's staging root, including a theme.json plus any extra
     * files the caller wants to plant.
     *
     * @param  array<string, string>  $extraFiles  filename => contents
     */
    private function stageTheme(string $slug, string $version, array $extraFiles = []): void
    {
        $stagedTheme = $this->stagingRoot."/themes/{$slug}";
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
     * shared staging root, capturing the returned snapshot map.
     *
     * @param  list<array{slug: string, version: string, path: string}>  $themes
     * @return array<string, string> slug => snapshot path
     */
    private function applyBundledThemes(array $themes): array
    {
        $updater = app(CoreUpdater::class);
        $method = (new ReflectionClass(CoreUpdater::class))->getMethod('applyBundledThemes');
        $method->setAccessible(true);

        return $method->invoke($updater, $this->stagingRoot, $themes, fn (string $line) => null);
    }
}
