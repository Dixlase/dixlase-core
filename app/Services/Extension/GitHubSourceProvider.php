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

namespace App\Services\Extension;

use App\Contracts\Extension\ExtensionSourceInterface;
use App\DTO\Extension\ReleaseInfo;
use App\Models\ExtensionSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * GitHub Source Provider
 *
 * Downloads extensions from GitHub repositories using the Releases API.
 * Supports private repositories via Personal Access Token authentication.
 */
class GitHubSourceProvider implements ExtensionSourceInterface
{
    /**
     * Timeout for the small metadata lookups behind the admin screens
     * (manifest, directory listing, thumbnail bytes).
     *
     * Downloads keep the longer default: a release ZIP is worth waiting
     * for, a missing thumbnail is not.
     */
    protected const LOOKUP_TIMEOUT = 8;

    protected string $baseUrl;

    protected string $owner;

    protected ?string $token;

    protected string $repoPrefix;

    protected string $themeRepoPrefix;

    protected string $coreRepo;

    public function __construct(
        protected ExtensionSource $source,
    ) {
        $this->baseUrl = rtrim($source->base_url, '/');
        $this->owner = $source->owner ?? config('extension-sources.github.default_owner', 'Dixlase');
        // Treat an empty-string `auth_token` the same as `null` so the
        // `EXTENSION_GITHUB_TOKEN` env fallback engages. `??` alone only
        // falls through on NULL, so a row where the operator cleared the
        // field to `''` (or a legacy row that was never populated) used
        // to pin an empty string as the API credential and every
        // download request went out anonymously, dying with
        // "release not found" on any private repo — the env token
        // configured on the same server was never consulted. Round 7's
        // operational note flagged this after a stale non-null PAT hid
        // the env fallback for the sandbox core-update run.
        $rawToken = $source->auth_token;
        $this->token = ($rawToken !== null && $rawToken !== '')
            ? $rawToken
            : config('extension-sources.github.default_token');
        $this->repoPrefix = $source->settings['repo_prefix'] ?? config('extension-sources.github.repo_prefix', 'dixlase-');
        $this->themeRepoPrefix = $source->settings['theme_repo_prefix'] ?? config('extension-sources.github.theme_repo_prefix', 'dixlase-theme-');
        $this->coreRepo = $source->settings['core_repo'] ?? config('extension-sources.github.core_repo', 'dixlase-core');
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
     * Retrieve detailed data for the specified slug
     *
     * @return array<string, mixed>|null
     */
    public function getExtensionDetails(string $slug, string $extensionType = 'plugin'): ?array
    {
        $repoName = $this->buildRepoName($slug, $extensionType);

        // Retrieve repository information (to obtain default_branch, etc.)
        $repoResponse = $this->client()->get("{$this->baseUrl}/repos/{$this->owner}/{$repoName}");
        if ($repoResponse->failed()) {
            return null;
        }
        $repo = $repoResponse->json();

        // Retrieve manifest
        $manifest = $this->fetchManifest($repoName, $extensionType);
        if ($manifest === null) {
            return null;
        }

        $defaultBranch = $repo['default_branch'] ?? 'main';
        $thumbnailFile = is_string($manifest['thumbnail'] ?? null)
            ? $manifest['thumbnail']
            : ($extensionType === 'theme' ? 'screenshot.png' : 'thumbnail.png');

        return [
            // Return the slug value received from URL as fixed (do not trust the slug field in manifest)
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
            'thumbnail_url' => route('admin.settings.extension-thumbnail-online', [
                'source' => $this->source->id,
                'type' => $extensionType,
                'slug' => $slug,
            ]),
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

        // Generate pseudo-release from default branch information if no release exists
        return $this->getDefaultBranchReleaseInfo($slug, $extensionType);
    }

    public function getLatestCoreRelease(): ?ReleaseInfo
    {
        $response = $this->client()
            ->get("{$this->baseUrl}/repos/{$this->owner}/{$this->coreRepo}/releases/latest");

        if (! $response->successful()) {
            // No release published yet — leave detection blank rather than
            // synthesising a pseudo-version from the default branch (the core
            // version-of-record is config('app.version'), not a branch tag).
            return null;
        }

        return ReleaseInfo::fromGitHub($response->json(), 'core', 'core');
    }

    public function downloadRelease(string $slug, string $version, string $extensionType = 'plugin'): string
    {
        // Attempt to retrieve from release
        $release = $this->findRelease($slug, $version, $extensionType);

        // Fall back to default branch zipball if no release exists
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

        $response = $this->downloadClient($downloadUrl)->withOptions(['sink' => $filePath])->get($downloadUrl);

        if ($response->failed()) {
            File::delete($filePath);
            throw new RuntimeException("Failed to download {$slug} v{$version}: HTTP {$response->status()}");
        }

        return $filePath;
    }

    public function downloadCoreRelease(string $version): string
    {
        // Locate the release row matching the requested tag (try v-prefixed
        // and bare tag formats since GitHub repos vary on this convention).
        $release = $this->findCoreRelease($version);

        if ($release === null || empty($release['download_url'])) {
            // Fall back to default-branch zipball so a core repo without
            // tagged releases can still be exercised in dev.
            $downloadUrl = $this->getCoreDefaultBranchZipballUrl();
            if ($downloadUrl === null) {
                throw new RuntimeException("Core release v{$version} not found and default branch is unavailable.");
            }
        } else {
            $downloadUrl = $release['download_url'];
        }

        $downloadPath = config('extension-sources.download_path');
        File::ensureDirectoryExists($downloadPath);

        $filename = "core-{$version}.zip";
        $filePath = "{$downloadPath}/{$filename}";

        $response = $this->downloadClient($downloadUrl)->withOptions(['sink' => $filePath])->get($downloadUrl);

        if ($response->failed()) {
            File::delete($filePath);
            throw new RuntimeException("Failed to download core v{$version}: HTTP {$response->status()}");
        }

        return $filePath;
    }

    /**
     * Find the GitHub release row matching the requested core tag.
     *
     * @return ?array{download_url: ?string, tag_name: string}
     */
    protected function findCoreRelease(string $version): ?array
    {
        // Try the GitHub Releases /tags/{tag} endpoint with both v-prefixed
        // and bare tag forms so we accept either naming.
        foreach (["v{$version}", $version] as $tag) {
            $response = $this->client()
                ->get("{$this->baseUrl}/repos/{$this->owner}/{$this->coreRepo}/releases/tags/{$tag}");

            if (! $response->successful()) {
                continue;
            }

            $payload = $response->json();
            $zipAsset = collect($payload['assets'] ?? [])->first(
                fn (array $asset) => str_ends_with($asset['name'] ?? '', '.zip')
            );

            // Use the asset's API url, not browser_download_url: on a
            // PRIVATE repo the browser URL 404s for a token-authenticated
            // request — release assets must be fetched from the API
            // endpoint with Accept: application/octet-stream (handled in
            // downloadCoreRelease). zipball_url is already an API url.
            return [
                'download_url' => $zipAsset['url'] ?? $payload['zipball_url'] ?? null,
                'tag_name' => $payload['tag_name'] ?? $tag,
            ];
        }

        return null;
    }

    /**
     * Fallback: default-branch zipball URL of the core repo.
     */
    protected function getCoreDefaultBranchZipballUrl(): ?string
    {
        $response = $this->client()
            ->get("{$this->baseUrl}/repos/{$this->owner}/{$this->coreRepo}");

        if ($response->failed()) {
            return null;
        }

        $defaultBranch = $response->json('default_branch') ?? 'main';

        return "{$this->baseUrl}/repos/{$this->owner}/{$this->coreRepo}/zipball/{$defaultBranch}";
    }

    /**
     * Generate pseudo-ReleaseInfo from default branch
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
     * Retrieve zipball URL of default branch
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
            // Verify connection with authentication endpoint if token exists, otherwise with owner information
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
     * Connection test with token authentication (GET /user)
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
     * Connection test without token (GET /users/{owner})
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
    /**
     * Build the GitHub repository name from extension slug and type.
     *
     * Public because the admin install controllers need to record the
     * computed repo name on the Plugin/Theme row at install time so
     * later update checks know where to look.
     */
    public function buildRepoName(string $slug, string $extensionType): string
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

                // Always derive slug from repository name (do not trust the slug field in manifest; use fixed value)
                $slug = substr($name, strlen($prefix));
                // Retrieve official name, description, and version from plugin.json / theme.json
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
                    'thumbnail_url' => route('admin.settings.extension-thumbnail-online', [
                        'source' => $this->source->id,
                        'type' => $extensionType,
                        'slug' => $slug,
                    ]),
                ];
            }

