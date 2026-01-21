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
    $currentNav = $menuList;
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
    $title = __($text);
    
    // 権限定義があるかどうか
    $hasPermission = isset($item['access_roles']);
    
    // 子要素があるかどうか
    $hasChildren = isset($item['children']) && !empty($item['children']);
    
    // ユニークID
    $accordionId = 'perm_' . str_replace('.', '_', $menuKey);
    
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
    $isSuperAdmin = $currentUserRoleValue === \App\Enums\MemberRole::SUPER_ADMIN->value;
    
    // 選択可能な最大値
    $maxSelectableRole = $isSuperAdmin 
        ? \App\Enums\MemberRole::SUPER_ADMIN->value 
        : $currentUserRoleValue;
    
    // 最小値はGUEST
    $minSelectableRole = \App\Enums\MemberRole::GUEST->value;
    
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
        <div class="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg mb-1 {{ $hasChildren ? '' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
            @if ($hasChildren)
                <button 
                    type="button"
                    class="flex-1 px-4 py-3 text-left flex items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg focus:outline-none"
                    @click="open = !open"
                >
                    <i class="fas fa-chevron-right w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform duration-200 mr-3"
                       :class="{ 'rotate-90': open }"></i>
                    <i class="{{ $icon }} w-5 h-5 text-gray-600 dark:text-gray-400 mr-3"></i>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $title }}</span>
                    @if ($hasPermission)
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400 font-mono">({{ $menuKey }})</span>
                    @endif
                </button>
            @else
                <button 
                    type="button"
                    class="flex-1 px-4 py-3 text-left flex items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg focus:outline-none"
                    @click="open = !open"
                >
                    <i class="fas fa-chevron-right w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform duration-200 mr-3"
                       :class="{ 'rotate-90': open }"></i>
                    <i class="{{ $icon }} w-5 h-5 text-gray-600 dark:text-gray-400 mr-3"></i>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $title }}</span>
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400 font-mono">({{ $menuKey }})</span>
                </button>
            @endif
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
                    $accessRoleValue = $permission['access_roles'] ?? $minSelectableRole;
                    $viewRoleValue = $permission['view_roles'] ?? $minSelectableRole;
                    $isOverridden = $permission['is_overridden'] ?? false;
                    
                    // 設定値がログインユーザーの権限を超えている場合は、最大値に制限
                    $accessRoleValue = min($accessRoleValue, $maxSelectableRole);
                    $viewRoleValue = min($viewRoleValue, $maxSelectableRole);
                    
                    // 実際の値からインデックスに変換
                    $accessRoleIndex = $valueToIndex[$accessRoleValue] ?? 0;
                    $viewRoleIndex = $valueToIndex[$viewRoleValue] ?? 0;
                    
                    // ユニークなIDを生成
                    $accessId = 'access_' . str_replace('.', '_', $menuKey);
                    $viewId = 'view_' . str_replace('.', '_', $menuKey);
                @endphp
                
                <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg p-4 mb-2 {{ $indentClass }}">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Access Permissions (編集権限) -->
                        <fieldset class="permission-section">
                            <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('admin/members/settings.roles.access_roles') }}
                            </legend>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                {{ __('admin/members/settings.roles.access_roles_help') }}
                            </p>
                            
                            <div x-data="{ 
                                     rangeIndex: {{ $accessRoleIndex }},
                                     roleValues: {{ json_encode(array_values($roleValues)) }},
                                     get actualValue() { return this.roleValues[this.rangeIndex] || this.roleValues[0]; }
                                 }">
                                <input type="hidden" 
                                       name="permissions[{{ $menuKey }}][access_roles]" 
                                       :value="actualValue">
                                
                                <x-form.range
                                    :id="$accessId"
                                    :name="''"
                                    :value="$accessRoleIndex"
                                    :min="0"
                                    :max="$maxIndex"
                                    :step="1"
                                    :labels="$roleLabelsForRange"
                                    :showValue="false"
                                    :showLabels="true"
                                    xModel="rangeIndex"
                                />
                            </div>
                        </fieldset>

                        <!-- View Permissions (閲覧権限) -->
                        <fieldset class="permission-section">
                            <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('admin/members/settings.roles.view_roles') }}
                            </legend>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                {{ __('admin/members/settings.roles.view_roles_help') }}
                            </p>
                            
                            <div x-data="{ 
                                     rangeIndex: {{ $viewRoleIndex }},
                                     roleValues: {{ json_encode(array_values($roleValues)) }},
                                     get actualValue() { return this.roleValues[this.rangeIndex] || this.roleValues[0]; }
                                 }">
                                <input type="hidden" 
                                       name="permissions[{{ $menuKey }}][view_roles]" 
                                       :value="actualValue">
                                
                                <x-form.range
                                    :id="$viewId"
                                    :name="''"
                                    :value="$viewRoleIndex"
                                    :min="0"
                                    :max="$maxIndex"
                                    :step="1"
                                    :labels="$roleLabelsForRange"
                                    :showValue="false"
                                    :showLabels="true"
                                    xModel="rangeIndex"
                                />
                            </div>
                        </fieldset>
                    </div>
                    
                    @if ($isOverridden)
                        <div class="mt-3 text-xs text-amber-600 dark:text-amber-400">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            {{ __('admin/members/settings.roles.overridden_from_default') }}
                        </div>
                    @endif
                </div>
            @endif
            
            @if ($hasChildren)
                <div class="space-y-1 mt-1">
                    @foreach ($item['children'] as $childKey => $childItem)
                        @include('admin.members.partials.roles-permission-accordion', [
                            'key' => $childKey,
                            'item' => $childItem,
                            'menuList' => $menuList,
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
