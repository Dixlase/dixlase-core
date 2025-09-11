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

namespace App\Helpers;


use App\Enums\MemberRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseSetting;
use App\Models\MemberRolePermission;


class AdminHelper
{

    public static function getAdminUrl()
    {
        if (Schema::hasTable('base_settings')) {
            $adminUrl = BaseSetting::getValue('admin_url', config('admin.admin_url'));
        } else {
            $adminUrl = config('admin.admin_url');
        }
        return $adminUrl;
    }


    public static function canAccessMenu(string $menuKey): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        $permission = MemberRolePermission::where('menu_key', $menuKey)->first();
        if (!$permission) {
            return false;
        }

        return in_array($user->role->value, explode(',', $permission->access_roles))
            || in_array($user->role->value, explode(',', $permission->view_roles));
    }

    public static function canEditMenu(string $menuKey): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        $permission = MemberRolePermission::where('menu_key', $menuKey)->first();
        if (!$permission) {
            return false;
        }

        return in_array($user->role->value, explode(',', $permission->access_roles));
    }
}
