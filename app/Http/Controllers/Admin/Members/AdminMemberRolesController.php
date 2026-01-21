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

namespace App\Http\Controllers\Admin\Members;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use App\Models\RolePermissionOverride;
use App\Services\PermissionRegistry;
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
        $roles = MemberRole::cases();
        $menuList = config('admin.nav');

        // コア権限（デフォルト＋オーバーライド合成済み）- ネスト構造
        $corePermissions = PermissionRegistry::getAllCorePermissions();
        
        // コア権限（フラット形式）- フォーム送信用
        $corePermissionsFlat = PermissionRegistry::getAllCorePermissionsFlat();
        
        // プラグイン権限グループを収集
        $pluginPermissionGroups = $this->collectPluginPermissions();

        $this->viewParams['permissions'] = $corePermissions;
        $this->viewParams['permissionsFlat'] = $corePermissionsFlat;
        $this->viewParams['roles'] = $roles;
        $this->viewParams['menuList'] = $menuList;
        $this->viewParams['pluginPermissionGroups'] = $pluginPermissionGroups;

        return view('admin.members.settings.roles', $this->viewParams);
    }

    /**
     * 権限設定更新
     * 
     * デフォルト値と異なる場合のみオーバーライドとして保存
     * デフォルト値に戻す場合はオーバーライドを削除
     */
    public function update(Request $request)
    {
        $this->authorizeEdit('members.settings.roles');

        $memberId = auth()->id();
        $data = $request->input('permissions', []);

        // コア権限の処理
        foreach ($data as $menuKey => $values) {
            $accessRoles = isset($values['access_roles']) ? (int) $values['access_roles'] : MemberRole::GUEST->value;
            $viewRoles = isset($values['view_roles']) ? (int) $values['view_roles'] : MemberRole::GUEST->value;
            
            // デフォルト値を取得（ネスト構造対応）
            $default = PermissionRegistry::getEffective($menuKey);
            
            if ($default) {
                // デフォルト値と同じ場合はオーバーライドを削除
                if ($accessRoles === $default['default_access_roles'] && $viewRoles === $default['default_view_roles']) {
                    RolePermissionOverride::resetCoreOverride($menuKey);
                } else {
                    // デフォルト値と異なる場合はオーバーライドを保存
                    RolePermissionOverride::setCoreOverride($menuKey, $accessRoles, $viewRoles, $memberId);
                }
            }
        }

        // プラグイン権限の処理
        $pluginData = $request->input('plugin_permissions', []);

        foreach ($pluginData as $pluginSlug => $menuItems) {
            foreach ($menuItems as $menuKey => $values) {
                $accessRoles = isset($values['access_roles']) ? (int) $values['access_roles'] : MemberRole::ADMIN->value;
                $viewRoles = isset($values['view_roles']) ? (int) $values['view_roles'] : MemberRole::ADMIN->value;
                
                // プラグインのデフォルト値を取得
                $pluginRolesPath = base_path("plugins/{$pluginSlug}/config/roles.php");
                $default = null;
                if (file_exists($pluginRolesPath)) {
                    $pluginRoles = require $pluginRolesPath;
                    $default = $pluginRoles['permissions'][$menuKey] ?? null;
                }
                
                if ($default) {
                    // デフォルト値と同じ場合はオーバーライドを削除
                    if ($accessRoles === $default['access_roles'] && $viewRoles === $default['view_roles']) {
                        RolePermissionOverride::resetPluginOverride($pluginSlug, $menuKey);
                    } else {
                        // デフォルト値と異なる場合はオーバーライドを保存
                        RolePermissionOverride::setPluginOverride($pluginSlug, $menuKey, $accessRoles, $viewRoles, $memberId);
                    }
                } else {
                    // デフォルト値がない場合は常にオーバーライドを保存
                    RolePermissionOverride::setPluginOverride($pluginSlug, $menuKey, $accessRoles, $viewRoles, $memberId);
                }
            }
        }

        // キャッシュをクリア
        PermissionRegistry::clearCache();

        return redirect()->back()->with('success', __('admin/members/index.messages.permissions_saved'));
    }

    /**
     * 権限をデフォルトにリセット
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
