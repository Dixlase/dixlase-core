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
use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Extension Source Manager
 *
 * Extension source management, provider registry, update checks,
 * and central service managing fallback downloads across multiple sources
 */
class ExtensionSourceManager
{
    /**
     * Registered provider classes indexed by type
     *
     * @var array<string, class-string<ExtensionSourceInterface>>
     */
    protected array $providers = [];

    public function __construct(
        protected SourceVerifier $verifier,
    ) {
        $this->bootProviders();
    }

    // ========================================
    // Provider Registry
    // ========================================

    /**
     * Register a provider class for a given type
     *
     * @param  class-string<ExtensionSourceInterface>  $providerClass
     */
    public function registerProvider(string $type, string $providerClass): void
    {
        $this->providers[$type] = $providerClass;
    }

    /**
     * Get available provider types and their registration status
     *
     * @return array<string, array{class: class-string<ExtensionSourceInterface>, registered: bool}>
     */
    public function getAvailableTypes(): array
    {
        $types = [];
        foreach ($this->providers as $type => $class) {
            $types[$type] = [
                'class' => $class,
                'registered' => class_exists($class),
            ];
        }

        return $types;
    }

    /**
     * Create a provider instance for the given source
     */
    public function makeProvider(ExtensionSource $source): ExtensionSourceInterface
    {
        $type = $source->type;

        if (! isset($this->providers[$type])) {
            throw new RuntimeException("No provider registered for source type: {$type}");
        }

        $class = $this->providers[$type];
        if (! class_exists($class)) {
            throw new RuntimeException("Provider class does not exist: {$class}");
        }

        return new $class($source);
    }

    // ========================================
    // Source Management
    // ========================================

    /**
     * Get all enabled sources ordered by priority
     *
     * Auto-create default sources from config presets if no sources exist in DB
     *
     * @return Collection<int, ExtensionSource>
     */
    public function getEnabledSources(): Collection
    {
        $sources = ExtensionSource::query()->enabled()->get();

        if ($sources->isEmpty()) {
            $this->seedDefaultSources();
            $sources = ExtensionSource::query()->enabled()->get();
        }

        return $sources;
    }

    /**
     * Create default sources in DB from config presets
     */
    protected function seedDefaultSources(): void
    {
        $presets = config('extension-sources.presets', []);

        foreach ($presets as $type => $preset) {
            // Skip if a source of the same type already exists
            if (ExtensionSource::query()->ofType($type)->exists()) {
                continue;
            }

            $attributes = [
                'name' => $preset['name'] ?? ucfirst($type),
                'type' => $type,
                'is_enabled' => true,
                'is_official' => $preset['is_official'] ?? false,
                'priority' => 0,
            ];

            // Set default values by type
            if ($type === 'github') {
                $attributes['base_url'] = config('extension-sources.github.api_base', 'https://api.github.com');
                $attributes['owner'] = config('extension-sources.github.default_owner', 'Dixlase');
            }

            ExtensionSource::query()->create($attributes);
        }
    }

    /**
     * Verify and update the official status of a source
     */
    public function verifySource(ExtensionSource $source): array
    {
        $result = $this->verifier->verify($source);

        if ($result['status'] === 'valid') {
            $source->update(['is_official' => true]);
        } elseif (in_array($result['status'], ['invalid', 'unsigned'])) {
            $source->update(['is_official' => false]);
        }

        return $result;
    }

    /**
     * Determine if source is official (hardcoded check + Ed25519 signature)
     */
    public function isOfficialSource(ExtensionSource $source): bool
    {
        // 1. Hardcoded check for Core preset source types
        $preset = config("extension-sources.presets.{$source->type}");
        if ($preset && ($preset['is_official'] ?? false)) {
            return true;
        }

        // 2. Verification by Ed25519 signature
        if ($source->hasSignature()) {
            return $this->verifier->verify($source)['verified'];
        }

        return false;
    }

    // ========================================
    // Extension Listing
    // ========================================

