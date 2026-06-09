<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Services;

use App\Contracts\LegalPage\LegalPageServiceInterface;
use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Legal page registry service
 *
 * Manages Core and plugin legal page types in a unified manner,
 * stores URLs in the dls_site_settings table
 */
class LegalPageService implements LegalPageServiceInterface
{
    /** @var string settings key prefix */
    private const SETTING_PREFIX = 'legal_page_url:';

    /** @var array<string, array{name: string, description: string, required: bool, icon: string, required_by?: list<string>}>|null */
    private ?array $mergedPageTypes = null;

    public function __construct(
        private SiteSettingRepositoryInterface $settingRepository,
    ) {}

    /**
     * Get merged list of Core + plugin page types
     *
     * @return array<string, array{name: string, description: string, required: bool, icon: string, required_by?: list<string>}>
     */
    public function getPageTypes(): array
    {
        if ($this->mergedPageTypes !== null) {
            return $this->mergedPageTypes;
        }

        $coreTypes = config('admin.legal', []);

        // Load plugin overrides
        $pluginOverrides = $this->loadPluginOverrides();

        // Merge process
        $merged = $coreTypes;

        foreach ($pluginOverrides as $slug => $override) {
            if (isset($merged[$slug])) {
                // Override existing entries
                if (! empty($override['required'])) {
                    $merged[$slug]['required'] = true;
                    $merged[$slug]['required_by'] = array_merge(
                        $merged[$slug]['required_by'] ?? [],
                        $override['required_by'] ?? []
                    );
                }
                // name/description/icon can be overridden by plugins
                if (isset($override['name'])) {
                    $merged[$slug]['name'] = $override['name'];
                }
                if (isset($override['description'])) {
                    $merged[$slug]['description'] = $override['description'];
                }
                if (isset($override['icon'])) {
                    $merged[$slug]['icon'] = $override['icon'];
                }
            } else {
                // New page types unique to plugins
                $merged[$slug] = $override;
            }
        }

        $this->mergedPageTypes = $merged;

        return $this->mergedPageTypes;
    }

    /**
     * Determine if the specified page type is required
     */
    public function isRequired(string $slug): bool
    {
        $types = $this->getPageTypes();

        return $types[$slug]['required'] ?? false;
    }

    /**
     * Determine if the URL for the specified page type is configured
     */
    public function exists(string $slug): bool
    {
        $url = $this->settingRepository->get(self::SETTING_PREFIX.$slug);

        return $url !== null && $url !== '';
    }

    /**
     * Get the URL for the specified page type
     */
    public function url(string $slug): ?string
    {
        $value = $this->settingRepository->get(self::SETTING_PREFIX.$slug);

        return ($value !== null && $value !== '') ? $value : null;
    }

    /**
     * Get list of required page types without configured URLs
     *
     * @return array<string, array{name: string, description: string, required: bool, icon: string}>
     */
    public function missingRequired(): array
    {
        $missing = [];

        foreach ($this->getPageTypes() as $slug => $type) {
            if (! empty($type['required']) && ! $this->exists($slug)) {
                $missing[$slug] = $type;
            }
        }

        return $missing;
    }

    /**
     * Set the URL for the specified page type (null to delete)
     */
    public function setUrl(string $slug, ?string $url): void
    {
        $key = self::SETTING_PREFIX.$slug;

        if ($url === null || $url === '') {
            $this->settingRepository->delete($key);
        } else {
            $this->settingRepository->set($key, $url);
        }
    }

    /**
     * Load legal page settings from active plugins
     *
     * @return array<string, array<string, mixed>>
     */
    private function loadPluginOverrides(): array
    {
        $overrides = [];

        try {
            $plugins = DB::table('plugins')
                ->whereNotNull('enabled_at')
                ->get();
        } catch (\Exception $e) {
            Log::warning('LegalPageService: Failed to load plugin table: '.$e->getMessage());

            return [];
        }

        foreach ($plugins as $plugin) {
            $configPath = base_path("plugins/{$plugin->directory}/config/admin/legal.php");

            if (! File::exists($configPath)) {
                continue;
            }

            try {
                $pluginConfig = require $configPath;

                if (! is_array($pluginConfig)) {
                    Log::warning("LegalPageService: Invalid legal.php format: {$plugin->slug}");

                    continue;
                }

                foreach ($pluginConfig as $slug => $pageConfig) {
                    if (! is_array($pageConfig)) {
                        continue;
                    }

                    // Add plugin name to required_by
                    if (! empty($pageConfig['required'])) {
                        $pageConfig['required_by'] = [$plugin->name];
                    }

                    if (isset($overrides[$slug])) {
                        // When the same slug is required by multiple plugins
                        if (! empty($pageConfig['required'])) {
                            $overrides[$slug]['required'] = true;
                            $overrides[$slug]['required_by'] = array_merge(
                                $overrides[$slug]['required_by'] ?? [],
                                $pageConfig['required_by']
                            );
                        }
                    } else {
                        $overrides[$slug] = $pageConfig;
                    }
                }
            } catch (\Exception $e) {
                Log::error("LegalPageService: Failed to load configuration for plugin {$plugin->slug}: {$e->getMessage()}");
            }
        }

        return $overrides;
    }
}
