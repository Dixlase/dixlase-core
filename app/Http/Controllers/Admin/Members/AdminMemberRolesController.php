<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Http\Controllers\Admin\Members;

use App\Enums\MemberRole;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\RolePermissionOverride;
use App\Services\PermissionRegistry;
use Illuminate\Http\Request;

class AdminMemberRolesController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Permission settings screen
     */
    public function index()
    {
        $roles = MemberRole::cases();
        $menuList = config('admin.navigation');

        // Core permissions (default + override merged) - nested structure
        $corePermissions = PermissionRegistry::getAllCorePermissions();

        // Core permissions (flat format) - for form submission
        $corePermissionsFlat = PermissionRegistry::getAllCorePermissionsFlat();

        // Collect plugin permission groups
        $pluginPermissionGroups = $this->collectPluginPermissions();

        // Pre-calculate role mapping data (shared across accordion views)
        $currentUserRole = auth()->user()->role;
        $currentUserRoleValue = $currentUserRole->value;
        $isSuperAdmin = $currentUserRoleValue === MemberRole::SUPER_ADMIN->value;
        $maxSelectableRole = $isSuperAdmin ? MemberRole::SUPER_ADMIN->value : $currentUserRoleValue;
        $minSelectableRole = MemberRole::GUEST->value;

        $roleOptions = [];
        foreach ($roles as $role) {
            if ($role->value <= $maxSelectableRole) {
                $roleOptions[$role->value] = $role->label();
            }
        }
        ksort($roleOptions);

        $roleValues = [];
        $roleLabelsForRange = [];
        $index = 0;
        foreach ($roleOptions as $value => $label) {
            $roleValues[$index] = $value;
            $roleLabelsForRange[$index] = $label;
            $index++;
        }
        $valueToIndex = array_flip($roleValues);
        $maxIndex = count($roleValues) - 1;

        $this->viewParams['permissions'] = $corePermissions;
        $this->viewParams['permissionsFlat'] = $corePermissionsFlat;
        $this->viewParams['roles'] = $roles;
        $this->viewParams['menuList'] = $menuList;
        $this->viewParams['pluginPermissionGroups'] = $pluginPermissionGroups;
        $this->viewParams['superAdminValue'] = MemberRole::SUPER_ADMIN->value;
        $this->viewParams['guestValue'] = MemberRole::GUEST->value;
        $this->viewParams['adminDefaultValue'] = MemberRole::ADMIN->value;
        $this->viewParams['isSuperAdmin'] = $isSuperAdmin;
        $this->viewParams['maxSelectableRole'] = $maxSelectableRole;
        $this->viewParams['minSelectableRole'] = $minSelectableRole;
        $this->viewParams['roleValues'] = $roleValues;
        $this->viewParams['roleLabelsForRange'] = $roleLabelsForRange;
        $this->viewParams['valueToIndex'] = $valueToIndex;
        $this->viewParams['maxIndex'] = $maxIndex;

        return view('admin.members.roles', $this->viewParams);
    }

    /**
     * Update permission settings
     *
     * Save as override only if different from default value
     * Delete override when reverting to default value
     */
    public function update(Request $request)
    {
        $this->authorizeEdit('members.settings.roles');

        $memberId = auth()->id();
        $data = $request->input('permissions', []);

        // Collect validation errors
        $errors = [];

        // Process Core permissions
        foreach ($data as $menuKey => $values) {
            $accessRoles = isset($values['access_roles']) ? (int) $values['access_roles'] : MemberRole::GUEST->value;
            $viewRoles = isset($values['view_roles']) ? (int) $values['view_roles'] : MemberRole::GUEST->value;

            // Check that edit permission is not lower than view permission
            if ($accessRoles < $viewRoles) {
                $errors[] = __('admin/members/roles.validation.access_must_be_greater_than_view', [
                    'menu_key' => $menuKey,
                ]);
            }

            // Get default value (nested structure support)
            $default = PermissionRegistry::getEffective($menuKey);

            if ($default) {
                // Delete override if same as default value
                if ($accessRoles === $default['default_access_roles'] && $viewRoles === $default['default_view_roles']) {
                    RolePermissionOverride::resetCoreOverride($menuKey);
                } else {
                    // Save override if different from default value
                    RolePermissionOverride::setCoreOverride($menuKey, $accessRoles, $viewRoles, $memberId);
                }
            }
        }

        // Process plugin permissions
        $pluginData = $request->input('plugin_permissions', []);

        foreach ($pluginData as $pluginSlug => $menuItems) {
            foreach ($menuItems as $menuKey => $values) {
                $accessRoles = isset($values['access_roles']) ? (int) $values['access_roles'] : MemberRole::ADMIN->value;
                $viewRoles = isset($values['view_roles']) ? (int) $values['view_roles'] : MemberRole::ADMIN->value;

                // Check that edit permission is not lower than view permission
                if ($accessRoles < $viewRoles) {
                    $errors[] = __('admin/members/roles.validation.access_must_be_greater_than_view', [
                        'menu_key' => "{$pluginSlug}.{$menuKey}",
                    ]);
                }

                // Get default value using PermissionRegistry (nested structure support)
                $effective = PermissionRegistry::getPluginEffective($pluginSlug, $menuKey);

                if ($effective) {
                    // Delete override if same as default value
                    if ($accessRoles === $effective['default_access_roles'] && $viewRoles === $effective['default_view_roles']) {
                        RolePermissionOverride::resetPluginOverride($pluginSlug, $menuKey);
                    } else {
                        // Save override if different from default value
                        RolePermissionOverride::setPluginOverride($pluginSlug, $menuKey, $accessRoles, $viewRoles, $memberId);
                    }
                }
            }
        }

        // Redirect if there are validation errors
        if (! empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        // Clear cache
        PermissionRegistry::clearCache();

        return redirect()->back()->with('success', __('admin/members/index.messages.permissions_saved'));
    }

    /**
     * Reset permissions to default
     */
    public function reset(Request $request)
    {
        $this->authorizeEdit('members.settings.roles');

        $menuKey = $request->input('menu_key');
        $pluginSlug = $request->input('plugin_slug');

        if ($pluginSlug) {
            RolePermissionOverride::resetPluginOverride($pluginSlug, $menuKey);
        } else {
            RolePermissionOverride::resetCoreOverride($menuKey);
        }

        PermissionRegistry::clearCache();

        return response()->json(['success' => true]);
    }

    /**
     * Collect items for permission settings from installed plugins
     * Only target plugins that have roles.php
     */
    private function collectPluginPermissions(): array
    {
        $pluginGroups = [];

        $pluginsPath = base_path('plugins');
        if (! is_dir($pluginsPath)) {
            return $pluginGroups;
        }

        $pluginDirs = array_filter(glob($pluginsPath.'/*'), 'is_dir');

        foreach ($pluginDirs as $pluginDir) {
            $pluginJsonPath = $pluginDir.'/plugin.json';
            $dixlaseJsonPath = $pluginDir.'/dixlase.json';

            $pluginInfo = null;
            if (file_exists($pluginJsonPath)) {
                $pluginInfo = json_decode(file_get_contents($pluginJsonPath), true);
            } elseif (file_exists($dixlaseJsonPath)) {
                $pluginInfo = json_decode(file_get_contents($dixlaseJsonPath), true);
            }

            if (! $pluginInfo) {
                continue;
            }

            // Use basename($pluginDir) because PermissionRegistry uses directory name as slug
            $pluginSlug = basename($pluginDir);
            $pluginName = $pluginInfo['name'] ?? $pluginSlug;

            if (PermissionRegistry::resolvePluginRolesPath($pluginSlug) === null) {
                continue;
            }

            // Get navigation info (icon + text labels) for the permission tree.
            // New structure: config/admin/navigation.php returns the nav array
            // directly. Legacy structure: config/admin.php with a 'nav' key.
            // Mirror PluginLoaderTrait::loadPluginConfigs() priority so plugins
            // that moved navigation out of admin.php still resolve their menu
            // labels here (otherwise the tree falls back to raw menu keys).
            $adminNav = [];
            $adminNavConfigPath = $pluginDir.'/config/admin/navigation.php';
            $adminConfigPath = $pluginDir.'/config/admin.php';
            if (file_exists($adminNavConfigPath)) {
                $adminNav = require $adminNavConfigPath;
            } elseif (file_exists($adminConfigPath)) {
                $adminConfig = require $adminConfigPath;
                $adminNav = $adminConfig['nav'] ?? [];
            }

            // Get permission settings (nested structure)
            $permissions = PermissionRegistry::getAllPluginPermissions($pluginSlug);
            $permissionsFlat = PermissionRegistry::getAllPluginPermissionsFlat($pluginSlug);

            if (! empty($permissions)) {
                $pluginGroups[] = [
                    'slug' => $pluginSlug,
                    'name' => $pluginName,
                    'description' => $pluginInfo['description'] ?? null,
                    'permissions' => $permissions,
                    'permissionsFlat' => $permissionsFlat,
                    'nav' => $adminNav,
                ];
            }
        }

        return $pluginGroups;
    }

    /**
     * Collect items for permission settings from plugin navigation
     */
    private function collectPluginMenuPermissions(array $navConfig, string $pluginSlug, string $parentKey = ''): array
    {
        $items = [];

        foreach ($navConfig as $key => $item) {
            $menuKey = $parentKey ? $parentKey.'.'.$key : $key;

            if (isset($item['route'])) {
                $items[] = [
                    'type' => 'permission',
                    'title' => isset($item['text']) ? __($item['text']) : $key,
                    'menuKey' => $menuKey,
                    'pluginSlug' => $pluginSlug,
                ];
            }

            if (isset($item['children'])) {
                $childItems = $this->collectPluginMenuPermissions($item['children'], $pluginSlug, $menuKey);
                $items = array_merge($items, $childItems);
            }
        }

        return $items;
    }

    /**
     * Collect items for permission settings from menu
     */
    private function collectMenuPermissions($menuList, $parentKey = '', &$currentSection = '')
    {
        $items = [];

        foreach ($menuList as $key => $item) {
            $menuKey = $parentKey ? $parentKey.'.'.$key : $key;

            $isTarget = isset($item['route']) && ! in_array($menuKey, ['dashboard', 'front', 'media', 'settings']);
            $isHeadingOnly = ! $isTarget && isset($item['children']) && ! in_array($menuKey, ['dashboard']);

            if ($isHeadingOnly) {
                $sectionTitle = __($item['text']);
                if ($currentSection !== $sectionTitle) {
                    $currentSection = $sectionTitle;
                    $items[] = [
                        'type' => 'heading',
                        'title' => $sectionTitle,
                    ];
                }
            }

            if ($isTarget) {
                $items[] = [
                    'type' => 'permission',
                    'title' => __($item['text']),
                    'menuKey' => $menuKey,
                ];
            }

            if (isset($item['children'])) {
                $childItems = $this->collectMenuPermissions($item['children'], $menuKey, $currentSection);
                $items = array_merge($items, $childItems);
            }
        }

        return $items;
    }

    protected function authorizeEdit(string $menuKey)
    {
        if (! \App\Helpers\AdminHelper::canEditMenu($menuKey)) {
            abort(403, __('admin/members/index.messages.insufficient_permissions'));
        }
    }
}