    /**
     * Aggregate available plugins from all enabled sources
     *
     * @return array<int, array{slug: string, name: string, description: ?string, version: ?string, source_id: int, source_name: string}>
     */
    public function listAvailablePlugins(): array
    {
        $plugins = [];

        foreach ($this->getEnabledSources() as $source) {
            try {
                $provider = $this->makeProvider($source);
                foreach ($provider->listPlugins() as $plugin) {
                    $plugin['source_id'] = $source->id;
                    $plugin['source_name'] = $source->name;
                    $plugins[] = $plugin;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $plugins;
    }

    /**
     * Aggregate available themes from all enabled sources
     *
     * @return array<int, array{slug: string, name: string, description: ?string, version: ?string, source_id: int, source_name: string}>
     */
    public function listAvailableThemes(): array
    {
        $themes = [];

        foreach ($this->getEnabledSources() as $source) {
            try {
                $provider = $this->makeProvider($source);
                foreach ($provider->listThemes() as $theme) {
                    $theme['source_id'] = $source->id;
                    $theme['source_name'] = $source->name;
                    $themes[] = $theme;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $themes;
    }

    /**
     * Retrieve extension detail data for the specified slug from sources
     *
     * @return array<string, mixed>|null Detail data with source_id/source_name
     */
    public function getExtensionDetails(string $slug, string $extensionType = 'plugin'): ?array
    {
        foreach ($this->getEnabledSources() as $source) {
            try {
                $provider = $this->makeProvider($source);
                $details = $provider->getExtensionDetails($slug, $extensionType);
                if ($details !== null) {
                    $details['source_id'] = $source->id;
                    $details['source_name'] = $source->name;

                    return $details;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    // ========================================
    // Update Checking
    // ========================================

    /**
     * Check all installed plugins and themes for available updates
     *
     * @return array{plugins: array<int, array{slug: string, current: string, available: string, source_id: int}>, themes: array<int, array{slug: string, current: string, available: string, source_id: int}>, core: ?array{current: string, available: string, source_id: ?int, release_url: ?string}}
     */
    public function checkUpdates(): array
    {
        $pluginUpdates = $this->checkPluginUpdates();
        $themeUpdates = $this->checkThemeUpdates();
        $coreUpdate = $this->checkCoreUpdate();

        return [
            'plugins' => $pluginUpdates,
            'themes' => $themeUpdates,
            'core' => $coreUpdate,
        ];
    }

    /**
     * Check the Dixlase Core itself for an available update.
     *
     * Polls each enabled source in priority order; the first one that returns
     * a release wins. Persists the discovered metadata onto the singleton
     * core_releases row regardless of whether an update is available so the
     * UI can show "last checked: ..." even when the core is current.
     *
     * @return ?array{current: string, available: string, source_id: ?int, release_url: ?string}
     */
    protected function checkCoreUpdate(): ?array
    {
        $coreState = \App\Models\CoreRelease::singleton();
        $current = (string) (\App\Models\CoreVersionHistory::currentVersion() ?? config('app.version', '0.0.0'));

        $release = null;
        $sourceId = null;
        foreach ($this->getEnabledSources() as $source) {
            try {
                $candidate = $this->makeProvider($source)->getLatestCoreRelease();
            } catch (\Throwable) {
                continue;
            }

            if ($candidate !== null) {
                $release = $candidate;
                $sourceId = $source->id;
                break;
            }
        }

        if ($release === null) {
            // No source provided a release; just stamp the check timestamp.
            $coreState->forceFill(['last_version_check' => now()])->save();

            return null;
        }

        $isNewer = version_compare($release->version, $current, '>');
        $publishedAt = $release->publishedAt ? \Illuminate\Support\Carbon::parse($release->publishedAt) : null;

        $tagName = $release->metadata['tag_name'] ?? null;
        $releaseUrl = $tagName !== null
            ? sprintf(
                'https://github.com/%s/%s/releases/tag/%s',
                $this->resolveSourceOwner($sourceId),
                $this->resolveCoreRepo($sourceId),
                $tagName
            )
            : null;

        $coreState->forceFill([
            'source_id' => $sourceId,
            'source_repo' => $this->resolveCoreRepo($sourceId),
            'available_version' => $isNewer ? $release->version : null,
            'available_version_published_at' => $isNewer ? $publishedAt : null,
            'release_url' => $isNewer ? $releaseUrl : null,
            'release_notes' => $isNewer ? $release->changelog : null,
            'last_version_check' => now(),
        ])->save();

        if (! $isNewer) {
            return null;
        }

        return [
            'current' => $current,
            'available' => $release->version,
            'source_id' => $sourceId,
            'release_url' => $releaseUrl,
        ];
    }

    /**
     * Owner string for the resolved source (used to build release URLs).
     */
    protected function resolveSourceOwner(?int $sourceId): string
    {
        if ($sourceId === null) {
            return (string) config('extension-sources.github.default_owner', 'Dixlase');
        }

        $source = ExtensionSource::query()->find($sourceId);

        return (string) ($source?->owner ?? config('extension-sources.github.default_owner', 'Dixlase'));
    }

    /**
     * Core repo name for the resolved source.
     */
    protected function resolveCoreRepo(?int $sourceId): string
    {
        $defaultRepo = (string) config('extension-sources.github.core_repo', 'dixlase-core');
        if ($sourceId === null) {
            return $defaultRepo;
        }

        $source = ExtensionSource::query()->find($sourceId);

        return (string) ($source?->settings['core_repo'] ?? $defaultRepo);
    }

    /**
     * Check installed plugins for updates
     *
     * @return array<int, array{slug: string, current: string, available: string, source_id: int}>
     */
    protected function checkPluginUpdates(): array
    {
        $updates = [];
        $plugins = Plugin::query()->installed()->get();

        foreach ($plugins as $plugin) {
            $release = $this->getLatestReleaseForExtension($plugin->slug, 'plugin', $plugin->source_id);
            if ($release && version_compare($release->version, $plugin->version, '>')) {
                $plugin->update([
                    'available_version' => $release->version,
                    'release_notes' => $release->changelog,
                    'last_version_check' => now(),
                ]);
                $updates[] = [
                    'slug' => $plugin->slug,
                    'current' => $plugin->version,
                    'available' => $release->version,
                    'source_id' => $plugin->source_id,
                ];
            } else {
                $plugin->update(['last_version_check' => now()]);
            }
        }

        return $updates;
    }

    /**
     * Check installed themes for updates
     *
     * @return array<int, array{slug: string, current: string, available: string, source_id: ?int}>
     */
    protected function checkThemeUpdates(): array
    {
        $updates = [];
        $themes = Theme::query()->installed()->get();

        foreach ($themes as $theme) {
            $release = $this->getLatestReleaseForExtension($theme->slug, 'theme', $theme->source_id);
            if ($release && version_compare($release->version, $theme->version, '>')) {
                $theme->update([
                    'available_version' => $release->version,
                    'release_notes' => $release->changelog,
                    'last_version_check' => now(),
                ]);
                $updates[] = [
                    'slug' => $theme->slug,
                    'current' => $theme->version,
                    'available' => $release->version,
                    'source_id' => $theme->source_id,
                ];
            } else {
                $theme->update(['last_version_check' => now()]);
            }
        }

        return $updates;
    }

    /**
     * Get the latest release for an extension from a specific source or all sources
     */
    protected function getLatestReleaseForExtension(string $slug, string $extensionType, ?int $sourceId = null): ?ReleaseInfo
    {
        if ($sourceId !== null) {
            $source = ExtensionSource::query()->find($sourceId);
            if ($source && $source->is_enabled) {
                try {
                    return $this->makeProvider($source)->getLatestRelease($slug, $extensionType);
                } catch (\Throwable) {
                    return null;
                }
            }

            return null;
        }

        // Try all enabled sources in priority order
        foreach ($this->getEnabledSources() as $source) {
            try {
                $release = $this->makeProvider($source)->getLatestRelease($slug, $extensionType);
                if ($release !== null) {
                    return $release;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    // ========================================
    // Download with Fallback
    // ========================================

    /**
     * Download an extension with fallback across sources
     *
     * Tries the specified source first. If not specified or if it fails,
     * iterates through all enabled sources in priority order.
     *
     * @return string Path to the downloaded ZIP file
     *
     * @throws RuntimeException When all sources fail
     */
    public function download(string $slug, string $extensionType = 'plugin', ?string $version = null, ?int $sourceId = null): string
    {
        return $this->downloadWithSource($slug, $extensionType, $version, $sourceId)['path'];
    }

    /**
     * Build the supply-chain linkage array for a freshly downloaded
     * extension. The admin install controllers persist this verbatim on
     * the Plugin/Theme record so subsequent update checks know which
     * source served the install and where to look on GitHub.
     *
     * Currently only GitHub-backed sources can supply the
     * `installed_from_url` and `source_repo` fields; other source types
     * fall back to leaving those empty rather than guessing.
     *
     * @return array{
     *     source_id: int,
     *     source_repo: ?string,
     *     installation_method: string,
     *     installed_from_url: ?string,
     * }
     */
    public function resolveSourceLinkage(ExtensionSource $source, string $slug, string $extensionType = 'plugin'): array
    {
        $provider = $this->makeProvider($source);

        $repo = null;
        $url = null;
        if ($provider instanceof GitHubSourceProvider) {
            $repo = $provider->buildRepoName($slug, $extensionType);
            $owner = $source->settings['owner'] ?? config('extension-sources.github.default_owner', 'Dixlase');
            $url = "https://github.com/{$owner}/{$repo}";
        }

        return [
            'source_id' => $source->id,
            'source_repo' => $repo,
            'installation_method' => $source->type,
            'installed_from_url' => $url,
        ];
    }

    /**
     * Same as download() but also returns the source that served the
     * extension. Callers that need to record the source linkage (e.g.
     * the admin install flow that has to persist source_id /
     * source_repo on the Plugin/Theme record) should use this instead
     * of download() so the metadata does not have to be re-discovered
     * later.
     *
     * @return array{path: string, source: ExtensionSource, slug: string, extension_type: string}
     */
    public function downloadWithSource(string $slug, string $extensionType = 'plugin', ?string $version = null, ?int $sourceId = null): array
    {
        $errors = [];

        // Try specific source first
        if ($sourceId !== null) {
            $source = ExtensionSource::query()->find($sourceId);
            if ($source && $source->is_enabled) {
                try {
                    $path = $this->downloadFromSource($source, $slug, $extensionType, $version);

                    return ['path' => $path, 'source' => $source, 'slug' => $slug, 'extension_type' => $extensionType];
                } catch (\Throwable $e) {
                    $errors[] = "[{$source->name}] {$e->getMessage()}";
                }
            }
        }

        // Fallback: try all enabled sources in priority order
        foreach ($this->getEnabledSources() as $source) {
            if ($source->id === $sourceId) {
                continue;
            }

            try {
                $path = $this->downloadFromSource($source, $slug, $extensionType, $version);

                return ['path' => $path, 'source' => $source, 'slug' => $slug, 'extension_type' => $extensionType];
            } catch (\Throwable $e) {
                $errors[] = "[{$source->name}] {$e->getMessage()}";
            }
        }

        $errorDetail = implode('; ', $errors);
        throw new RuntimeException("Failed to download {$slug} from all sources. Errors: {$errorDetail}");
    }

    /**
     * Download from a specific source, resolving version if needed
     */
    protected function downloadFromSource(ExtensionSource $source, string $slug, string $extensionType, ?string $version): string
    {
        $provider = $this->makeProvider($source);

        if ($version === null) {
            $release = $provider->getLatestRelease($slug, $extensionType);
            if ($release === null) {
                throw new RuntimeException("No release found for {$slug}.");
            }
            $version = $release->version;
        }

        return $provider->downloadRelease($slug, $version, $extensionType);
    }

    /**
     * Download the Dixlase Core release ZIP.
     *
     * Tries the source recorded on `core_releases.source_id` first, then
     * falls back to every enabled source in priority order.
     *
     * @param  ?string  $version  Defaults to `core_releases.available_version`
     * @return string Path to the downloaded ZIP file
     *
     * @throws RuntimeException When every source fails
     */
    public function downloadCore(?string $version = null): string
    {
        $coreState = \App\Models\CoreRelease::singleton();
        $version ??= $coreState->available_version;

        if ($version === null) {
            throw new RuntimeException('No core update is currently available.');
        }

        $errors = [];

        // Try the source remembered on the core_releases row first.
        if ($coreState->source_id !== null) {
            $source = ExtensionSource::query()->find($coreState->source_id);
            if ($source && $source->is_enabled) {
                try {
                    return $this->makeProvider($source)->downloadCoreRelease($version);
                } catch (\Throwable $e) {
                    $errors[] = "[{$source->name}] {$e->getMessage()}";
                }
            }
        }

        // Fallback: any other enabled source.
        foreach ($this->getEnabledSources() as $source) {
            if ($source->id === $coreState->source_id) {
                continue;
            }
            try {
                return $this->makeProvider($source)->downloadCoreRelease($version);
            } catch (\Throwable $e) {
                $errors[] = "[{$source->name}] {$e->getMessage()}";
            }
        }

        $detail = implode('; ', $errors) ?: 'no enabled sources';
        throw new RuntimeException("Failed to download core v{$version} from all sources. Errors: {$detail}");
    }

    /**
     * Load providers from config
     */
    protected function bootProviders(): void
    {
        $configProviders = config('extension-sources.providers', []);
        foreach ($configProviders as $type => $class) {
            $this->providers[$type] = $class;
        }
    }
}
