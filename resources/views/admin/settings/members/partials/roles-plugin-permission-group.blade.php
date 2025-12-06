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
    
    // プラグイン情報
    $pluginSlug = $pluginGroup['slug'];
    $pluginName = $pluginGroup['name'];
    $pluginDescription = $pluginGroup['description'] ?? null;
    $sectionId = 'plugin_' . md5($pluginSlug);
@endphp

<div class="accordion-section bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm"
     x-init="
        if (typeof openSections['{{ $sectionId }}'] === 'undefined') {
            openSections['{{ $sectionId }}'] = false;
        }
     ">
    {{-- アコーディオンヘッダー --}}
    <button 
        type="button"
        class="accordion-header w-full px-6 py-1 text-left flex items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2"
        @click="openSections['{{ $sectionId }}'] = !openSections['{{ $sectionId }}']"
        :class="{ 
            'border-b border-gray-200 dark:border-gray-600 rounded-t-lg rounded-b-none': openSections['{{ $sectionId }}']
        }"
    >
        {{-- 左側の開閉アイコン --}}
        <i class="mb-2 fas fa-chevron-right w-2 h-2 text-gray-500 dark:text-gray-400 transition-transform duration-300 mr-3 flex-shrink-0"
           :class="{ 'rotate-90': openSections['{{ $sectionId }}'] }"
           style="transform-origin: center;"></i>
        
        <div class="flex-grow">
            <h2 class="my-2 text-md font-bold text-gray-900 dark:text-white flex items-center gap-2">
                {{ $pluginName }}
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                    {{ $pluginSlug }}
                </span>
            </h2>
            @if ($pluginDescription)
                <p class="text-xs text-gray-500 dark:text-gray-400 -mt-1 mb-2">
                    @if (is_array($pluginDescription))
                        {{ $pluginDescription[app()->getLocale()] ?? $pluginDescription['en'] ?? '' }}
                    @else
                        {{ $pluginDescription }}
                    @endif
                </p>
            @endif
        </div>
    </button>
    
    {{-- アコーディオンコンテンツ --}}
    <div 
        class="accordion-content overflow-hidden rounded-b-lg"
        x-show="openSections['{{ $sectionId }}']"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 max-h-0"
        x-transition:enter-end="opacity-100 max-h-screen"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 max-h-screen"
        x-transition:leave-end="opacity-0 max-h-0"
    >
        <div class="p-6 space-y-6">
            @foreach ($pluginGroup['items'] as $item)
                @php
                    // 現在の設定値を取得（デフォルトはADMIN）
                    $defaultRole = \App\Enums\MemberRole::ADMIN->value;
                    $accessRoleValue = $pluginPermissions[$item['menuKey']]->access_roles ?? $defaultRole;
                    $viewRoleValue = $pluginPermissions[$item['menuKey']]->view_roles ?? $defaultRole;
                    
                    // 設定値がログインユーザーの権限を超えている場合は、最大値に制限
                    $accessRoleValue = min($accessRoleValue, $maxSelectableRole);
                    $viewRoleValue = min($viewRoleValue, $maxSelectableRole);
                    
                    // 実際の値からインデックスに変換
                    $accessRoleIndex = $valueToIndex[$accessRoleValue] ?? ($valueToIndex[$defaultRole] ?? 0);
                    $viewRoleIndex = $valueToIndex[$viewRoleValue] ?? ($valueToIndex[$defaultRole] ?? 0);
                    
                    // ユニークなIDを生成
                    $accessId = 'plugin_access_' . str_replace(['.', '-'], '_', $pluginSlug . '_' . $item['menuKey']);
                    $viewId = 'plugin_view_' . str_replace(['.', '-'], '_', $pluginSlug . '_' . $item['menuKey']);
                @endphp

                <section class="permission-group bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-6">
                    <header class="permission-group__header mb-6">
                        <h3 class="permission-group__title text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $item['title'] }}
                            <span class="permission-group__key text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                                ({{ $item['menuKey'] }})
                            </span>
                        </h3>
                    </header>

                    <div class="permission-group__content grid md:grid-cols-2 gap-8">
                        <!-- Access Permissions (編集権限) -->
                        <fieldset class="permission-section">
                            <legend class="permission-section__title text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('admin.settings.members.roles.access_roles') }}
                            </legend>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                {{ __('admin.settings.members.roles.access_roles_help') }}
                            </p>
                            
                            <div class="permission-section__options" 
                                 x-data="{ 
                                     rangeIndex: {{ $accessRoleIndex }},
                                     roleValues: {{ json_encode(array_values($roleValues)) }},
                                     get actualValue() { return this.roleValues[this.rangeIndex] || this.roleValues[0]; }
                                 }">
                                {{-- Hidden input for actual value --}}
                                <input type="hidden" 
                                       name="plugin_permissions[{{ $pluginSlug }}][{{ $item['menuKey'] }}][access_roles]" 
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
                            <legend class="permission-section__title text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('admin.settings.members.roles.view_roles') }}
                            </legend>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                {{ __('admin.settings.members.roles.view_roles_help') }}
                            </p>
                            
                            <div class="permission-section__options"
                                 x-data="{ 
                                     rangeIndex: {{ $viewRoleIndex }},
                                     roleValues: {{ json_encode(array_values($roleValues)) }},
                                     get actualValue() { return this.roleValues[this.rangeIndex] || this.roleValues[0]; }
                                 }">
                                {{-- Hidden input for actual value --}}
                                <input type="hidden" 
                                       name="plugin_permissions[{{ $pluginSlug }}][{{ $item['menuKey'] }}][view_roles]" 
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
                </section>
            @endforeach
        </div>
    </div>
</div>
