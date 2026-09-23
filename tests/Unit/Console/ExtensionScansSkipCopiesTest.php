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

namespace Tests\Unit\Console;

use App\Console\Commands\PluginAudit;
use App\Console\Commands\PluginLint;
use App\Console\Commands\SyncGitExclude;
use App\Console\Commands\SyncGitIgnore;
use App\Console\Commands\ThemeAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Commands that scan `plugins/` and `themes/` must ignore move-aside copies.
 *
 * A copy left by an update (`Foo.stale.<timestamp>`, `Foo.bak`) is complete —
 * manifest, `app/`, `database/migrations/` — so a scan keyed on structure
 * alone accepts it. What each command then does with it differs, and none of
 * it is harmless:
 *
 *   - `dls:sync:git-exclude` / `dls:sync:gitignore` propose a negation entry,
 *     which would make Core's git track the copy's whole tree.
 *   - `dls:plugin:audit --fix` / `dls:theme:audit --fix` resolve the copy by
 *     its manifest slug once the live directory is gone, then write into the
 *     abandoned directory while reporting success.
 *   - `dls:plugin:lint --all --fix` writes to the copy and scores it; below
 *     the threshold the command exits non-zero, failing a lint gate.
 *
 * These exercise the real scanning methods against a throwaway tree rather
 * than the repository's own plugins/ and themes/.
 */
class ExtensionScansSkipCopiesTest extends TestCase
{
    // The audit commands look the extension up in the database before they
    // fall back to scanning the directory, so the schema has to exist for the
    // scan to be reached at all. phpunit.xml pins the connection to an
    // in-memory SQLite database.
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = base_path('storage/framework/testing/ext-scan-'.uniqid('', true));
        mkdir($this->root.'/plugins', 0777, true);
        mkdir($this->root.'/themes', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);

        parent::tearDown();
    }

    private function makeExtension(string $parent, string $name, string $manifest, string $slug): void
    {
        $dir = $this->root.'/'.$parent.'/'.$name;

        mkdir($dir.'/app', 0777, true);
        mkdir($dir.'/database/migrations', 0777, true);
        file_put_contents($dir.'/'.$manifest, json_encode(['slug' => $slug]));
        file_put_contents(
            $dir.'/database/migrations/0001_01_01_000001_create_something.php',
            "<?php\n"
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

    /**
     * @param  array<int, mixed>  $args
     */
    private function invokeProtected(object $command, string $method, array $args): mixed
    {
        $reflection = new ReflectionMethod($command, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($command, $args);
    }

    /**
     * @return list<array{0: class-string}>
     */
    public static function syncCommandProvider(): array
    {
        return [[SyncGitExclude::class], [SyncGitIgnore::class]];
    }

    /**
     * @param  class-string  $commandClass
     */
    #[DataProvider('syncCommandProvider')]
    public function test_the_git_sync_commands_skip_copies(string $commandClass): void
    {
        $this->makeExtension('plugins', 'DixlaseSEO', 'plugin.json', 'dixlase-seo');
        $this->makeExtension('plugins', 'DixlaseSEO.stale.20260710-221107', 'plugin.json', 'dixlase-seo');
        $this->makeExtension('plugins', 'DixlaseOld.bak', 'plugin.json', 'dixlase-old');

        $names = $this->invokeProtected(app($commandClass), 'detectDirectories', [$this->root.'/plugins', []]);

        $this->assertSame(['DixlaseSEO'], $names);
    }

    public function test_the_git_sync_commands_still_honour_the_explicit_exclude(): void
    {
        $this->makeExtension('themes', 'DixlaseOnePage', 'theme.json', 'dixlase-one-page');
        $this->makeExtension('themes', 'OtherTheme', 'theme.json', 'other-theme');

        $names = $this->invokeProtected(
            app(SyncGitExclude::class),
            'detectDirectories',
            [$this->root.'/themes', ['DixlaseOnePage']]
        );

        // DixlaseOnePage is installed by composer/installers and gitignored
        // outright, so it is excluded for a different reason than a copy.
        $this->assertSame(['OtherTheme'], $names);
    }

    public function test_plugin_lint_does_not_collect_copies(): void
    {
        $this->makeExtension('plugins', 'DixlaseSEO', 'plugin.json', 'dixlase-seo');
        $this->makeExtension('plugins', 'DixlaseSEO.stale.20260710-221107', 'plugin.json', 'dixlase-seo');

        $this->app->setBasePath($this->root);

        $names = $this->invokeProtected(app(PluginLint::class), 'collectAllPlugins', []);

        $this->assertSame(['DixlaseSEO'], $names);
    }

    /**
     * The dangerous shape: the live directory is gone and only the copy is
     * left, which is exactly the "renamed instead of deleted" case.
     */
    public function test_plugin_audit_does_not_resolve_a_copy_by_slug(): void
    {
        $this->makeExtension('plugins', 'DixlaseSEO.stale.20260710-221107', 'plugin.json', 'dixlase-seo');

        $this->app->setBasePath($this->root);

        $resolved = $this->invokeProtected(app(PluginAudit::class), 'resolvePluginDirectory', ['dixlase-seo']);

        $this->assertNull($resolved);
    }

    public function test_theme_audit_does_not_resolve_a_copy_by_slug(): void
    {
        $this->makeExtension('themes', 'DixlaseOnePage.stale.20260817-211837', 'theme.json', 'dixlase-one-page');

        $this->app->setBasePath($this->root);

        $resolved = $this->invokeProtected(app(ThemeAudit::class), 'resolveThemeDirectory', ['dixlase-one-page']);

        $this->assertNull($resolved);
    }

    public function test_the_audit_commands_still_resolve_a_live_extension(): void
    {
        $this->makeExtension('plugins', 'DixlaseSEO', 'plugin.json', 'dixlase-seo');
        $this->makeExtension('plugins', 'DixlaseSEO.stale.20260710-221107', 'plugin.json', 'dixlase-seo');

        $this->app->setBasePath($this->root);

        $resolved = $this->invokeProtected(app(PluginAudit::class), 'resolvePluginDirectory', ['dixlase-seo']);

        $this->assertNotNull($resolved);
        $this->assertStringEndsWith('/DixlaseSEO', (string) $resolved);
    }
}
