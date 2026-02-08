<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
     * コアのクリーンアップ設定を取得
     */
    public function getCoreCleanupConfig(): array
    {
        $config = config('admin.database-cleanup', []);
        
        return collect($config)
            ->filter(fn($item) => $item['enabled'] ?? true)
            ->toArray();
    }

    /**
     * プラグインのクリーンアップ設定を取得
     */
    public function getPluginCleanupConfig(): array
    {
        $pluginCleanupConfig = [];
        
        $plugins = DB::table('plugins')
            ->whereNotNull('enabled_at')
            ->get();
        
        foreach ($plugins as $plugin) {
            $configPath = base_path("plugins/{$plugin->directory}/config/database-cleanup.php");
            
            if (!File::exists($configPath)) {
                continue;
            }
            
            try {
                $pluginConfig = require $configPath;
                
                if (!is_array($pluginConfig)) {
                    Log::warning("Invalid database-cleanup.php format in plugin: {$plugin->slug}");
                    continue;
                }
                
                foreach ($pluginConfig as $key => $tableConfig) {
                    if (!isset($tableConfig['table']) || !($tableConfig['enabled'] ?? true)) {
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
     * すべてのクリーンアップ設定を取得
     */
    public function getAllCleanupConfig(): array
    {
        return array_merge(
            $this->getCoreCleanupConfig(),
            $this->getPluginCleanupConfig()
        );
    }

    /**
     * 特定のクリーンアップ設定を取得
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
     * データベースクリーンアップを実行
     */
    public function cleanup(string $type, int $days, bool $force = false): array
    {
        $config = $this->getCleanupConfig($type);
        
        if (!$config) {
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
                if (!$force) {
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
            
            // 追加条件の適用
            if (isset($config['additional_conditions'])) {
                if (is_callable($config['additional_conditions'])) {
                    $query = $config['additional_conditions']($query);
                } elseif (is_string($config['additional_conditions'])) {
                    $query = $this->applyAdditionalCondition($query, $config['additional_conditions'], $table);
                }
            }
            
            $count = $query->delete();
            
            Log::info("Database cleanup executed", [
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
            Log::error("Database cleanup failed", [
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
     * すべてのテーブルをクリーンアップ
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
     * 管理画面用の表示情報を取得
     */
    public function getCleanupInfo(): array
    {
        $locale = app()->getLocale();
        $coreConfig = $this->getCoreCleanupConfig();
        $info = [];
        
        foreach ($coreConfig as $key => $config) {
            // 名前を取得
            $name = $config['name'] ?? '';
            if (is_string($name) && str_contains($name, '.')) {
                $name = __($name);
            } elseif (is_array($name)) {
                $name = $name[$locale] ?? $name['en'] ?? $name['ja'] ?? '';
            }
            
            // 説明を取得
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
     * プラグイン用の表示情報を取得
     */
    public function getPluginCleanupInfo(): array
    {
        $locale = app()->getLocale();
        $pluginConfig = $this->getPluginCleanupConfig();
        $info = [];
        
        foreach ($pluginConfig as $key => $config) {
            // 名前を取得
            $name = $config['name'] ?? '';
            if (is_string($name) && str_contains($name, '.')) {
                $name = __($name);
            } elseif (is_array($name)) {
                $name = $name[$locale] ?? $name['en'] ?? $name['ja'] ?? '';
            }
            
            // 説明を取得
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
     * 追加条件を適用
     */
    protected function applyAdditionalCondition($query, string $condition, string $table)
    {
        switch ($condition) {
            case 'expired':
                // 2FAトークン: 有効期限切れも削除
                if ($table === 'members_two_fa_tokens') {
                    $query->orWhere('expires_at', '<', now());
                }
                break;
                
            case 'used':
                // リカバリーコード: 使用済みも削除
                if ($table === 'members_recovery_codes') {
                    $query->orWhereNotNull('used_at');
                }
                break;
                
            case 'unused':
                // パスキー: 未使用かつ90日以上経過したものも削除
                if ($table === 'webauthn_credentials') {
                    $query->orWhere(function($q) {
                        $q->whereNull('last_used_at')
                          ->where('created_at', '<', now()->subDays(90));
                    });
                }
                break;
                
            case 'expired_cache':
                // キャッシュ: 有効期限切れのみ削除
                if ($table === 'cache') {
                    $query->where('expiration', '<', now()->timestamp);
                }
                break;
        }
        
        return $query;
    }
}
