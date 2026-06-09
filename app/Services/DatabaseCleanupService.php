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

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DatabaseCleanupService
{
    /**
     * Get Core cleanup settings
     */
    public function getCoreCleanupConfig(): array
    {
        $config = config('admin.database-cleanup', []);

        return collect($config)
            ->filter(fn ($item) => $item['enabled'] ?? true)
            ->toArray();
    }

    /**
     * Get plugin cleanup settings
     */
    public function getPluginCleanupConfig(): array
    {
        $pluginCleanupConfig = [];

        $plugins = DB::table('plugins')
            ->whereNotNull('enabled_at')
            ->get();

        foreach ($plugins as $plugin) {
            $configPath = base_path("plugins/{$plugin->directory}/config/database-cleanup.php");

            if (! File::exists($configPath)) {
                continue;
            }

            try {
                $pluginConfig = require $configPath;

                if (! is_array($pluginConfig)) {
                    Log::warning("Invalid database-cleanup.php format in plugin: {$plugin->slug}");

                    continue;
                }

                foreach ($pluginConfig as $key => $tableConfig) {
                    if (! isset($tableConfig['table']) || ! ($tableConfig['enabled'] ?? true)) {
                        continue;
                    }

                    $pluginCleanupConfig["plugin:{$plugin->slug}:{$key}"] = array_merge($tableConfig, [
                        'plugin_name' => $plugin->name,
                        'plugin_slug' => $plugin->slug,
                        'is_plugin' => true,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to load database-cleanup.php from plugin {$plugin->slug}: {$e->getMessage()}");

                continue;
            }
        }

        return $pluginCleanupConfig;
    }

    /**
     * Get all cleanup settings
     */
    public function getAllCleanupConfig(): array
    {
        return array_merge(
            $this->getCoreCleanupConfig(),
            $this->getPluginCleanupConfig()
        );
    }

    /**
     * Get specific cleanup settings
     */
    public function getCleanupConfig(string $type): ?array
    {
        if (str_starts_with($type, 'plugin:')) {
            $pluginConfig = $this->getPluginCleanupConfig();

            return $pluginConfig[$type] ?? null;
        }

        $coreConfig = $this->getCoreCleanupConfig();

        return $coreConfig[$type] ?? null;
    }

    /**
     * Execute database cleanup
     */
    public function cleanup(string $type, int $days, bool $force = false): array
    {
        $config = $this->getCleanupConfig($type);

        if (! $config) {
            return [
                'success' => false,
                'message' => "Cleanup configuration not found for type: {$type}",
                'count' => 0,
            ];
        }

        try {
            $table = $config['table'];
            $dateColumn = $config['date_column'];
            $dateColumnType = $config['date_column_type'] ?? 'datetime';

            $query = DB::table($table);

            if ($days === 0) {
                if (! $force) {
                    return [
                        'success' => false,
                        'message' => 'Force flag required to delete all records',
                        'count' => 0,
                    ];
                }
            } else {
                if ($dateColumnType === 'timestamp') {
                    $cutoffDate = now()->subDays($days)->timestamp;
                } else {
                    $cutoffDate = now()->subDays($days);
                }

                $query->where($dateColumn, '<', $cutoffDate);
            }

            // Apply additional conditions
            if (isset($config['additional_conditions'])) {
                if (is_callable($config['additional_conditions'])) {
                    $query = $config['additional_conditions']($query);
                } elseif (is_string($config['additional_conditions'])) {
                    $query = $this->applyAdditionalCondition($query, $config['additional_conditions'], $table);
                }
            }

            $count = $query->delete();

            Log::info('Database cleanup executed', [
                'type' => $type,
                'table' => $table,
                'days' => $days,
                'deleted_count' => $count,
            ]);

            return [
                'success' => true,
                'message' => "Successfully deleted {$count} records",
                'count' => $count,
            ];
        } catch (\Exception $e) {
            Log::error('Database cleanup failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'count' => 0,
            ];
        }
    }

    /**
     * Clean up all tables
     */
    public function cleanupAll(int $days, bool $force = false): array
    {
        $results = [];
        $totalCount = 0;
        $coreConfig = $this->getCoreCleanupConfig();

        foreach ($coreConfig as $type => $config) {
            if ($type === 'cache_data' && $days > 0) {
                continue;
            }

            $result = $this->cleanup($type, $days, $force);
            $results[$type] = $result;
            $totalCount += $result['count'];
        }

        return [
            'success' => true,
            'message' => "Successfully deleted {$totalCount} records from all tables",
            'count' => $totalCount,
            'details' => $results,
        ];
    }

    /**
     * Get display information for admin panel
     */
    public function getCleanupInfo(): array
    {
        $locale = app()->getLocale();
        $coreConfig = $this->getCoreCleanupConfig();
        $info = [];

        foreach ($coreConfig as $key => $config) {
            // Get name
            $name = $config['name'] ?? '';
            if (is_string($name) && str_contains($name, '.')) {
                $name = __($name);
            } elseif (is_array($name)) {
                $name = $name[$locale] ?? $name['en'] ?? $name['ja'] ?? '';
            }

            // Get description
            $description = $config['description'] ?? '';
            if (is_string($description) && str_contains($description, '.')) {
                $description = __($description);
            } elseif (is_array($description)) {
                $description = $description[$locale] ?? $description['en'] ?? $description['ja'] ?? '';
            }

            $info[$key] = [
                'name' => $name,
                'description' => $description,
                'default_days' => $config['default_days'],
                'table' => $config['table'],
            ];
        }

        return $info;
    }

    /**
     * Get display information for plugin
     */
    public function getPluginCleanupInfo(): array
    {
        $locale = app()->getLocale();
        $pluginConfig = $this->getPluginCleanupConfig();
        $info = [];

        foreach ($pluginConfig as $key => $config) {
            // Get name
            $name = $config['name'] ?? '';
            if (is_string($name) && str_contains($name, '.')) {
                $name = __($name);
            } elseif (is_array($name)) {
                $name = $name[$locale] ?? $name['en'] ?? $name['ja'] ?? '';
            }

            // Get description
            $description = $config['description'] ?? '';
            if (is_string($description) && str_contains($description, '.')) {
                $description = __($description);
            } elseif (is_array($description)) {
                $description = $description[$locale] ?? $description['en'] ?? $description['ja'] ?? '';
            }

            $info[$key] = [
                'plugin_name' => $config['plugin_name'],
                'plugin_slug' => $config['plugin_slug'],
                'name' => $name ?: $config['table'],
                'description' => $description,
                'default_days' => $config['default_days'],
                'table' => $config['table'],
            ];
        }

        return $info;
    }

    /**
     * Apply additional conditions
     */
    protected function applyAdditionalCondition($query, string $condition, string $table)
    {
        switch ($condition) {
            case 'expired':
                // 2FA tokens: delete expired ones as well
                if ($table === 'members_two_fa_tokens') {
                    $query->orWhere('expires_at', '<', now());
                }
                break;

            case 'used':
                // Recovery codes: delete used ones as well
                if ($table === 'members_recovery_codes') {
                    $query->orWhereNotNull('used_at');
                }
                break;

            case 'unused':
                // Passkeys: delete unused ones older than 90 days as well
                if ($table === 'webauthn_credentials') {
                    $query->orWhere(function ($q) {
                        $q->whereNull('last_used_at')
                            ->where('created_at', '<', now()->subDays(90));
                    });
                }
                break;

            case 'expired_cache':
                // Cache: delete expired ones only
                if ($table === 'cache') {
                    $query->where('expiration', '<', now()->timestamp);
                }
                break;
        }

        return $query;
    }
}
