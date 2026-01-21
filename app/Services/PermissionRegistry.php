<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

declare(strict_types=1);

namespace App\Services;

use App\Enums\MemberRole;
use App\Models\RolePermissionOverride;
use Illuminate\Support\Facades\Cache;

/**
 * Permission Registry Service
 * 
 * デフォルト権限（config/roles.php）とオーバーライド（DB）を合成して
 * 実効権限（effective）を提供するサービス
 */
class PermissionRegistry
{
    /**
     * キャッシュTTL（秒）
     */
    protected const CACHE_TTL = 300;

    /**
     * キャッシュキープレフィックス
     */
    protected const CACHE_PREFIX = 'permission_registry:';

    /**
     * 登録されたプラグイン権限定義
     * @var array<string, array>
     */
    protected static array $pluginPermissions = [];

    /**
     * プラグインの権限定義を登録
     * ServiceProviderのregister()で呼び出す
     */
    public static function registerPlugin(string $pluginSlug, array $permissions): void
    {
        self::$pluginPermissions[$pluginSlug] = $permissions;
        self::clearCache();
    }

    /**
     * プラグインの権限定義を登録解除
     */
    public static function unregisterPlugin(string $pluginSlug): void
    {
        unset(self::$pluginPermissions[$pluginSlug]);
        self::clearCache();
    }