            $page++;
        } while (count($data) === 100);

        return $repos;
    }

    /**
     * Fetch the raw thumbnail bytes for an extension in this GitHub source.
     *
     * Probes `thumbnail.{ext}` at the repo root first (recommended —
     * matches the extension-root location the installed
     * AdminExtensionThumbnailController now prefers) and only then a
     * manifest-declared path or the legacy `resources/assets/thumbnail.{ext}`
     * layout, so a plugin author who follows the standard layout does
     * not need to declare anything. Extensions are checked in `webp,
     * png, jpg, jpeg` priority order — modern/small first.
     *
     * Uses the Contents API rather than raw.githubusercontent.com so the
     * request is authenticated with the same token as every other
     * provider call; raw.githubusercontent.com would 404 for a private
     * repo even when a valid token is present because there is no
     * standard way to authenticate that host from a caller that isn't a
     * git client.
     *
     * @return array{content: string, mime: string}|null
     */
    public function fetchThumbnail(string $slug, string $extensionType = 'plugin'): ?array
    {
        $repoName = $this->buildRepoName($slug, $extensionType);
        $manifest = $this->fetchManifest($repoName, $extensionType);

        // Names to accept at the repository root, in priority order. This is
        // the recommended location — alongside plugin.json / theme.json,
        // where no build tool's outDir wipe can reach it.
        $rootNames = [];
        foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
            $rootNames[] = "thumbnail.{$ext}";
        }
        if ($extensionType === 'theme') {
            foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
                $rootNames[] = "screenshot.{$ext}";
            }
        }

        // Ask the repository what it has instead of guessing twelve times.
        // One directory listing answers every candidate at once; probing them
        // one by one meant a round trip per name, and for an extension that
        // ships no thumbnail at all, twelve of them.
        $path = $this->firstExisting($repoName, '', $rootNames);

        // A manifest may point somewhere else entirely; that is a single
        // known path, so fetch it directly rather than listing its directory.
        if ($path === null) {
            $declared = is_array($manifest) && is_string($manifest['thumbnail'] ?? null)
                ? ltrim($manifest['thumbnail'], '/')
                : null;

            if ($declared !== null) {
                $bytes = $this->fetchRepoFileBytes($repoName, $declared);
                if ($bytes !== null) {
                    return ['content' => $bytes, 'mime' => $this->thumbnailMime($declared)];
                }
            }
        }

        // Legacy location — where older extensions ship the file. Still
        // probed so existing repositories do not have to move it before
        // their next release.
        if ($path === null) {
            $legacyNames = [];
            foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
                $legacyNames[] = "thumbnail.{$ext}";
            }

            $found = $this->firstExisting($repoName, 'resources/assets', $legacyNames);
            $path = $found === null ? null : 'resources/assets/'.$found;
        }

        if ($path === null) {
            return null;
        }

        $bytes = $this->fetchRepoFileBytes($repoName, $path);

        if ($bytes === null) {
            return null;
        }

        return ['content' => $bytes, 'mime' => $this->thumbnailMime($path)];
    }

    /**
     * The first of $names that the repository actually has in $directory.
     *
     * Costs one Contents API call regardless of how many names are asked
     * about. Returns the file name (not the full path), or null when the
     * directory is missing or holds none of them.
     *
     * @param  array<int, string>  $names
     */
    protected function firstExisting(string $repoName, string $directory, array $names): ?string
    {
        $entries = $this->listRepoDirectory($repoName, $directory);

        if ($entries === null) {
            return null;
        }

        foreach ($names as $name) {
            if (in_array($name, $entries, true)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * File names directly inside a repository directory.
     *
     * Returns null when the directory does not exist or cannot be read —
     * which the caller treats the same as "none of the names are there".
     *
     * @return array<int, string>|null
     */
    protected function listRepoDirectory(string $repoName, string $directory = ''): ?array
    {
        $path = trim($directory, '/');
        $url = "{$this->baseUrl}/repos/{$this->owner}/{$repoName}/contents";
        if ($path !== '') {
            $url .= '/'.$path;
        }

        // A short timeout: this is a lookup behind an admin screen that draws
        // one card per extension, not a download.
        $response = $this->client(self::LOOKUP_TIMEOUT)->acceptJson()->get($url);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data)) {
            return null;
        }

        $names = [];
        foreach ($data as $entry) {
            if (is_array($entry) && ($entry['type'] ?? null) === 'file' && is_string($entry['name'] ?? null)) {
                $names[] = $entry['name'];
            }
        }

        return $names;
    }

    /**
     * Content type for a thumbnail path.
     */
    protected function thumbnailMime(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }

    /**
     * Fetch a file's raw bytes from the repo via the Contents API.
     * Returns null on any failure (missing file, auth error, oversized
     * file that the API refuses to inline, etc.).
     */
    protected function fetchRepoFileBytes(string $repoName, string $path): ?string
    {
        $response = $this->client(self::LOOKUP_TIMEOUT)
            ->acceptJson()
            ->get("{$this->baseUrl}/repos/{$this->owner}/{$repoName}/contents/{$path}");

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $content = $data['content'] ?? null;
        $encoding = $data['encoding'] ?? null;

        if (! is_string($content) || $encoding !== 'base64') {
            return null;
        }

        $decoded = base64_decode(str_replace("\n", '', $content), true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * Retrieve plugin.json / theme.json from repository
     *
     * @return array<string, mixed>|null
     */
    protected function fetchManifest(string $repoName, string $extensionType): ?array
    {
        $manifestFile = $extensionType === 'theme' ? 'theme.json' : 'plugin.json';

        $response = $this->client(self::LOOKUP_TIMEOUT)
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
     * Resolve multilingual string fields with current locale
     *
     * Return strings as-is, and resolve associative arrays (e.g., `{ja: "...", en: "..."}`) with current locale
     * Return null for unresolvable values (objects, numbers, etc.) to prevent them from being stringified as `[object Object]` on the frontend
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
     * Strictly check string fields (do not perform multilingual resolution)
     *
     * Used for fields that should be strings (slug, version, license, etc.)
     * Returns null if a non-string value is received
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

                // Asset API url (not browser_download_url): see
                // findCoreRelease — the browser URL 404s for a
                // token-authenticated request on a private repo.
                return [
                    'download_url' => $zipAsset['url'] ?? $data['zipball_url'] ?? null,
                    'tag_name' => $data['tag_name'],
                ];
            }
        }

        return null;
    }

    /**
     * Create an authenticated HTTP client
     */
    protected function client(int $timeout = 30): PendingRequest
    {
        $client = Http::accept('application/vnd.github+json')
            ->timeout($timeout)
            // Retry only what retrying can fix. Laravel's retry() treats any
            // failed response as a failure, so the default retried 404s too:
            // every "this file is not in the repo" answer cost a second of
            // sleep plus a second request. Probing for a thumbnail asks that
            // question a dozen times per extension, which is how a plugin
            // list took two and a half minutes to draw.
            ->retry(2, 1000, function (\Throwable $exception): bool {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                if ($exception instanceof RequestException && $exception->response !== null) {
                    $status = $exception->response->status();

                    // Rate limiting and server faults are worth another go;
                    // 404 / 401 / 403 are answers, not failures.
                    return $status === 429 || $status >= 500;
                }

                return false;
            }, throw: false);

        if ($this->token) {
            $client = $client->withToken($this->token);
        }

        return $client;
    }

    /**
     * Client for downloading a release asset / zipball.
     *
     * The right Accept header depends on the endpoint:
     *   - a release asset API url (/releases/assets/{id}) needs
     *     application/octet-stream — required to fetch it from a PRIVATE
     *     repo, where the API then redirects to a signed download
     *     (browser_download_url would 404 for a token request);
     *   - the zipball endpoint rejects octet-stream with HTTP 415, so it
     *     keeps the github media type.
     * A longer timeout covers larger archives.
     */
    protected function downloadClient(string $downloadUrl): PendingRequest
    {
        $accept = str_contains($downloadUrl, '/releases/assets/')
            ? 'application/octet-stream'
            : 'application/vnd.github+json';

        $client = Http::accept($accept)
            ->timeout((int) config('extension-sources.download_timeout', 600))
            ->retry(2, 1000, throw: false);

        if ($this->token) {
            $client = $client->withToken($this->token);
        }

        return $client;
    }
}
