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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\MemberRole;

/**
 * @deprecated このモデルは廃止されました。
 *             新方式では RolePermissionOverride モデルと PermissionRegistry サービスを使用してください。
 *             詳細は docs/role-permission-system.md を参照してください。
 */
class PluginMemberRolePermission extends Model
{
    use HasFactory;

    protected $table = 'plugins_members_role_permissions';

    protected $fillable = [
        'plugin_slug',
        'menu_key',
        'access_roles',
        'view_roles',
    ];

    /**
     * access_rolesを整数として取得
     */
    public function getAccessRolesAttribute($value): int
    {
        return (int) ($value ?? MemberRole::ADMIN->value);
    }

    /**
     * view_rolesを整数として取得
     */
    public function getViewRolesAttribute($value): int
    {
        return (int) ($value ?? MemberRole::ADMIN->value);
    }

    /**
     * 指定されたユーザーの権限がアクセス可能かチェック
     */
    public function canAccess(MemberRole $userRole): bool
    {
        // SUPER_ADMINは常にアクセス可能
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }
        
        return $userRole->value >= $this->access_roles;
    }

    /**
     * 指定されたユーザーの権限が閲覧可能かチェック
     */
    public function canView(MemberRole $userRole): bool
    {
        // SUPER_ADMINは常に閲覧可能
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }
        
        return $userRole->value >= $this->view_roles;
    }

    /**
     * 指定されたユーザーの権限が編集可能かチェック
     */
    public function canEdit(MemberRole $userRole): bool
    {
        // SUPER_ADMINは常に編集可能
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }
        
        return $userRole->value >= $this->view_roles;
    }

    /**
     * SUPER_ADMIN専用のアクセス権限かどうか
     */
    public function isSuperAdminOnlyAccess(): bool
    {
        return $this->access_roles === MemberRole::SUPER_ADMIN->value;
    }

    /**
     * SUPER_ADMIN専用の閲覧権限かどうか
     */
    public function isSuperAdminOnlyView(): bool
    {
        return $this->view_roles === MemberRole::SUPER_ADMIN->value;
    }

    /**
     * プラグインスラッグとメニューキーで権限を取得
     */
    public static function getPermission(string $pluginSlug, string $menuKey): ?self
    {
        return static::where('plugin_slug', $pluginSlug)
            ->where('menu_key', $menuKey)
            ->first();
    }

    /**
     * プラグインの全権限を取得
     */
    public static function getPluginPermissions(string $pluginSlug): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('plugin_slug', $pluginSlug)->get();
    }

    /**
     * 全プラグインの権限をプラグインスラッグでグループ化して取得
     */
    public static function getAllGroupedByPlugin(): \Illuminate\Support\Collection
    {
        return static::all()->groupBy('plugin_slug');
    }
}
