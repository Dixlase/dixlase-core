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

namespace Tests\Unit;

use App\Models\ExtensionSource;
use App\Services\Extension\GitHubSourceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubSourceProviderTest extends TestCase
{
    use RefreshDatabase;

    protected ExtensionSource $source;

    protected GitHubSourceProvider $provider;

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

        $this->provider = new GitHubSourceProvider($this->source);
    }

    public function test_get_type_returns_github(): void
    {
        $this->assertEquals('github', $this->provider->getType());
    }

    public function test_get_label_returns_source_name(): void
    {
        $this->assertEquals('Test GitHub', $this->provider->getLabel());
    }

    public function test_check_connection_success(): void
    {
        Http::fake([
            'api.github.com/user' => Http::response([
                'login' => 'test-user',
            ], 200, [
                'X-OAuth-Scopes' => 'repo',
                'X-RateLimit-Remaining' => '4999',
            ]),
        ]);

        $result = $this->provider->checkConnection();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('test-user', $result['message']);
        $this->assertEquals('test-user', $result['details']['login']);
    }

    public function test_check_connection_failure(): void
    {
        Http::fake([
            'api.github.com/user' => Http::response(['message' => 'Bad credentials'], 401),
        ]);

        $result = $this->provider->checkConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('401', $result['message']);
    }

    public function test_check_connection_public_success_without_token(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Public GitHub',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'TestOrg',
            'priority' => 0,
        ]);
        $provider = new GitHubSourceProvider($source);

        Http::fake([
            'api.github.com/users/TestOrg' => Http::response([
                'login' => 'TestOrg',
            ], 200, [
                'X-RateLimit-Remaining' => '59',
            ]),
        ]);

        $result = $provider->checkConnection();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('TestOrg', $result['message']);
        $this->assertFalse($result['details']['authenticated']);
    }

    public function test_check_connection_public_failure_without_token(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Public GitHub',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'NonExistentOrg',
            'priority' => 0,
        ]);
        $provider = new GitHubSourceProvider($source);

        Http::fake([
            'api.github.com/users/NonExistentOrg' => Http::response(['message' => 'Not Found'], 404),
        ]);

        $result = $provider->checkConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('404', $result['message']);
    }

    public function test_is_available_returns_true_on_success(): void
    {
        Http::fake([
            'api.github.com/user' => Http::response(['login' => 'test'], 200),
        ]);

        $this->assertTrue($this->provider->isAvailable());
    }

    public function test_is_available_returns_false_on_failure(): void
    {
        Http::fake([
            'api.github.com/user' => Http::response([], 401),
        ]);

        $this->assertFalse($this->provider->isAvailable());
    }

    public function test_get_latest_release(): void
    {
        Http::fake([
            'api.github.com/repos/TestOrg/dixlase-test-plugin/releases/latest' => Http::response([
                'tag_name' => 'v1.5.0',
                'body' => 'Release notes',
                'published_at' => '2026-03-01T00:00:00Z',
                'id' => 100,
                'prerelease' => false,
                'zipball_url' => 'https://api.github.com/repos/TestOrg/dixlase-test-plugin/zipball/v1.5.0',
                'assets' => [
                    [
                        'name' => 'test-plugin-1.5.0.zip',
                        'browser_download_url' => 'https://github.com/TestOrg/dixlase-test-plugin/releases/download/v1.5.0/test-plugin-1.5.0.zip',
                    ],
                ],
            ]),
        ]);

        $release = $this->provider->getLatestRelease('test-plugin');

        $this->assertNotNull($release);
        $this->assertEquals('1.5.0', $release->version);
        $this->assertEquals('test-plugin', $release->slug);
        $this->assertStringContainsString('test-plugin-1.5.0.zip', $release->downloadUrl);
    }

    public function test_get_latest_release_returns_null_on_404(): void
    {
        Http::fake([
            'api.github.com/repos/TestOrg/dixlase-nonexistent/releases/latest' => Http::response([], 404),
        ]);

        $this->assertNull($this->provider->getLatestRelease('nonexistent'));
    }

    public function test_list_plugins(): void
    {
        Http::fake([
            'api.github.com/orgs/TestOrg/repos*' => Http::sequence()
                ->push([
                    ['name' => 'dixlase-pages', 'description' => 'Page manager'],
                    ['name' => 'dixlase-blog', 'description' => 'Blog plugin'],
                    ['name' => 'unrelated-repo', 'description' => 'Not a plugin'],
                    ['name' => 'dixlase-theme-default', 'description' => 'Default theme'],
                ])
                ->push([]),
        ]);

        $plugins = $this->provider->listPlugins();

        $this->assertCount(2, $plugins);
        $this->assertEquals('pages', $plugins[0]['slug']);
        $this->assertEquals('blog', $plugins[1]['slug']);
    }

    public function test_list_themes(): void
    {
        Http::fake([
            'api.github.com/orgs/TestOrg/repos*' => Http::sequence()
                ->push([
                    ['name' => 'dixlase-theme-default', 'description' => 'Default theme'],
                    ['name' => 'dixlase-theme-corporate', 'description' => 'Corporate theme'],
                    ['name' => 'dixlase-pages', 'description' => 'Not a theme'],
                ])
                ->push([]),
        ]);

        $themes = $this->provider->listThemes();

        $this->assertCount(2, $themes);
        $this->assertEquals('default', $themes[0]['slug']);
        $this->assertEquals('corporate', $themes[1]['slug']);
    }

    public function test_download_release(): void
    {
        $zipContent = 'PK'.str_repeat("\0", 100);

        Http::fake([
            'api.github.com/repos/TestOrg/dixlase-test-plugin/releases/tags/v1.0.0' => Http::response([
                'tag_name' => 'v1.0.0',
                'zipball_url' => 'https://api.github.com/repos/TestOrg/dixlase-test-plugin/zipball/v1.0.0',
                'assets' => [
                    [
                        'name' => 'test-plugin-1.0.0.zip',
                        'browser_download_url' => 'https://github.com/test/download.zip',
                    ],
                ],
            ]),
            'github.com/test/download.zip' => Http::response($zipContent, 200),
        ]);

        $downloadPath = config('extension-sources.download_path');

        $path = $this->provider->downloadRelease('test-plugin', '1.0.0');

        $this->assertStringEndsWith('test-plugin-1.0.0.zip', $path);

        // Clean up
        if (file_exists($path)) {
            unlink($path);
        }
        if (is_dir($downloadPath)) {
            rmdir($downloadPath);
        }
    }

    public function test_download_release_throws_on_not_found(): void
    {
        Http::fake([
            'api.github.com/repos/TestOrg/dixlase-test-plugin/releases/tags/*' => Http::response([], 404),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Release v9.9.9 not found');

        $this->provider->downloadRelease('test-plugin', '9.9.9');
    }

    public function test_theme_repo_name_uses_theme_prefix(): void
    {
        Http::fake([
            'api.github.com/repos/TestOrg/dixlase-theme-my-theme/releases/latest' => Http::response([
                'tag_name' => 'v1.0.0',
                'assets' => [],
            ]),
        ]);

        $release = $this->provider->getLatestRelease('my-theme', 'theme');

        $this->assertNotNull($release);
        $this->assertEquals('1.0.0', $release->version);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'dixlase-theme-my-theme'));
    }
}
