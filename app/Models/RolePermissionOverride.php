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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\MemberRole;

class RolePermissionOverride extends Model
{
    protected $table = 'role_permission_overrides';

    protected $fillable = [
        'source_type',
        'source_id',
        'menu_key',
        'access_roles',
        'view_roles',
        'updated_by',
    ];

    protected $casts = [
        'access_roles' => 'integer',
        'view_roles' => 'integer',
    ];

    /**
     * ソース種別定数
     */
    public const SOURCE_CORE = 'core';
    public const SOURCE_PLUGIN = 'plugin';

    /**
     * 更新者リレーション
     */
    public function updatedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'updated_by');
    }

    /**
     * 指定されたユーザー権限がアクセス可能かチェック
     */
    public function canAccess(MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }
        
        return $userRole->value >= ($this->access_roles ?? MemberRole::GUEST->value);
    }

    /**
     * 指定されたユーザー権限が閲覧可能かチェック
     */
    public function canView(MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }
        
        return $userRole->value >= ($this->view_roles ?? MemberRole::GUEST->value);
    }

    /**
     * コア機能のオーバーライドを取得
     */
    public static function getCoreOverride(string $menuKey): ?self
    {
        return static::where('source_type', self::SOURCE_CORE)
            ->whereNull('source_id')
            ->where('menu_key', $menuKey)
            ->first();
    }

    /**
     * プラグイン機能のオーバーライドを取得
     */
    public static function getPluginOverride(string $pluginSlug, string $menuKey): ?self
    {
        return static::where('source_type', self::SOURCE_PLUGIN)
            ->where('source_id', $pluginSlug)
            ->where('menu_key', $menuKey)
            ->first();
    }

    /**
     * コア機能のオーバーライドを全て取得
     */
    public static function getAllCoreOverrides(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('source_type', self::SOURCE_CORE)
            ->whereNull('source_id')
            ->get();
    }

    /**
     * プラグイン機能のオーバーライドを全て取得
     */
    public static function getAllPluginOverrides(?string $pluginSlug = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('source_type', self::SOURCE_PLUGIN);
        
        if ($pluginSlug !== null) {
            $query->where('source_id', $pluginSlug);
        }
        
        return $query->get();
    }

    /**
     * コア機能のオーバーライドを保存または更新
     */
    public static function setCoreOverride(string $menuKey, int $accessRoles, int $viewRoles, ?int $updatedBy = null): self
    {
        $result = static::updateOrCreate(
            [
                'source_type' => self::SOURCE_CORE,
                'source_id' => null,
                'menu_key' => $menuKey,
            ],
            [
                'access_roles' => $accessRoles,
                'view_roles' => $viewRoles,
                'updated_by' => $updatedBy,
            ]
        );
        
        // キャッシュをクリア
        \App\Services\PermissionRegistry::clearMenuCache($menuKey);
        
        return $result;
    }

    /**
     * プラグイン機能のオーバーライドを保存または更新
     */
    public static function setPluginOverride(string $pluginSlug, string $menuKey, int $accessRoles, int $viewRoles, ?int $updatedBy = null): self
    {
        $result = static::updateOrCreate(
            [
                'source_type' => self::SOURCE_PLUGIN,
                'source_id' => $pluginSlug,
                'menu_key' => $menuKey,
            ],
            [
                'access_roles' => $accessRoles,
                'view_roles' => $viewRoles,
                'updated_by' => $updatedBy,
            ]
        );
        
        // キャッシュをクリア
        \App\Services\PermissionRegistry::clearMenuCache($menuKey, $pluginSlug);
        
        return $result;
    }

    /**
     * コア機能のオーバーライドを削除（デフォルトに戻す）
     */
    public static function resetCoreOverride(string $menuKey): bool
    {
        $result = static::where('source_type', self::SOURCE_CORE)
            ->whereNull('source_id')
            ->where('menu_key', $menuKey)
            ->delete() > 0;
        
        // キャッシュをクリア
        \App\Services\PermissionRegistry::clearMenuCache($menuKey);
        
        return $result;
    }

    /**
     * プラグイン機能のオーバーライドを削除（デフォルトに戻す）
     */
    public static function resetPluginOverride(string $pluginSlug, string $menuKey): bool
    {
        $result = static::where('source_type', self::SOURCE_PLUGIN)
            ->where('source_id', $pluginSlug)
            ->where('menu_key', $menuKey)
            ->delete() > 0;
        
        // キャッシュをクリア
        \App\Services\PermissionRegistry::clearMenuCache($menuKey, $pluginSlug);
        
        return $result;
    }

    /**
     * プラグインの全オーバーライドを削除（アンインストール時）
     */
    public static function deletePluginOverrides(string $pluginSlug): int
    {
        return static::where('source_type', self::SOURCE_PLUGIN)
            ->where('source_id', $pluginSlug)
            ->delete();
    }

    /**
     * 孤児オーバーライド（存在しないプラグインのオーバーライド）を検出
     */
    public static function findOrphanOverrides(array $activePluginSlugs): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('source_type', self::SOURCE_PLUGIN)
            ->whereNotIn('source_id', $activePluginSlugs)
            ->get();
    }

    /**
     * 孤児オーバーライドを削除
     */
    public static function deleteOrphanOverrides(array $activePluginSlugs): int
    {
        return static::where('source_type', self::SOURCE_PLUGIN)
            ->whereNotIn('source_id', $activePluginSlugs)
            ->delete();
    }
}
