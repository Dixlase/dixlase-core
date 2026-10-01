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

namespace Tests\Unit\Services\Extension;

use App\Exceptions\ExtensionSourceRateLimitException;
use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\GitHubSourceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * A site without a GitHub token must not spend the anonymous API limit
 * (60 calls an hour per IP) on files and release assets.
 *
 * Installing the five official plugins used to take 60-70 API calls, so the
 * third download already failed with HTTP 403. Without a token, manifests and
 * thumbnails now come from raw.githubusercontent.com and assets from their
 * browser_download_url; only the repository listing and the release lookup
 * still use the API. With a token nothing changes (private repositories need
 * the API).
 */
class GitHubAnonymousSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('extension-sources.github.default_token', null);
        config()->set('extension-sources.download_path', storage_path('framework/testing/extension-downloads-'.uniqid()));
    }

    private function source(?string $token = null): ExtensionSource
    {
        return ExtensionSource::query()->create([
            'name' => 'GitHub',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'Dixlase',
            'auth_token' => $token,
            'priority' => 0,
            'is_enabled' => true,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function apiPaths(): array
    {
        return Http::recorded()
            ->map(fn (array $pair) => $pair[0])
            ->filter(fn (Request $request) => str_contains($request->url(), 'api.github.com'))
            ->map(fn (Request $request) => (string) parse_url($request->url(), PHP_URL_PATH))
            ->values()
            ->all();
    }

    private function fakeRepos(): void
    {
        Http::fake([
            'api.github.com/orgs/Dixlase/repos*' => Http::response([
                ['name' => 'plugin-dixlase-pages', 'default_branch' => 'main', 'description' => 'Pages'],
                ['name' => 'plugin-dixlase-seo', 'default_branch' => 'main', 'description' => 'SEO'],
            ]),
            'raw.githubusercontent.com/Dixlase/plugin-dixlase-pages/HEAD/plugin.json' => Http::response(json_encode(['name' => 'Pages', 'version' => '0.1.0'])),
            'raw.githubusercontent.com/Dixlase/plugin-dixlase-seo/HEAD/plugin.json' => Http::response(json_encode(['name' => 'SEO', 'version' => '0.1.0'])),
            'api.github.com/*' => Http::response(['message' => 'unexpected API call'], 500),
        ]);
    }

    public function test_listing_reads_manifests_from_raw_without_a_token(): void
    {
        $this->fakeRepos();

        $plugins = (new GitHubSourceProvider($this->source()))->listPlugins();

        $this->assertSame(['Pages', 'SEO'], array_column($plugins, 'name'));
        $this->assertSame(['/orgs/Dixlase/repos'], $this->apiPaths());
    }

    public function test_listing_is_cached_between_visits(): void
    {
        $this->fakeRepos();
        $provider = new GitHubSourceProvider($this->source());

        $provider->listPlugins();
        $provider->listPlugins();

        $this->assertCount(1, $this->apiPaths());
    }

    public function test_thumbnail_is_probed_on_raw_without_a_token(): void
    {
        Http::fake([
            'raw.githubusercontent.com/Dixlase/plugin-dixlase-pages/HEAD/thumbnail.png' => Http::response('PNGBYTES'),
            'raw.githubusercontent.com/*' => Http::response('', 404),
            'api.github.com/*' => Http::response(['message' => 'unexpected API call'], 500),
        ]);

        $thumbnail = (new GitHubSourceProvider($this->source()))->fetchThumbnail('dixlase-pages');

        $this->assertSame(['content' => 'PNGBYTES', 'mime' => 'image/png'], $thumbnail);
        $this->assertSame([], $this->apiPaths());
    }

    public function test_download_uses_the_browser_url_without_a_token(): void
    {
        Http::fake([
            'api.github.com/repos/Dixlase/plugin-dixlase-pages/releases/tags/v0.1.0' => Http::response([
                'tag_name' => 'v0.1.0',
                'assets' => [[
                    'name' => 'plugin-dixlase-pages-v0.1.0.zip',
                    'url' => 'https://api.github.com/repos/Dixlase/plugin-dixlase-pages/releases/assets/1',
                    'browser_download_url' => 'https://github.com/Dixlase/plugin-dixlase-pages/releases/download/v0.1.0/plugin-dixlase-pages-v0.1.0.zip',
                ]],
            ]),
            'github.com/Dixlase/plugin-dixlase-pages/releases/download/*' => Http::response('ZIP'),
            'api.github.com/*' => Http::response(['message' => 'unexpected API call'], 500),
        ]);

        $path = (new GitHubSourceProvider($this->source()))->downloadRelease('dixlase-pages', '0.1.0');

        $this->assertFileExists($path);
        $this->assertSame(['/repos/Dixlase/plugin-dixlase-pages/releases/tags/v0.1.0'], $this->apiPaths());
    }

    public function test_a_token_keeps_the_api_paths(): void
    {
        Http::fake([
            'api.github.com/repos/Dixlase/plugin-dixlase-pages/releases/tags/v0.1.0' => Http::response([
                'tag_name' => 'v0.1.0',
                'assets' => [[
                    'name' => 'plugin-dixlase-pages-v0.1.0.zip',
                    'url' => 'https://api.github.com/repos/Dixlase/plugin-dixlase-pages/releases/assets/1',
                    'browser_download_url' => 'https://github.com/Dixlase/plugin-dixlase-pages/releases/download/v0.1.0/plugin-dixlase-pages-v0.1.0.zip',
                ]],
            ]),
            'api.github.com/repos/Dixlase/plugin-dixlase-pages/releases/assets/1' => Http::response('ZIP'),
            'api.github.com/repos/Dixlase/plugin-dixlase-pages/contents/plugin.json' => Http::response([
                'content' => base64_encode(json_encode(['name' => 'Pages'])),
                'encoding' => 'base64',
            ]),
            '*' => Http::response('', 500),
        ]);

        $provider = new GitHubSourceProvider($this->source('ghp_test_token'));
        $provider->downloadRelease('dixlase-pages', '0.1.0');
        $provider->getExtensionDetails('dixlase-pages');

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'raw.githubusercontent.com')
            || str_contains($request->url(), 'github.com/Dixlase/plugin-dixlase-pages/releases/download/'));
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.github.com/repos/Dixlase/plugin-dixlase-pages/releases/assets/1');
    }

    public function test_an_exhausted_limit_names_the_reset_time_on_download(): void
    {
        config()->set('app.timezone', 'UTC');
        Http::fake([
            'api.github.com/*' => Http::response(['message' => 'API rate limit exceeded'], 403, [
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) gmmktime(7, 45, 0, 10, 1, 2026),
            ]),
        ]);

        try {
            (new GitHubSourceProvider($this->source()))->downloadRelease('dixlase-seo', '0.1.0');
            $this->fail('A rate-limited download must not fall through to a generic error.');
        } catch (ExtensionSourceRateLimitException $e) {
            $this->assertFalse($e->authenticated);
            $this->assertStringContainsString('07:45', $e->getMessage());
            $this->assertSame(__('services/extension_sources.rate_limited_anonymous').' '.__('services/extension_sources.rate_limit_resets_at', ['time' => '07:45']), $e->getMessage());
        }
    }

    public function test_an_exhausted_limit_is_reported_instead_of_an_empty_list(): void
    {
        $this->source();
        Http::fake([
            'api.github.com/*' => Http::response(['message' => 'API rate limit exceeded'], 403, [
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) (time() + 600),
            ]),
        ]);

        $this->expectException(ExtensionSourceRateLimitException::class);

        app(ExtensionSourceManager::class)->listAvailablePlugins();
    }

    public function test_a_plain_forbidden_answer_is_not_mistaken_for_the_limit(): void
    {
        $this->source();
        Http::fake([
            'api.github.com/*' => Http::response(['message' => 'Forbidden'], 403, ['X-RateLimit-Remaining' => '42']),
        ]);

        $this->assertSame([], app(ExtensionSourceManager::class)->listAvailablePlugins());
    }
}