    /**
     * 実効権限を取得（コア機能）
     * 
     * @param string $menuKey メニューキー（例：settings.base）
     * @return array{access_roles: int, view_roles: int}|null
     */
    public static function getEffective(string $menuKey): ?array
    {
        $cacheKey = self::CACHE_PREFIX . 'core:' . $menuKey;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($menuKey) {
            // デフォルト値を取得
            $default = config("roles.permissions.{$menuKey}");
            
            if ($default === null) {
                return null;
            }
            
            // オーバーライドを取得
            $override = RolePermissionOverride::getCoreOverride($menuKey);
            
            if ($override) {
                return [
                    'access_roles' => $override->access_roles ?? $default['access_roles'],
                    'view_roles' => $override->view_roles ?? $default['view_roles'],
                    'is_overridden' => true,
                    'default_access_roles' => $default['access_roles'],
                    'default_view_roles' => $default['view_roles'],
                ];
            }
            
            return [
                'access_roles' => $default['access_roles'],
                'view_roles' => $default['view_roles'],
                'is_overridden' => false,
                'default_access_roles' => $default['access_roles'],
                'default_view_roles' => $default['view_roles'],
            ];
        });
    }

    /**
     * 実効権限を取得（プラグイン機能）
     * 
     * @param string $pluginSlug プラグインスラッグ
     * @param string $menuKey メニューキー
     * @return array{access_roles: int, view_roles: int}|null
     */
    public static function getPluginEffective(string $pluginSlug, string $menuKey): ?array
    {
        $cacheKey = self::CACHE_PREFIX . "plugin:{$pluginSlug}:{$menuKey}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pluginSlug, $menuKey) {
            // プラグインのデフォルト値を取得
            $default = self::getPluginDefault($pluginSlug, $menuKey);
            
            if ($default === null) {
                // デフォルトがない場合はADMIN権限をデフォルトとする
                $default = [
                    'access_roles' => MemberRole::ADMIN->value,
                    'view_roles' => MemberRole::ADMIN->value,
                ];
            }
            
            // オーバーライドを取得
            $override = RolePermissionOverride::getPluginOverride($pluginSlug, $menuKey);
            
            if ($override) {
                return [
                    'access_roles' => $override->access_roles ?? $default['access_roles'],
                    'view_roles' => $override->view_roles ?? $default['view_roles'],
                    'is_overridden' => true,
                    'default_access_roles' => $default['access_roles'],
                    'default_view_roles' => $default['view_roles'],
                ];
            }
            
            return [
                'access_roles' => $default['access_roles'],
                'view_roles' => $default['view_roles'],
                'is_overridden' => false,
                'default_access_roles' => $default['access_roles'],
                'default_view_roles' => $default['view_roles'],
            ];
        });
    }

    /**
     * プラグインのデフォルト権限を取得
     */
    protected static function getPluginDefault(string $pluginSlug, string $menuKey): ?array
    {
        // メモリ上の登録から取得
        if (isset(self::$pluginPermissions[$pluginSlug][$menuKey])) {
            return self::$pluginPermissions[$pluginSlug][$menuKey];
        }
        
        // プラグインのconfig/roles.phpから取得を試みる
        $pluginRolesPath = base_path("plugins/{$pluginSlug}/config/roles.php");
        if (file_exists($pluginRolesPath)) {
            $pluginRoles = require $pluginRolesPath;
            if (isset($pluginRoles['permissions'][$menuKey])) {
                return $pluginRoles['permissions'][$menuKey];
            }
        }
        
        return null;
    }

    /**
     * コア機能の全権限定義を取得（デフォルト＋オーバーライド合成済み）
     */
    public static function getAllCorePermissions(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all_core';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $defaults = config('roles.permissions', []);
            $overrides = RolePermissionOverride::getAllCoreOverrides()->keyBy('menu_key');
            
            $result = [];
            foreach ($defaults as $menuKey => $default) {
                $override = $overrides->get($menuKey);
                
                $result[$menuKey] = [
                    'access_roles' => $override?->access_roles ?? $default['access_roles'],
                    'view_roles' => $override?->view_roles ?? $default['view_roles'],
                    'is_overridden' => $override !== null,
                    'default_access_roles' => $default['access_roles'],
                    'default_view_roles' => $default['view_roles'],
                ];
            }
            
            return $result;
        });
    }

    /**
     * プラグインの全権限定義を取得（デフォルト＋オーバーライド合成済み）
     */
    public static function getAllPluginPermissions(string $pluginSlug): array
    {
        $cacheKey = self::CACHE_PREFIX . "all_plugin:{$pluginSlug}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pluginSlug) {
            // プラグインのデフォルト権限を取得
            $defaults = self::$pluginPermissions[$pluginSlug] ?? [];
            
            // config/roles.phpからも取得
            $pluginRolesPath = base_path("plugins/{$pluginSlug}/config/roles.php");
            if (file_exists($pluginRolesPath)) {
                $pluginRoles = require $pluginRolesPath;
                $defaults = array_merge($defaults, $pluginRoles['permissions'] ?? []);
            }
            
            $overrides = RolePermissionOverride::getAllPluginOverrides($pluginSlug)->keyBy('menu_key');
            
            $result = [];
            foreach ($defaults as $menuKey => $default) {
                $override = $overrides->get($menuKey);
                
                $result[$menuKey] = [
                    'access_roles' => $override?->access_roles ?? $default['access_roles'],
                    'view_roles' => $override?->view_roles ?? $default['view_roles'],
                    'is_overridden' => $override !== null,
                    'default_access_roles' => $default['access_roles'],
                    'default_view_roles' => $default['view_roles'],
                ];
            }
            
            return $result;
        });
    }

    /**
     * 全プラグインの権限定義を取得
     */
    public static function getAllPluginsPermissions(): array
    {
        $result = [];
        
        // メモリ上の登録
        foreach (array_keys(self::$pluginPermissions) as $pluginSlug) {
            $result[$pluginSlug] = self::getAllPluginPermissions($pluginSlug);
        }
        
        // pluginsディレクトリからも取得
        $pluginsPath = base_path('plugins');
        if (is_dir($pluginsPath)) {
            foreach (glob($pluginsPath . '/*/config/roles.php') as $rolesFile) {
                $pluginSlug = basename(dirname(dirname($rolesFile)));
                if (!isset($result[$pluginSlug])) {
                    $result[$pluginSlug] = self::getAllPluginPermissions($pluginSlug);
                }
            }
        }
        
        return $result;
    }

    /**
     * ユーザーがメニューにアクセス可能かチェック（コア機能）
     */
    public static function canAccess(string $menuKey, MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }
        
        $effective = self::getEffective($menuKey);
        
        if ($effective === null) {
            return false;
        }
        
        return $userRole->value >= $effective['access_roles'];
    }

    /**
     * ユーザーがメニューを閲覧可能かチェック（コア機能）
     */
    public static function canView(string $menuKey, MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }
        
        $effective = self::getEffective($menuKey);
        
        if ($effective === null) {
            return false;
        }
        
        return $userRole->value >= $effective['view_roles'];
    }

    /**
     * ユーザーがプラグインメニューにアクセス可能かチェック
     */
    public static function canAccessPlugin(string $pluginSlug, string $menuKey, MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }
        
        $effective = self::getPluginEffective($pluginSlug, $menuKey);
        
        if ($effective === null) {
            // デフォルトがない場合はADMIN以上でアクセス可能
            return $userRole->value >= MemberRole::ADMIN->value;
        }
        
        return $userRole->value >= $effective['access_roles'];
    }

    /**
     * ユーザーがプラグインメニューを閲覧可能かチェック
     */
    public static function canViewPlugin(string $pluginSlug, string $menuKey, MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }
        
        $effective = self::getPluginEffective($pluginSlug, $menuKey);
        
        if ($effective === null) {
            return $userRole->value >= MemberRole::ADMIN->value;
        }
        
        return $userRole->value >= $effective['view_roles'];
    }

    /**
     * キャッシュをクリア
     */
    public static function clearCache(): void
    {
        // コア権限キャッシュをクリア
        $corePermissions = config('roles.permissions', []);
        foreach (array_keys($corePermissions) as $menuKey) {
            Cache::forget(self::CACHE_PREFIX . 'core:' . $menuKey);
        }
        Cache::forget(self::CACHE_PREFIX . 'all_core');
        
        // プラグイン権限キャッシュをクリア
        foreach (array_keys(self::$pluginPermissions) as $pluginSlug) {
            Cache::forget(self::CACHE_PREFIX . "all_plugin:{$pluginSlug}");
        }
    }

    /**
     * 特定のメニューキーのキャッシュをクリア
     */
    public static function clearMenuCache(string $menuKey, ?string $pluginSlug = null): void
    {
        if ($pluginSlug) {
            Cache::forget(self::CACHE_PREFIX . "plugin:{$pluginSlug}:{$menuKey}");
            Cache::forget(self::CACHE_PREFIX . "all_plugin:{$pluginSlug}");
        } else {
            Cache::forget(self::CACHE_PREFIX . 'core:' . $menuKey);
            Cache::forget(self::CACHE_PREFIX . 'all_core');
        }
    }

    /**
     * 登録されているプラグインスラッグ一覧を取得
     */
    public static function getRegisteredPlugins(): array
    {
        return array_keys(self::$pluginPermissions);
    }
}
