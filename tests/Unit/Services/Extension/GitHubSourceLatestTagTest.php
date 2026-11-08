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

namespace Tests\Unit\Services\Extension;

use App\Models\ExtensionSource;
use App\Services\Extension\GitHubSourceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The add screen advertises, and installs, the newest tag.
 *
 * It used to read `plugin.json` from the default branch while the
 * download took the latest release, so the badge named a `-dev` version
 * one step ahead of what was installed; and for a repository with no
 * published release the download took the branch itself, which moves.
 * See dixlase-core#958.
 */
class GitHubSourceLatestTagTest extends TestCase
{
    use RefreshDatabase;

    private ExtensionSource $source;

    private GitHubSourceProvider $provider;

    private string $downloadDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = ExtensionSource::query()->create([
            'name' => 'Test GitHub',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'TestOrg',
            'auth_token' => 'ghp_test_token',
            'priority' => 0,
        ]);

        // The listing is cached for 15 minutes in production; a test
        // asserts what the provider asks GitHub, so it must not be
        // answered from a previous test's cache.
        config(['extension-sources.github.list_cache_ttl' => 0]);

        // Never the real download directory.
        $this->downloadDir = storage_path('framework/testing/ext-dl-'.uniqid());
        config(['extension-sources.download_path' => $this->downloadDir]);

        $this->provider = new GitHubSourceProvider($this->source);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->downloadDir)) {
            foreach (glob($this->downloadDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->downloadDir);
        }

        parent::tearDown();
    }

    /**
     * @param  array<int, string>  $tags
     */
    private function fakeRepoWithTags(array $tags, string $manifestVersion = '1.2.0'): void
    {
        Http::fake([
            'api.github.com/orgs/TestOrg/repos*' => Http::sequence()
                ->push([['name' => 'plugin-foo', 'default_branch' => 'next', 'description' => 'Foo']])
                ->push([]),
            'api.github.com/repos/TestOrg/plugin-foo/tags*' => Http::response(
                array_map(fn (string $t) => ['name' => $t], $tags)
            ),
            'api.github.com/repos/TestOrg/plugin-foo/contents/plugin.json*' => Http::response([
                'encoding' => 'base64',
                'content' => base64_encode((string) json_encode([
                    'name' => 'Foo',
                    'version' => $manifestVersion,
                    'author' => 'exc-D inc.',
                    'license' => 'GPL-3.0-or-later',
                ])),
            ]),
            // Http::fake() lets an unmatched URL through to the real
            // network, so anything not named above must fail loudly.
            '*' => Http::response(['message' => 'unexpected request'], 500),
        ]);
    }

    #[Test]
    public function the_listing_reads_the_manifest_at_the_newest_tag(): void
    {
        $this->fakeRepoWithTags(['v1.2.0', 'v1.1.0'], manifestVersion: '1.2.0');

        $plugins = $this->provider->listPlugins();

        $this->assertCount(1, $plugins);
        $this->assertSame('1.2.0', $plugins[0]['version']);
        $this->assertSame('v1.2.0', $plugins[0]['ref']);
        $this->assertSame('tag', $plugins[0]['ref_type']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/contents/plugin.json')
            && str_contains($request->url(), 'ref=v1.2.0'));
    }

    #[Test]
    public function the_newest_tag_is_the_highest_version_not_the_first_listed(): void
    {
        // GitHub does not document the order of /tags, and the order it
        // happens to return is not version order.
        $this->fakeRepoWithTags(['v0.9.0', 'v0.10.0', 'v0.2.0']);

        $this->provider->listPlugins();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/contents/plugin.json')
            && str_contains($request->url(), 'ref=v0.10.0'));
    }

    #[Test]
    public function a_tag_that_is_not_a_version_is_not_treated_as_one(): void
    {
        // A name like this can be moved to another commit, which is
        // exactly what advertising a fixed ref is meant to avoid.
        $this->fakeRepoWithTags(['latest', 'stable']);

        $plugins = $this->provider->listPlugins();

        $this->assertSame('branch', $plugins[0]['ref_type']);
        $this->assertSame('next', $plugins[0]['ref']);
    }

    #[Test]
    public function a_repository_with_no_tag_is_listed_and_marked_as_a_branch(): void
    {
        $this->fakeRepoWithTags([], manifestVersion: '1.3.0-dev');

        $plugins = $this->provider->listPlugins();

        $this->assertCount(1, $plugins, 'An untagged extension is still offered.');
        $this->assertSame('1.3.0-dev', $plugins[0]['version']);
        $this->assertSame('branch', $plugins[0]['ref_type']);
        $this->assertSame('next', $plugins[0]['ref']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/contents/plugin.json')
            && ! str_contains($request->url(), 'ref='));
    }

    #[Test]
    public function a_tag_only_repository_resolves_to_its_tag_rather_than_its_branch(): void
    {
        Http::fake([
            'api.github.com/repos/TestOrg/plugin-foo/releases/latest' => Http::response(['message' => 'Not Found'], 404),
            'api.github.com/repos/TestOrg/plugin-foo/tags*' => Http::response([['name' => 'v0.1.3']]),
            '*' => Http::response(['message' => 'unexpected request'], 500),
        ]);

        $release = $this->provider->getLatestRelease('foo', 'plugin');

        $this->assertNotNull($release);
        $this->assertSame('0.1.3', $release->version);
        $this->assertSame('tag', $release->metadata['source'] ?? null);
        $this->assertSame('v0.1.3', $release->metadata['tag'] ?? null);
        $this->assertStringContainsString('/zipball/v0.1.3', (string) $release->downloadUrl);
    }

    #[Test]
    public function a_repository_with_neither_release_nor_tag_still_falls_back_to_the_branch(): void
    {
        Http::fake([
            'api.github.com/repos/TestOrg/plugin-foo/releases/latest' => Http::response(['message' => 'Not Found'], 404),
            'api.github.com/repos/TestOrg/plugin-foo/tags*' => Http::response([]),
            'api.github.com/repos/TestOrg/plugin-foo/contents/plugin.json*' => Http::response([
                'encoding' => 'base64',
                'content' => base64_encode((string) json_encode(['version' => '0.1.0-dev'])),
            ]),
            'api.github.com/repos/TestOrg/plugin-foo' => Http::response(['default_branch' => 'next']),
            '*' => Http::response(['message' => 'unexpected request'], 500),
        ]);

        $release = $this->provider->getLatestRelease('foo', 'plugin');

        $this->assertNotNull($release);
        $this->assertSame('default_branch', $release->metadata['source'] ?? null);
    }

    #[Test]
    public function the_download_takes_the_tag_when_there_is_no_release_for_it(): void
    {
        Http::fake([
            'api.github.com/repos/TestOrg/plugin-foo/releases/tags/*' => Http::response(['message' => 'Not Found'], 404),
            'api.github.com/repos/TestOrg/plugin-foo/tags*' => Http::response([['name' => 'v0.1.3']]),
            'api.github.com/repos/TestOrg/plugin-foo/zipball/v0.1.3' => Http::response('PK-zip-bytes'),
            '*' => Http::response(['message' => 'unexpected request'], 500),
        ]);

        $path = $this->provider->downloadRelease('foo', '0.1.3', 'plugin');

        $this->assertFileExists($path);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/zipball/v0.1.3'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/zipball/next'));
    }
}
