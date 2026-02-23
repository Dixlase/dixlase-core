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
 * @api プラグイン/テーマから直接DIで使用可能な安定APIです
 *
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
     *
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
     * @param  string  $menuKey  メニューキー（例：settings.base.index）
     * @return array{access_roles: int, view_roles: int}|null
     */
    public static function getEffective(string $menuKey): ?array
    {
        $cacheKey = self::CACHE_PREFIX.'core:'.$menuKey;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($menuKey) {
            // デフォルト値を取得（ネスト構造から取得）
            $default = self::getDefaultFromNestedConfig($menuKey);

            if ($default === null) {
                return;
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
     * ネスト構造のconfigからドット記法のキーで権限を取得
     *
     * @param  string  $menuKey  ドット記法のメニューキー（例：settings.base.index）
     * @return array{access_roles: int, view_roles: int}|null
     */
    protected static function getDefaultFromNestedConfig(string $menuKey): ?array
    {
        $permissions = config('roles.permissions', []);
        $parts = explode('.', $menuKey);

        $current = $permissions;
        foreach ($parts as $part) {
            if (! is_array($current)) {
                return null;
            }

            // 直接キーがある場合
            if (isset($current[$part])) {
                // access_rolesがあれば権限定義
                if (isset($current[$part]['access_roles'])) {
                    return $current[$part];
                }
                // childrenがあればさらに深く
                if (isset($current[$part]['children'])) {
                    $current = $current[$part]['children'];

                    continue;
                }
                // それ以外は次の階層へ
                $current = $current[$part];

                continue;
            }

            return null;
        }

        // 最終的にaccess_rolesがあれば権限定義
        if (is_array($current) && isset($current['access_roles'])) {
            return $current;
        }

        return null;
    }

    /**
     * 実効権限を取得（プラグイン機能）
     *
     * @param  string  $pluginSlug  プラグインスラッグ
     * @param  string  $menuKey  メニューキー
     * @return array{access_roles: int, view_roles: int}|null
     */
    public static function getPluginEffective(string $pluginSlug, string $menuKey): ?array
    {
        $cacheKey = self::CACHE_PREFIX."plugin:{$pluginSlug}:{$menuKey}";

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
     * プラグインのデフォルト権限を取得（ネスト構造対応）
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
            $permissions = $pluginRoles['permissions'] ?? [];

            // ネスト構造から取得
            return self::getDefaultFromNestedArray($permissions, $menuKey);
        }

        return null;
    }

    /**
     * ネスト構造の配列からドット記法のキーで権限を取得
     */
    protected static function getDefaultFromNestedArray(array $permissions, string $menuKey): ?array
    {
        $parts = explode('.', $menuKey);

        $current = $permissions;
        foreach ($parts as $part) {
            if (! is_array($current)) {
                return null;
            }

            // 直接キーがある場合
            if (isset($current[$part])) {
                // access_rolesがあれば権限定義
                if (isset($current[$part]['access_roles'])) {
                    return $current[$part];
                }
                // childrenがあればさらに深く
                if (isset($current[$part]['children'])) {
                    $current = $current[$part]['children'];

                    continue;
                }
                // それ以外は次の階層へ
                $current = $current[$part];

                continue;
            }

            return null;
        }

        // 最終的にaccess_rolesがあれば権限定義
        if (is_array($current) && isset($current['access_roles'])) {
            return $current;
        }

        return null;
    }

    /**
     * コア機能の全権限定義を取得（デフォルト＋オーバーライド合成済み）
     * ネスト構造を維持して返す
     */
    public static function getAllCorePermissions(): array
    {
        $cacheKey = self::CACHE_PREFIX.'all_core';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $defaults = config('roles.permissions', []);
            $overrides = RolePermissionOverride::getAllCoreOverrides()->keyBy('menu_key');

            return self::mergePermissionsWithOverrides($defaults, $overrides);
        });
    }

    /**
     * コア機能の全権限定義をフラット形式で取得（デフォルト＋オーバーライド合成済み）
     * キーはドット記法（例：settings.base.index）
     */
    public static function getAllCorePermissionsFlat(): array
    {
        $cacheKey = self::CACHE_PREFIX.'all_core_flat';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $defaults = config('roles.permissions', []);
            $overrides = RolePermissionOverride::getAllCoreOverrides()->keyBy('menu_key');

            $flat = [];
            self::flattenPermissions($defaults, '', $flat);

            $result = [];
            foreach ($flat as $menuKey => $default) {
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
     * ネスト構造の権限定義をフラット化
     */
    protected static function flattenPermissions(array $permissions, string $prefix, array &$result): void
    {
        foreach ($permissions as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (isset($value['access_roles'])) {
                // 権限定義
                $result[$fullKey] = $value;
            }

            if (isset($value['children'])) {
                // 子要素を再帰処理
                self::flattenPermissions($value['children'], $fullKey, $result);
            }
        }
    }

    /**
     * ネスト構造の権限定義にオーバーライドをマージ
     */
    protected static function mergePermissionsWithOverrides(array $permissions, $overrides, string $prefix = ''): array
    {
        $result = [];

        foreach ($permissions as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (isset($value['access_roles'])) {
                // 権限定義
                $override = $overrides->get($fullKey);
                $result[$key] = [
                    'access_roles' => $override?->access_roles ?? $value['access_roles'],
                    'view_roles' => $override?->view_roles ?? $value['view_roles'],
                    'is_overridden' => $override !== null,
                    'default_access_roles' => $value['access_roles'],
                    'default_view_roles' => $value['view_roles'],
                ];

                // childrenがあれば再帰処理
                if (isset($value['children'])) {
                    $result[$key]['children'] = self::mergePermissionsWithOverrides(
                        $value['children'],
                        $overrides,
                        $fullKey
                    );
                }
            } elseif (isset($value['children'])) {
                // 権限定義なしでchildrenのみ
                $result[$key] = [
                    'children' => self::mergePermissionsWithOverrides(
                        $value['children'],
                        $overrides,
                        $fullKey
                    ),
                ];
            }
        }

        return $result;
    }

    /**
     * プラグインの全権限定義を取得（デフォルト＋オーバーライド合成済み）
     * ネスト構造を維持して返す
     */
    public static function getAllPluginPermissions(string $pluginSlug): array
    {
        $cacheKey = self::CACHE_PREFIX."all_plugin:{$pluginSlug}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pluginSlug) {
            // プラグインのデフォルト権限を取得
            $defaults = self::$pluginPermissions[$pluginSlug] ?? [];

            // config/roles.phpからも取得
            $pluginRolesPath = base_path("plugins/{$pluginSlug}/config/roles.php");
            if (file_exists($pluginRolesPath)) {
                $pluginRoles = require $pluginRolesPath;
                $defaults = array_merge_recursive($defaults, $pluginRoles['permissions'] ?? []);
            }

            $overrides = RolePermissionOverride::getAllPluginOverrides($pluginSlug)->keyBy('menu_key');

            return self::mergePermissionsWithOverrides($defaults, $overrides);
        });
    }

    /**
     * プラグインの全権限定義をフラット形式で取得（デフォルト＋オーバーライド合成済み）
     * キーはドット記法（例：pages.index）
     */
    public static function getAllPluginPermissionsFlat(string $pluginSlug): array
    {
        $cacheKey = self::CACHE_PREFIX."all_plugin_flat:{$pluginSlug}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pluginSlug) {
            // プラグインのデフォルト権限を取得
            $defaults = self::$pluginPermissions[$pluginSlug] ?? [];

            // config/roles.phpからも取得
            $pluginRolesPath = base_path("plugins/{$pluginSlug}/config/roles.php");
            if (file_exists($pluginRolesPath)) {
                $pluginRoles = require $pluginRolesPath;
                $defaults = array_merge_recursive($defaults, $pluginRoles['permissions'] ?? []);
            }

            $overrides = RolePermissionOverride::getAllPluginOverrides($pluginSlug)->keyBy('menu_key');

            $flat = [];
            self::flattenPermissions($defaults, '', $flat);

            $result = [];
            foreach ($flat as $menuKey => $default) {
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
            foreach (glob($pluginsPath.'/*/config/roles.php') as $rolesFile) {
                $pluginSlug = basename(dirname(dirname($rolesFile)));
                if (! isset($result[$pluginSlug])) {
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
            // 権限定義がない場合、子項目の権限をチェック
            $allPermissions = self::getAllCorePermissionsFlat();

            foreach ($allPermissions as $key => $permission) {
                if (str_starts_with($key, $menuKey.'.')) {
                    if ($userRole->value >= $permission['access_roles']) {
                        return true;
                    }
                }
            }

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
            // 権限定義がない場合、子項目の権限をチェック
            $allPermissions = self::getAllCorePermissionsFlat();

            foreach ($allPermissions as $key => $permission) {
                if (str_starts_with($key, $menuKey.'.')) {
                    if ($userRole->value >= $permission['view_roles']) {
                        return true;
                    }
                }
            }

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
        // コア権限キャッシュをクリア（フラット化してすべてのキーを取得）
        $corePermissions = config('roles.permissions', []);
        $flat = [];
        self::flattenPermissions($corePermissions, '', $flat);

        foreach (array_keys($flat) as $menuKey) {
            Cache::forget(self::CACHE_PREFIX.'core:'.$menuKey);
        }
        Cache::forget(self::CACHE_PREFIX.'all_core');
        Cache::forget(self::CACHE_PREFIX.'all_core_flat');

        // プラグイン権限キャッシュをクリア
        foreach (array_keys(self::$pluginPermissions) as $pluginSlug) {
            // プラグイン全体のキャッシュをクリア
            Cache::forget(self::CACHE_PREFIX."all_plugin:{$pluginSlug}");

            // プラグインの個別メニューキーのキャッシュをクリア
            $pluginPerms = self::$pluginPermissions[$pluginSlug] ?? [];
            $pluginFlat = [];
            self::flattenPermissions($pluginPerms, '', $pluginFlat);

            foreach (array_keys($pluginFlat) as $menuKey) {
                Cache::forget(self::CACHE_PREFIX."plugin:{$pluginSlug}:{$menuKey}");
            }
        }
    }

    /**
     * 特定のメニューキーのキャッシュをクリア
     */
    public static function clearMenuCache(string $menuKey, ?string $pluginSlug = null): void
    {
        if ($pluginSlug) {
            Cache::forget(self::CACHE_PREFIX."plugin:{$pluginSlug}:{$menuKey}");
            Cache::forget(self::CACHE_PREFIX."all_plugin:{$pluginSlug}");
            Cache::forget(self::CACHE_PREFIX."all_plugin_flat:{$pluginSlug}");
        } else {
            Cache::forget(self::CACHE_PREFIX.'core:'.$menuKey);
            Cache::forget(self::CACHE_PREFIX.'all_core');
            Cache::forget(self::CACHE_PREFIX.'all_core_flat');
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
