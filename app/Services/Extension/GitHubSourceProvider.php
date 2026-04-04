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

namespace App\Services\Extension;

use App\Contracts\Extension\ExtensionSourceInterface;
use App\DTO\Extension\ReleaseInfo;
use App\Models\ExtensionSource;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * GitHub Source Provider
 *
 * Downloads extensions from GitHub repositories using the Releases API.
 * Supports private repositories via Personal Access Token authentication.
 */
class GitHubSourceProvider implements ExtensionSourceInterface
{
    protected string $baseUrl;

    protected string $owner;

    protected ?string $token;

    protected string $repoPrefix;

    protected string $themeRepoPrefix;

    public function __construct(
        protected ExtensionSource $source,
    ) {
        $this->baseUrl = rtrim($source->base_url, '/');
        $this->owner = $source->owner ?? config('extension-sources.github.default_owner', 'Dixlase');
        $this->token = $source->auth_token ?? config('extension-sources.github.default_token');
        $this->repoPrefix = $source->settings['repo_prefix'] ?? config('extension-sources.github.repo_prefix', 'dixlase-');
        $this->themeRepoPrefix = $source->settings['theme_repo_prefix'] ?? config('extension-sources.github.theme_repo_prefix', 'dixlase-theme-');
    }

    public function getType(): string
    {
        return 'github';
    }

    public function getLabel(): string
    {
        return $this->source->name;
    }

    /**
     * @return array<int, array{slug: string, name: string, description: ?string, version: ?string}>
     */
    public function listPlugins(): array
    {
        return $this->listRepositories($this->repoPrefix, 'plugin', $this->themeRepoPrefix);
    }

    /**
     * @return array<int, array{slug: string, name: string, description: ?string, version: ?string}>
     */
    public function listThemes(): array
    {
        return $this->listRepositories($this->themeRepoPrefix, 'theme');
    }

    public function getLatestRelease(string $slug, string $extensionType = 'plugin'): ?ReleaseInfo
    {
        $repo = $this->buildRepoName($slug, $extensionType);

        $response = $this->client()
            ->get("{$this->baseUrl}/repos/{$this->owner}/{$repo}/releases/latest");

        if ($response->failed()) {
            return null;
        }

        return ReleaseInfo::fromGitHub($response->json(), $slug, $extensionType);
    }

    public function downloadRelease(string $slug, string $version, string $extensionType = 'plugin'): string
    {
        $release = $this->findRelease($slug, $version, $extensionType);
        if ($release === null) {
            throw new RuntimeException("Release v{$version} not found for {$slug}.");
        }

        $downloadUrl = $release['download_url'];
        if ($downloadUrl === null) {
            throw new RuntimeException("No downloadable asset found for {$slug} v{$version}.");
        }

        $downloadPath = config('extension-sources.download_path');
        File::ensureDirectoryExists($downloadPath);

        $filename = "{$slug}-{$version}.zip";
        $filePath = "{$downloadPath}/{$filename}";

        $response = $this->client()->withOptions(['sink' => $filePath])->get($downloadUrl);

        if ($response->failed()) {
            File::delete($filePath);
            throw new RuntimeException("Failed to download {$slug} v{$version}: HTTP {$response->status()}");
        }

        return $filePath;
    }

    public function isAvailable(): bool
    {
        return $this->checkConnection()['success'];
    }

    /**
     * @return array{success: bool, message: string, details: array<string, mixed>}
     */
    public function checkConnection(): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/user");

            if ($response->successful()) {
                $user = $response->json();

                return [
                    'success' => true,
                    'message' => "Connected as {$user['login']}",
                    'details' => [
                        'login' => $user['login'],
                        'scopes' => $response->header('X-OAuth-Scopes'),
                        'rate_limit' => $response->header('X-RateLimit-Remaining'),
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => "Authentication failed: HTTP {$response->status()}",
                'details' => ['status' => $response->status()],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "Connection error: {$e->getMessage()}",
                'details' => ['exception' => get_class($e)],
            ];
        }
    }

    /**
     * Build the GitHub repository name from extension slug and type
     */
    protected function buildRepoName(string $slug, string $extensionType): string
    {
        $prefix = $extensionType === 'theme' ? $this->themeRepoPrefix : $this->repoPrefix;

        return $prefix.$slug;
    }

    /**
     * List repositories matching the given prefix
     *
     * @return array<int, array{slug: string, name: string, description: ?string, version: ?string}>
     */
    protected function listRepositories(string $prefix, string $extensionType, ?string $excludePrefix = null): array
    {
        $repos = [];
        $page = 1;

        do {
            $response = $this->client()->get("{$this->baseUrl}/orgs/{$this->owner}/repos", [
                'per_page' => 100,
                'page' => $page,
                'type' => 'all',
            ]);

            if ($response->failed()) {
                break;
            }

            $data = $response->json();
            if (empty($data)) {
                break;
            }

            foreach ($data as $repo) {
                $name = $repo['name'] ?? '';
                if (! str_starts_with($name, $prefix)) {
                    continue;
                }
                if ($excludePrefix !== null && str_starts_with($name, $excludePrefix)) {
                    continue;
                }

                $slug = substr($name, strlen($prefix));
                $repos[] = [
                    'slug' => $slug,
                    'name' => $repo['description'] ?? $slug,
                    'description' => $repo['description'] ?? null,
                    'version' => null,
                ];
            }

            $page++;
        } while (count($data) === 100);

        return $repos;
    }

    /**
     * Find a specific release by version tag
     *
     * @return array{download_url: ?string, tag_name: string}|null
     */
    protected function findRelease(string $slug, string $version, string $extensionType): ?array
    {
        $repo = $this->buildRepoName($slug, $extensionType);

        // Try with and without 'v' prefix
        foreach (["v{$version}", $version] as $tag) {
            $response = $this->client()
                ->get("{$this->baseUrl}/repos/{$this->owner}/{$repo}/releases/tags/{$tag}");

            if ($response->successful()) {
                $data = $response->json();
                $zipAsset = collect($data['assets'] ?? [])->first(
                    fn (array $asset) => str_ends_with($asset['name'], '.zip')
                );

                return [
                    'download_url' => $zipAsset['browser_download_url'] ?? $data['zipball_url'] ?? null,
                    'tag_name' => $data['tag_name'],
                ];
            }
        }

        return null;
    }

    /**
     * Create an authenticated HTTP client
     */
    protected function client(): PendingRequest
    {
        $client = Http::accept('application/vnd.github+json')
            ->timeout(30)
            ->retry(2, 1000, throw: false);

        if ($this->token) {
            $client = $client->withToken($this->token);
        }

        return $client;
    }
}
