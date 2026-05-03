<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

    /**
     * 指定したスラッグの詳細データを取得する
     *
     * @return array<string, mixed>|null
     */
    public function getExtensionDetails(string $slug, string $extensionType = 'plugin'): ?array
    {
        $repoName = $this->buildRepoName($slug, $extensionType);

        // リポジトリ情報を取得（default_branch などを取得するため）
        $repoResponse = $this->client()->get("{$this->baseUrl}/repos/{$this->owner}/{$repoName}");
        if ($repoResponse->failed()) {
            return null;
        }
        $repo = $repoResponse->json();

        // manifest を取得
        $manifest = $this->fetchManifest($repoName, $extensionType);
        if ($manifest === null) {
            return null;
        }

        $defaultBranch = $repo['default_branch'] ?? 'main';
        $thumbnailFile = is_string($manifest['thumbnail'] ?? null)
            ? $manifest['thumbnail']
            : ($extensionType === 'theme' ? 'screenshot.png' : 'thumbnail.png');

        return [
            // slug は URL から受け取った値を固定で返す（manifest の slug フィールドは信頼しない）
            'slug' => $slug,
            'name' => $this->resolveLocalizedString($manifest['name'] ?? null) ?? ($repo['description'] ?? $slug),
            'description' => $this->resolveLocalizedString($manifest['description'] ?? null) ?? $repo['description'] ?? null,
            'version' => $this->resolveString($manifest['version'] ?? null),
            'author' => $this->resolveString($manifest['author'] ?? null),
            'email' => $this->resolveString($manifest['email'] ?? null),
            'url' => $this->resolveString($manifest['url'] ?? $manifest['homepage'] ?? null),
            'license' => $this->resolveString($manifest['license'] ?? null),
            'package_name' => $this->resolveString($manifest['package_name'] ?? null),
            'namespace' => $this->resolveString($manifest['namespace'] ?? null),
            'thumbnail_url' => "https://raw.githubusercontent.com/{$this->owner}/{$repoName}/{$defaultBranch}/{$thumbnailFile}",
            'repository_url' => $repo['html_url'] ?? null,
            'updated_at' => $repo['updated_at'] ?? null,
            'extension_type' => $extensionType,
        ];
    }

    public function getLatestRelease(string $slug, string $extensionType = 'plugin'): ?ReleaseInfo
    {
        $repo = $this->buildRepoName($slug, $extensionType);

        $response = $this->client()
            ->get("{$this->baseUrl}/repos/{$this->owner}/{$repo}/releases/latest");

        if ($response->successful()) {
            return ReleaseInfo::fromGitHub($response->json(), $slug, $extensionType);
        }

        // Release がない場合はデフォルトブランチの情報から疑似 Release を生成
        return $this->getDefaultBranchReleaseInfo($slug, $extensionType);
    }

    public function downloadRelease(string $slug, string $version, string $extensionType = 'plugin'): string
    {
        // Release からの取得を試みる
        $release = $this->findRelease($slug, $version, $extensionType);

        // Release がない場合はデフォルトブランチの zipball にフォールバック
        if ($release === null) {
            $downloadUrl = $this->getDefaultBranchZipballUrl($slug, $extensionType);
            if ($downloadUrl === null) {
                throw new RuntimeException("Release v{$version} not found for {$slug} and default branch is unavailable.");
            }
        } else {
            $downloadUrl = $release['download_url'];
        }

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

    /**
     * デフォルトブランチから疑似 ReleaseInfo を生成
     */
    protected function getDefaultBranchReleaseInfo(string $slug, string $extensionType): ?ReleaseInfo
    {
        $repo = $this->buildRepoName($slug, $extensionType);
        $manifest = $this->fetchManifest($repo, $extensionType);
        $version = $manifest['version'] ?? '0.0.0-dev';

        $downloadUrl = $this->getDefaultBranchZipballUrl($slug, $extensionType);
        if ($downloadUrl === null) {
            return null;
        }

        return new ReleaseInfo(
            version: $version,
            slug: $slug,
            extensionType: $extensionType,
            downloadUrl: $downloadUrl,
            metadata: ['source' => 'default_branch'],
        );
    }

    /**
     * デフォルトブランチの zipball URL を取得
     */
    protected function getDefaultBranchZipballUrl(string $slug, string $extensionType): ?string
    {
        $repo = $this->buildRepoName($slug, $extensionType);

        $response = $this->client()
            ->get("{$this->baseUrl}/repos/{$this->owner}/{$repo}");

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $defaultBranch = $data['default_branch'] ?? 'main';

        return "{$this->baseUrl}/repos/{$this->owner}/{$repo}/zipball/{$defaultBranch}";
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
            // トークンがある場合は認証エンドポイント、ない場合はオーナー情報で接続確認
            if ($this->token) {
                return $this->checkAuthenticatedConnection();
            }

            return $this->checkPublicConnection();
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "Connection error: {$e->getMessage()}",
                'details' => ['exception' => get_class($e)],
            ];
        }
    }

    /**
     * トークン認証ありの接続テスト（GET /user）
     *
     * @return array{success: bool, message: string, details: array<string, mixed>}
     */
    protected function checkAuthenticatedConnection(): array
    {
        $response = $this->client()->get("{$this->baseUrl}/user");

        if ($response->successful()) {
            $user = $response->json();

            return [
                'success' => true,
                'message' => "Connected as {$user['login']}",
                'details' => [
                    'login' => $user['login'],
                    'authenticated' => true,
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
    }

    /**
     * トークンなしの接続テスト（GET /users/{owner}）
     *
     * @return array{success: bool, message: string, details: array<string, mixed>}
     */
    protected function checkPublicConnection(): array
    {
        $response = $this->client()->get("{$this->baseUrl}/users/{$this->owner}");

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'message' => "Connected to {$data['login']}",
                'details' => [
                    'login' => $data['login'],
                    'authenticated' => false,
                    'rate_limit' => $response->header('X-RateLimit-Remaining'),
                ],
            ];
        }

        return [
            'success' => false,
            'message' => "Connection failed: HTTP {$response->status()}",
            'details' => ['status' => $response->status()],
        ];
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

                // slug はリポジトリ名から必ず導出する（manifest の slug フィールドは信頼せず固定）
                $slug = substr($name, strlen($prefix));
                // plugin.json / theme.json から正式な名前・説明・バージョンを取得
                $manifest = $this->fetchManifest($name, $extensionType) ?? [];
                $defaultBranch = $repo['default_branch'] ?? 'main';
                $thumbnailFile = is_string($manifest['thumbnail'] ?? null)
                    ? $manifest['thumbnail']
                    : ($extensionType === 'theme' ? 'screenshot.png' : 'thumbnail.png');

                $repos[] = [
                    'slug' => $slug,
                    'name' => $this->resolveLocalizedString($manifest['name'] ?? null) ?? ($repo['description'] ?? $slug),
                    'description' => $this->resolveLocalizedString($manifest['description'] ?? null) ?? $repo['description'] ?? null,
                    'version' => $this->resolveString($manifest['version'] ?? null),
                    'author' => $this->resolveString($manifest['author'] ?? null),
                    'license' => $this->resolveString($manifest['license'] ?? null),
                    'thumbnail_url' => "https://raw.githubusercontent.com/{$this->owner}/{$name}/{$defaultBranch}/{$thumbnailFile}",
                ];
            }

            $page++;
        } while (count($data) === 100);

        return $repos;
    }

    /**
     * リポジトリから plugin.json / theme.json を取得
     *
     * @return array<string, mixed>|null
     */
    protected function fetchManifest(string $repoName, string $extensionType): ?array
    {
        $manifestFile = $extensionType === 'theme' ? 'theme.json' : 'plugin.json';

        $response = $this->client()
            ->acceptJson()
            ->get("{$this->baseUrl}/repos/{$this->owner}/{$repoName}/contents/{$manifestFile}");

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $content = $data['content'] ?? null;
        if ($content === null) {
            return null;
        }

        try {
            $decoded = base64_decode(str_replace("\n", '', $content), true);
            if ($decoded === false) {
                return null;
            }

            $manifest = json_decode($decoded, true);
            if (! is_array($manifest)) {
                return null;
            }

            return $manifest;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 多言語対応の文字列フィールドを現在のロケールで解決する
     *
     * 文字列はそのまま返し、連想配列（例: `{ja: "...", en: "..."}`）は現在のロケールで解決する。
     * 解決不能な値（オブジェクト・数値など）は null を返し、フロントで `[object Object]` として文字列化されるのを防ぐ。
     */
    protected function resolveLocalizedString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            $locale = app()->getLocale();
            $resolved = $value[$locale] ?? $value['en'] ?? $value['ja'] ?? reset($value);

            return is_string($resolved) ? $resolved : null;
        }

        return null;
    }

    /**
     * 文字列フィールドを厳密にチェックする（多言語解決は行わない）
     *
     * 本来文字列であるべきフィールド（slug, version, license 等）に対して使用する。
     * 文字列以外が来た場合は null を返す。
     */
    protected function resolveString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
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
