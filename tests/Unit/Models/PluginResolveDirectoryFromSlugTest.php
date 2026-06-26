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

namespace Tests\Unit\Models;

use App\Models\Plugin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pins Plugin::resolveDirectoryFromSlug against the acronym-loss regression.
 *
 * Many call sites historically piped the slug through
 * `Str::studly(str_replace('-', '_', $slug))` to reconstruct the on-disk
 * directory name. That transform silently mangles plugins whose canonical
 * name contains an uppercase acronym — e.g. `dixlase-seo` becomes
 * `DixlaseSeo` instead of the actual `DixlaseSEO`. The result is either a
 * missing directory or, on case-insensitive filesystems, a wrong-case path
 * that PHP iterators (`RecursiveDirectoryIterator::__construct()`) refuse
 * to open with `Failed to open directory: No such file or directory`.
 *
 * The model helper canonicalises lookups through the on-disk index and
 * the plugins table.
 */
class PluginResolveDirectoryFromSlugTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A directory name that no real plugin will ever shadow. We create
     * the fixture under plugins/ for each test and remove it in
     * tearDown so the helper can scan via its production base_path().
     */
    private const ACRONYM_FIXTURE = 'TestPluginAPI';

    private const STUDLY_FIXTURE = 'TestSimpleName';

    protected function tearDown(): void
    {
        foreach ([self::ACRONYM_FIXTURE, self::STUDLY_FIXTURE] as $name) {
            $dir = base_path('plugins/'.$name);
            if (is_dir($dir)) {
                @rmdir($dir);
            }
        }

        parent::tearDown();
    }

    public function test_resolves_acronym_bearing_directory_from_db_directory_column(): void
    {
        $this->createFixtureDirectory(self::ACRONYM_FIXTURE);

        Plugin::create([
            'name' => 'Test Plugin API',
            'directory' => self::ACRONYM_FIXTURE,
            'slug' => 'test-plugin-api',
            'namespace' => 'Plugins\\TestPluginAPI',
            'version' => '0.0.1',
        ]);

        $this->assertSame(
            self::ACRONYM_FIXTURE,
            Plugin::resolveDirectoryFromSlug('test-plugin-api'),
            'DB-stored directory must win and round-trip the acronym',
        );
    }

    public function test_falls_back_to_normalized_match_when_db_row_missing(): void
    {
        $this->createFixtureDirectory(self::ACRONYM_FIXTURE);

        // No Plugin row exists for this slug — the helper should still
        // recover the directory by stripping dashes and matching
        // case-insensitively against the actual filesystem entry.
        $this->assertSame(
            self::ACRONYM_FIXTURE,
            Plugin::resolveDirectoryFromSlug('test-plugin-api'),
            'normalized fallback must find the on-disk acronym directory without a DB row',
        );
    }

    public function test_studly_fast_path_resolves_no_acronym_slugs(): void
    {
        $this->createFixtureDirectory(self::STUDLY_FIXTURE);

        $this->assertSame(
            self::STUDLY_FIXTURE,
            Plugin::resolveDirectoryFromSlug('test-simple-name'),
            'studly heuristic must keep working for plugins without acronyms',
        );
    }

    public function test_returns_null_when_no_directory_matches(): void
    {
        $this->assertNull(
            Plugin::resolveDirectoryFromSlug('never-installed-plugin-xyz-'.uniqid()),
            'unknown slug must yield null, not a fabricated path',
        );
    }

    public function test_empty_slug_returns_null(): void
    {
        $this->assertNull(
            Plugin::resolveDirectoryFromSlug(''),
            'empty input must short-circuit to null without scanning the filesystem',
        );
    }

    private function createFixtureDirectory(string $name): void
    {
        $path = base_path('plugins/'.$name);
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}
