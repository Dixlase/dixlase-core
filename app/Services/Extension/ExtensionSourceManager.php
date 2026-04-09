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
use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Extension Source Manager
 *
 * 拡張機能ソースの管理、プロバイダーレジストリ、更新チェック、
 * 複数ソース間のフォールバックダウンロードを管理する中央サービス。
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
     * @return Collection<int, ExtensionSource>
     */
    public function getEnabledSources(): Collection
    {
        return ExtensionSource::query()->enabled()->get();
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
     * ソースが公式かどうかを判定する（ハードコード + Ed25519 署名併用）
     */
    public function isOfficialSource(ExtensionSource $source): bool
    {
        // 1. コアがプリセットしたソースタイプのハードコードチェック
        $preset = config("extension-sources.presets.{$source->type}");
        if ($preset && ($preset['is_official'] ?? false)) {
            return true;
        }

        // 2. Ed25519 署名による検証
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

    // ========================================
    // Update Checking
    // ========================================

    /**
     * Check all installed plugins and themes for available updates
     *
     * @return array{plugins: array<int, array{slug: string, current: string, available: string, source_id: int}>, themes: array<int, array{slug: string, current: string, available: string, source_id: int}>}
     */
    public function checkUpdates(): array
    {
        $pluginUpdates = $this->checkPluginUpdates();
        $themeUpdates = $this->checkThemeUpdates();

        return [
            'plugins' => $pluginUpdates,
            'themes' => $themeUpdates,
        ];
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
        $errors = [];

        // Try specific source first
        if ($sourceId !== null) {
            $source = ExtensionSource::query()->find($sourceId);
            if ($source && $source->is_enabled) {
                try {
                    return $this->downloadFromSource($source, $slug, $extensionType, $version);
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
                return $this->downloadFromSource($source, $slug, $extensionType, $version);
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
