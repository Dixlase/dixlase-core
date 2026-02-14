{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@php
    // メニューキーを生成
    $menuKey = $prefix ? $prefix . '.' . $key : $key;
    
    // ナビゲーション設定からアイコンとテキストを取得
    $navItem = null;
    $navParts = explode('.', $menuKey);
    $currentNav = $pluginNav;
    foreach ($navParts as $part) {
        if (isset($currentNav[$part])) {
            $navItem = $currentNav[$part];
            $currentNav = $navItem['children'] ?? [];
        } else {
            $navItem = null;
            break;
        }
    }
    
    $icon = $navItem['icon'] ?? 'fas fa-folder';
    $text = $navItem['text'] ?? $key;
    
    // ナビゲーションにないキーの場合、翻訳ファイルからラベルを取得
    if ($navItem === null) {
        // プラグイン固有の翻訳キーを試す
        // DixlaseUsers -> users-plugin のように変換
        $pluginNamespace = strtolower(str_replace('Dixlase', '', $pluginSlug)) . '-plugin';
        $pluginTransKey = $pluginNamespace . '::admin/nav.permission_labels.' . $key;
        $pluginLabel = __($pluginTransKey);
        
        // プラグイン翻訳が見つかった場合
        if ($pluginLabel !== $pluginTransKey) {
            $title = $pluginLabel;
        } else {
            // コアの翻訳を試す
            $permissionLabel = __('admin/members/roles.permission_labels.' . $key);
            $title = $permissionLabel !== 'admin/members/roles.permission_labels.' . $key 
                ? $permissionLabel 
                : $key;
        }
    } else {
        $title = __($text);
    }
    
    // 権限定義があるかどうか
    $hasPermission = isset($item['access_roles']);
    
    // 子要素があるかどうか
    $hasChildren = isset($item['children']) && !empty($item['children']);
    
    // ユニークID
    $accordionId = 'plugin_perm_' . $pluginSlug . '_' . str_replace('.', '_', $menuKey);
    
    // 深さに応じたインデント
    $indentClass = match($depth) {
        0 => '',
        1 => 'ml-4',
        2 => 'ml-8',
        3 => 'ml-12',
        default => 'ml-16'
    };
    
    // ログインユーザーの権限を取得
    $currentUserRole = auth()->user()->role;
    $currentUserRoleValue = $currentUserRole->value;
    $isSuperAdmin = $currentUserRoleValue === $superAdminValue;

    // 選択可能な最大値
    $maxSelectableRole = $isSuperAdmin
        ? $superAdminValue
        : $currentUserRoleValue;

    // 最小値はGUEST
    $minSelectableRole = $guestValue;
    
    // 権限オプションを作成
    $roleOptions = [];
    $roleValues = [];
    $roleLabelsForRange = [];
    $index = 0;
    foreach ($roles as $role) {
        if ($role->value <= $maxSelectableRole) {
            $roleOptions[$role->value] = $role->label();
        }
    }
    ksort($roleOptions);
    
    foreach ($roleOptions as $value => $label) {
        $roleValues[$index] = $value;
        $roleLabelsForRange[$index] = $label;
        $index++;
    }
    
    $valueToIndex = array_flip($roleValues);
    $maxIndex = count($roleValues) - 1;
@endphp

<div class="permission-accordion {{ $indentClass }}" x-data="{ open: false }">
    @if ($hasChildren || $hasPermission)
        {{-- アコーディオンヘッダー --}}
        <div class="flex items-center bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg mb-1">
            <button 
                type="button"
                class="flex-1 px-4 py-3 text-left flex items-center hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors rounded-lg focus:outline-none"
                @click="open = !open"
            >
                <i class="fas fa-chevron-right w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform duration-200 mr-3"
                   :class="{ 'rotate-90': open }"></i>
                <i class="{{ $icon }} w-5 h-5 text-gray-600 dark:text-gray-400 mr-3"></i>
                <span class="font-medium text-gray-900 dark:text-white">{{ $title }}</span>
                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400 font-mono">({{ $menuKey }})</span>
            </button>
        </div>
        
        {{-- アコーディオンコンテンツ --}}
        <div 
            x-show="open"
            x-collapse
            class="overflow-hidden"
        >
            @if ($hasPermission)
                @php
                    // 現在の設定値を取得
                    $permission = $permissionsFlat[$menuKey] ?? null;
                    $accessRoleValue = $permission['access_roles'] ?? $adminDefaultValue;
                    $viewRoleValue = $permission['view_roles'] ?? $adminDefaultValue;
                    $isOverridden = $permission['is_overridden'] ?? false;
                    $defaultAccessRoles = $permission['default_access_roles'] ?? $adminDefaultValue;
                    $defaultViewRoles = $permission['default_view_roles'] ?? $adminDefaultValue;
                    
                    // 設定値がログインユーザーの権限を超えている場合は、最大値に制限
                    $accessRoleValue = min($accessRoleValue, $maxSelectableRole);
                    $viewRoleValue = min($viewRoleValue, $maxSelectableRole);
                    
                    // 実際の値からインデックスに変換
                    $accessRoleIndex = $valueToIndex[$accessRoleValue] ?? 0;
                    $viewRoleIndex = $valueToIndex[$viewRoleValue] ?? 0;
                    
                    // デフォルト値のインデックスを計算
                    $defaultAccessIndex = $valueToIndex[$defaultAccessRoles] ?? null;
                    $defaultViewIndex = $valueToIndex[$defaultViewRoles] ?? null;
                    
                    // ユニークなIDを生成
                    $accessId = 'plugin_access_' . $pluginSlug . '_' . str_replace('.', '_', $menuKey);
                    $viewId = 'plugin_view_' . $pluginSlug . '_' . str_replace('.', '_', $menuKey);
                @endphp
                
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 mb-2 {{ $indentClass }}"
                     x-data="{
                         accessIndex: {{ $accessRoleIndex }},
                         viewIndex: {{ $viewRoleIndex }},
                         roleValues: {{ json_encode(array_values($roleValues)) }},
                         get accessValue() { return this.roleValues[this.accessIndex] || this.roleValues[0]; },
                         get viewValue() { return this.roleValues[this.viewIndex] || this.roleValues[0]; },
                         init() {
                             // accessIndexの変更を監視
                             this.$watch('accessIndex', (newValue, oldValue) => {
                                 // 編集権限が閲覧権限より下にならないように制限
                                 if (parseInt(newValue) < parseInt(this.viewIndex)) {
                                     this.$nextTick(() => {
                                         this.accessIndex = this.viewIndex;
                                     });
                                 }
                             });
                             // viewIndexの変更を監視
                             this.$watch('viewIndex', (newValue, oldValue) => {
                                 // 閲覧権限を上げたときに編集権限がそれより下なら自動的に上げる
                                 if (parseInt(this.accessIndex) < parseInt(newValue)) {
                                     this.$nextTick(() => {
                                         this.accessIndex = newValue;
                                     });
                                 }
                             });
                         }
                     }">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- View Permissions (閲覧権限) -->
                        <fieldset class="permission-section">
                            <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('admin/members/roles.view_roles') }}
                            </legend>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                {{ __('admin/members/roles.view_roles_help') }}
                            </p>
                            
                            <div>
                                <input type="hidden" 
                                       name="plugin_permissions[{{ $pluginSlug }}][{{ $menuKey }}][view_roles]" 
                                       :value="viewValue">
                                
                                <x-form-range
                                    :id="$viewId"
                                    :name="''"
                                    :value="$viewRoleIndex"
                                    :min="0"
                                    :max="$maxIndex"
                                    :step="1"
                                    :labels="$roleLabelsForRange"
                                    :showValue="false"
                                    :showLabels="true"
                                    xModel="viewIndex"
                                    :defaultValue="$defaultViewIndex"
                                />
                            </div>
                        </fieldset>

                        <!-- Access Permissions (編集権限) -->
                        <fieldset class="permission-section">
                            <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('admin/members/roles.access_roles') }}
                            </legend>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                {{ __('admin/members/roles.access_roles_help') }}
                            </p>
                            
                            <div>
                                <input type="hidden" 
                                       name="plugin_permissions[{{ $pluginSlug }}][{{ $menuKey }}][access_roles]" 
                                       :value="accessValue">
                                
                                <x-form-range
                                    :id="$accessId"
                                    :name="''"
                                    :value="$accessRoleIndex"
                                    :min="0"
                                    :max="$maxIndex"
                                    :step="1"
                                    :labels="$roleLabelsForRange"
                                    :showValue="false"
                                    :showLabels="true"
                                    xModel="accessIndex"
                                    :defaultValue="$defaultAccessIndex"
                                />
                            </div>
                        </fieldset>
                    </div>
                    
                    @if ($isOverridden)
                        <div class="mt-3 text-xs text-amber-600 dark:text-amber-400">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            {{ __('admin/members/roles.overridden_from_default') }}
                        </div>
                    @endif
                </div>
            @endif
            
            @if ($hasChildren)
                <div class="space-y-1 mt-1">
                    @foreach ($item['children'] as $childKey => $childItem)
                        @include('admin.members.partials.roles-plugin-permission-accordion', [
                            'key' => $childKey,
                            'item' => $childItem,
                            'pluginSlug' => $pluginSlug,
                            'pluginNav' => $pluginNav,
                            'permissionsFlat' => $permissionsFlat,
                            'roles' => $roles,
                            'prefix' => $menuKey,
                            'depth' => $depth + 1
                        ])
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
