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

namespace App\Http\Controllers\Admin\Settings\Members;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use App\Models\MemberRolePermission;
use App\Models\PluginMemberRolePermission;
use App\Enums\MemberRole;

class AdminMemberRolesController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 権限設定画面
     */
    public function index()
    {
        $permissions = MemberRolePermission::all()->keyBy('menu_key');
        $roles = MemberRole::cases();
        $menuList = config('admin.nav');

        $corePermissionItems = $this->collectMenuPermissions($menuList);
        $pluginPermissionGroups = $this->collectPluginPermissions();
        
        $pluginPermissions = PluginMemberRolePermission::all()
            ->groupBy('plugin_slug')
            ->map(fn($items) => $items->keyBy('menu_key'));

        $this->viewParams['permissions'] = $permissions;
        $this->viewParams['pluginPermissions'] = $pluginPermissions;
        $this->viewParams['roles'] = $roles;
        $this->viewParams['menuList'] = $menuList;
        $this->viewParams['permissionItems'] = $corePermissionItems;
        $this->viewParams['pluginPermissionGroups'] = $pluginPermissionGroups;

        return view('admin.members.settings.roles', $this->viewParams);
    }

    /**
     * 権限設定更新
     */
    public function update(Request $request)
    {
        $this->authorizeEdit('members.roles');

        $data = $request->input('permissions', []);

        foreach ($data as $menuKey => $values) {
            MemberRolePermission::updateOrCreate(
                ['menu_key' => $menuKey],
                [
                    'access_roles' => isset($values['access_roles']) ? (int) $values['access_roles'] : MemberRole::GUEST->value,
                    'view_roles' => isset($values['view_roles']) ? (int) $values['view_roles'] : MemberRole::GUEST->value,
                ]
            );
        }

        $pluginData = $request->input('plugin_permissions', []);

        foreach ($pluginData as $pluginSlug => $menuItems) {
            foreach ($menuItems as $menuKey => $values) {
                PluginMemberRolePermission::updateOrCreate(
                    [
                        'plugin_slug' => $pluginSlug,
                        'menu_key' => $menuKey,
                    ],
                    [
                        'access_roles' => isset($values['access_roles']) ? (int) $values['access_roles'] : MemberRole::ADMIN->value,
                        'view_roles' => isset($values['view_roles']) ? (int) $values['view_roles'] : MemberRole::ADMIN->value,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', __('admin/members/index.messages.permissions_saved'));
    }

    /**
     * インストール済みプラグインから権限設定用のアイテムを収集
     */
    private function collectPluginPermissions(): array
    {
        $pluginGroups = [];
        
        $pluginsPath = base_path('plugins');
        if (!is_dir($pluginsPath)) {
            return $pluginGroups;
        }

        $pluginDirs = array_filter(glob($pluginsPath . '/*'), 'is_dir');
        
        foreach ($pluginDirs as $pluginDir) {
            $pluginJsonPath = $pluginDir . '/plugin.json';
            $dixlaseJsonPath = $pluginDir . '/dixlase.json';
            
            $pluginInfo = null;
            if (file_exists($pluginJsonPath)) {
                $pluginInfo = json_decode(file_get_contents($pluginJsonPath), true);
            } elseif (file_exists($dixlaseJsonPath)) {
                $pluginInfo = json_decode(file_get_contents($dixlaseJsonPath), true);
            }
            
            if (!$pluginInfo) {
                continue;
            }

            $pluginSlug = $pluginInfo['slug'] ?? basename($pluginDir);
            $pluginName = $pluginInfo['name'] ?? $pluginSlug;
            
            $adminConfigPath = $pluginDir . '/config/admin.php';
            if (!file_exists($adminConfigPath)) {
                continue;
            }
            
            $adminConfig = require $adminConfigPath;
            if (!isset($adminConfig['nav']) || !is_array($adminConfig['nav'])) {
                continue;
            }

            $items = $this->collectPluginMenuPermissions($adminConfig['nav'], $pluginSlug);
            
            if (!empty($items)) {
                $pluginGroups[] = [
                    'slug' => $pluginSlug,
                    'name' => $pluginName,
                    'description' => $pluginInfo['description'] ?? null,
                    'items' => $items,
                ];
            }
        }
        
        return $pluginGroups;
    }

    /**
     * プラグインのナビゲーションから権限設定用のアイテムを収集
     */
    private function collectPluginMenuPermissions(array $navConfig, string $pluginSlug, string $parentKey = ''): array
    {
        $items = [];
        
        foreach ($navConfig as $key => $item) {
            $menuKey = $parentKey ? $parentKey . '.' . $key : $key;
            
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
     * メニューから権限設定用のアイテムを収集
     */
    private function collectMenuPermissions($menuList, $parentKey = '', &$currentSection = '')
    {
        $items = [];
        
        foreach ($menuList as $key => $item) {
            $menuKey = $parentKey ? $parentKey . '.' . $key : $key;

            $isTarget = isset($item['route']) && !in_array($menuKey, ['dashboard', 'front', 'media', 'settings']);
            $isHeadingOnly = !$isTarget && isset($item['children']) && !in_array($menuKey, ['dashboard']);

            if ($isHeadingOnly) {
                $sectionTitle = __($item['text']);
                if ($currentSection !== $sectionTitle) {
                    $currentSection = $sectionTitle;
                    $items[] = [
                        'type' => 'heading',
                        'title' => $sectionTitle
                    ];
                }
            }

            if ($isTarget) {
                $items[] = [
                    'type' => 'permission',
                    'title' => __($item['text']),
                    'menuKey' => $menuKey
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
        if (!\App\Helpers\AdminHelper::canEditMenu($menuKey)) {
            abort(403, __('admin/members/index.messages.insufficient_permissions'));
        }
    }
}
