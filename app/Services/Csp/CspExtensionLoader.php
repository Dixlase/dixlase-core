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

namespace App\Services\Csp;

use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * CSP Extension Loader
 *
 * Reads CSP settings from plugin.json/theme.json of plugins and themes,
 * and automatically registers them in CspPolicyRegistry
 *
 * Priority:
 * 1. Denied domains (administrator settings) - highest priority block
 * 2. Declarations in plugin.json/theme.json - automatically allowed by default
 * 3. Trusted domains (administrator settings) - additional allowance
 */
class CspExtensionLoader
{
    protected CspPolicyRegistry $registry;

    /**
     * CSP directive mapping
     * plugin.json/theme.json key => CSP directive name
     */
    protected array $directiveMapping = [
        'scripts' => 'script-src',
        'styles' => 'style-src',
        'fonts' => 'font-src',
        'images' => 'img-src',
        'connect' => 'connect-src',
        'media' => 'media-src',
        'frames' => 'frame-src',
        'workers' => 'worker-src',
    ];

    public function __construct(CspPolicyRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Load CSP settings from active plugins and themes, and register them in the registry
     */
    public function loadAll(): void
    {
        $this->loadPlugins();
        $this->loadThemes();
    }

    /**
     * Load CSP settings from active plugins
     */
    public function loadPlugins(): void
    {
        try {
            $plugins = Plugin::whereNotNull('enabled_at')->get();

            foreach ($plugins as $plugin) {
                $this->loadPlugin($plugin->slug);
            }
        } catch (\Exception $e) {
            // Could not load plugins
        }
    }

    /**
     * Load CSP settings from active themes
     */
    public function loadThemes(): void
    {
        try {
            // Get the currently active theme
            $activeThemeId = \DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->value('value');

            if ($activeThemeId) {
                $theme = Theme::find($activeThemeId);
                if ($theme) {
                    $this->loadTheme($theme->slug);
                }
            }
        } catch (\Exception $e) {
            // Could not load themes
        }
    }

    /**
     * Load CSP settings from a specific plugin
     */
    public function loadPlugin(string $slug): array
    {
        $pluginPath = base_path('plugins/'.$slug);
        $jsonPath = $pluginPath.'/plugin.json';

        return $this->loadFromJson($jsonPath, 'plugin', $slug);
    }

    /**
     * Load CSP settings from a specific theme
     */
    public function loadTheme(string $slug): array
    {
        $themePath = base_path('themes/'.$slug);
        $jsonPath = $themePath.'/theme.json';

        return $this->loadFromJson($jsonPath, 'theme', $slug);
    }

    /**
     * Load CSP settings from JSON file and register them in the registry
     */
    protected function loadFromJson(string $jsonPath, string $type, string $slug): array
    {
        if (! File::exists($jsonPath)) {
            return [];
        }

        $cacheKey = "csp_extension_{$type}_{$slug}";

        // Attempt to retrieve from cache
        $directives = Cache::remember($cacheKey, 3600, function () use ($jsonPath) {
            $content = File::get($jsonPath);
            $json = json_decode($content, true);

            if (! $json || ! isset($json['csp'])) {
                return [];
            }

            return $this->parseCspConfig($json['csp']);
        });

        if (! empty($directives)) {
            $this->registry->addDirectives($directives, "{$type}:{$slug}");
        }

        return $directives;
    }

    /**
     * Parse CSP settings and convert to directive array
     */
    protected function parseCspConfig(array $cspConfig): array
    {
        $directives = [];

        // Process external_domains section
        if (isset($cspConfig['external_domains']) && is_array($cspConfig['external_domains'])) {
            foreach ($cspConfig['external_domains'] as $key => $domains) {
                if (! is_array($domains)) {
                    continue;
                }

                $directiveName = $this->directiveMapping[$key] ?? null;
                if ($directiveName) {
                    $directives[$directiveName] = array_merge(
                        $directives[$directiveName] ?? [],
                        $this->normalizeDomains($domains)
                    );
                }
            }
        }

        // Backward compatibility: also support legacy format keys
        foreach ($this->directiveMapping as $jsonKey => $directiveName) {
            if (isset($cspConfig[$jsonKey]) && is_array($cspConfig[$jsonKey])) {
                $directives[$directiveName] = array_merge(
                    $directives[$directiveName] ?? [],
                    $this->normalizeDomains($cspConfig[$jsonKey])
                );
            }
        }

        return $directives;
    }

    /**
     * Normalize domain array
     */
    protected function normalizeDomains(array $domains): array
    {
        return array_map(function ($domain) {
            // Add https if protocol is missing
            if (! preg_match('/^https?:\/\//', $domain)) {
                return 'https://'.$domain;
            }

            return $domain;
        }, $domains);
    }

    /**
     * Get extension CSP settings (for UI display)
     */
    public function getExtensionCspInfo(string $type, string $slug): array
    {
        $path = $type === 'plugin'
            ? base_path("plugins/{$slug}/plugin.json")
            : base_path("themes/{$slug}/theme.json");

        if (! File::exists($path)) {
            return [
                'has_csp' => false,
                'domains' => [],
            ];
        }

        $content = File::get($path);
        $json = json_decode($content, true);

        if (! $json || ! isset($json['csp'])) {
            return [
                'has_csp' => false,
                'domains' => [],
            ];
        }

        $directives = $this->parseCspConfig($json['csp']);
        $allDomains = [];

        foreach ($directives as $directive => $domains) {
            foreach ($domains as $domain) {
                $allDomains[$domain] = $allDomains[$domain] ?? [];
                $allDomains[$domain][] = $directive;
            }
        }

        return [
            'has_csp' => true,
            'domains' => $allDomains,
            'directives' => $directives,
        ];
    }

    /**
     * Check extension CSP domains against blocklist
     *
     * @param  string  $type  'plugin' or 'theme'
     * @param  string  $slug  Slug
     * @return array Information about domains matched in blocklist
     */
    public function checkAgainstBlocklist(string $type, string $slug): array
    {
        $blocklistService = app(CspBlocklistService::class);

        // Skip if blocklist checking is disabled
        if (! $blocklistService->isBlocklistCheckEnabled()) {
            return [];
        }

        // Get extension CSP information
        $cspInfo = $this->getExtensionCspInfo($type, $slug);

        if (! $cspInfo['has_csp'] || empty($cspInfo['domains'])) {
            return [];
        }

        // Extract domain list
        $domains = array_keys($cspInfo['domains']);

        // Check against blocklist
        return $blocklistService->checkDomainsAgainstBlocklist($domains);
    }

    /**
     * Check multiple extensions' CSP domains against blocklist
     *
     * @param  array  $extensions  [['type' => 'plugin', 'slug' => 'xxx'], ...]
     * @return array Match results per extension
     */
    public function checkMultipleAgainstBlocklist(array $extensions): array
    {
        $results = [];

        foreach ($extensions as $ext) {
            $type = $ext['type'] ?? '';
            $slug = $ext['slug'] ?? '';

            if ($type && $slug) {
                $matches = $this->checkAgainstBlocklist($type, $slug);
                if (! empty($matches)) {
                    $results[] = [
                        'type' => $type,
                        'slug' => $slug,
                        'matches' => $matches,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Check if extension requires inline JS
     *
     * @param  string  $type  'plugin' or 'theme'
     * @param  string  $slug  Slug
     */
    public function requiresInlineJs(string $type, string $slug): bool
    {
        $path = $type === 'plugin'
            ? base_path("plugins/{$slug}/plugin.json")
            : base_path("themes/{$slug}/theme.json");

        if (! File::exists($path)) {
            return false;
        }

        try {
            $content = File::get($path);
            $json = json_decode($content, true);

            return (bool) ($json['requires_inline_js'] ?? false);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get extension CSP compatibility status
     *
     * @param  string  $type  'plugin' or 'theme'
     * @param  string  $slug  Slug
     * @return array CSP compatibility information
     */
    public function getCspCompatibility(string $type, string $slug): array
    {
        $path = $type === 'plugin'
            ? base_path("plugins/{$slug}/plugin.json")
            : base_path("themes/{$slug}/theme.json");

        if (! File::exists($path)) {
            return [
                'status' => 'unknown',
                'requires_inline_js' => false,
                'has_csp_config' => false,
                'csp_ready' => false,
            ];
        }

        try {
            $content = File::get($path);
            $json = json_decode($content, true);

            $requiresInlineJs = (bool) ($json['requires_inline_js'] ?? false);
            $hasCspConfig = isset($json['csp']);

            // CSP Ready = no inline JS required AND has CSP settings (or doesn't use external resources)
            $cspReady = ! $requiresInlineJs;

            if ($requiresInlineJs) {
                $status = 'inline_required';
            } elseif ($hasCspConfig) {
                $status = 'csp_ready';
            } else {
                $status = 'compatible';
            }

            return [
                'status' => $status,
                'requires_inline_js' => $requiresInlineJs,
                'has_csp_config' => $hasCspConfig,
                'csp_ready' => $cspReady,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unknown',
                'requires_inline_js' => false,
                'has_csp_config' => false,
                'csp_ready' => false,
            ];
        }
    }

    /**
     * Check if extension can be activated in strict mode
     *
     * @param  string  $type  'plugin' or 'theme'
     * @param  string  $slug  Slug
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canEnableInStrictMode(string $type, string $slug): array
    {
        $compatibility = $this->getCspCompatibility($type, $slug);

        if ($compatibility['requires_inline_js']) {
            return [
                'allowed' => false,
                'reason' => 'requires_inline_js',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Clear cache
     */
    public function clearCache(?string $type = null, ?string $slug = null): void
    {
        if ($type && $slug) {
            Cache::forget("csp_extension_{$type}_{$slug}");
        } else {
            // Full cache clearing must be done individually
            // Get and clear the plugin and theme list
            try {
                $plugins = Plugin::all();
                foreach ($plugins as $plugin) {
                    Cache::forget("csp_extension_plugin_{$plugin->slug}");
                }

                $themes = Theme::all();
                foreach ($themes as $theme) {
                    Cache::forget("csp_extension_theme_{$theme->slug}");
                }
            } catch (\Exception $e) {
                // Ignore if database is not configured
            }
        }
    }

    /**
     * Add or update CSP section in plugin.json/theme.json
     */
    public function updateCspConfig(string $type, string $slug, array $cspConfig): bool
    {
        $path = $type === 'plugin'
            ? base_path("plugins/{$slug}/plugin.json")
            : base_path("themes/{$slug}/theme.json");

        if (! File::exists($path)) {
            return false;
        }

        $content = File::get($path);
        $json = json_decode($content, true);

        if (! $json) {
            return false;
        }

        $json['csp'] = $cspConfig;

        $result = File::put($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if ($result) {
            $this->clearCache($type, $slug);
        }

        return $result !== false;
    }
}
