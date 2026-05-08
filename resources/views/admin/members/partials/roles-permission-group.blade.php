{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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

<div class="accordion-section bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm"
     x-init="
        if (typeof openSections['{{ $sectionId }}'] === 'undefined') {
            openSections['{{ $sectionId }}'] = false;
            console.log('Initialized section: {{ $sectionId }}', openSections);
        }
     ">
    {{-- Accordion header --}}
    <button 
        type="button"
        class="accordion-header w-full px-6 py-1 text-left flex items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        @click="openSections['{{ $sectionId }}'] = !openSections['{{ $sectionId }}']"
        :class="{ 
            'border-b border-gray-200 dark:border-gray-600 rounded-t-lg rounded-b-none': openSections['{{ $sectionId }}']
        }"
    >
        {{-- Toggle icon on the left --}}
        <i class="mb-2 fas fa-chevron-right w-2 h-2 text-gray-500 dark:text-gray-400 transition-transform duration-300 mr-3 flex-shrink-0"
           :class="{ 'rotate-90': openSections['{{ $sectionId }}'] }"
           style="transform-origin: center;"></i>
        
        <h2 class="my-2 text-md font-bold text-gray-900 dark:text-white flex-grow">
            {{ $sectionTitle }}
        </h2>
    </button>
    
    {{-- Accordion content --}}
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
            @foreach ($items as $item)
                @php
                    // 現在の設定値を取得（配列形式）
                    $permission = $permissions[$item['menuKey']] ?? null;
                    $accessRoleValue = $permission['access_roles'] ?? $minSelectableRole;
                    $viewRoleValue = $permission['view_roles'] ?? $minSelectableRole;
                    $isOverridden = $permission['is_overridden'] ?? false;
                    $defaultAccessRoles = $permission['default_access_roles'] ?? $minSelectableRole;
                    $defaultViewRoles = $permission['default_view_roles'] ?? $minSelectableRole;
                    
                    // 設定値がログインユーザーの権限を超えている場合は、最大値に制限
                    $accessRoleValue = min($accessRoleValue, $maxSelectableRole);
                    $viewRoleValue = min($viewRoleValue, $maxSelectableRole);
                    
                    // 実際の値からインデックスに変換（rangeスライダー用）
                    $accessRoleIndex = $valueToIndex[$accessRoleValue] ?? 0;
                    $viewRoleIndex = $valueToIndex[$viewRoleValue] ?? 0;
                    
                    // デフォルト値のインデックスを計算
                    $defaultAccessIndex = $valueToIndex[$defaultAccessRoles] ?? null;
                    $defaultViewIndex = $valueToIndex[$defaultViewRoles] ?? null;
                    
                    // ユニークなIDを生成
                    $accessId = 'access_' . str_replace('.', '_', $item['menuKey']);
                    $viewId = 'view_' . str_replace('.', '_', $item['menuKey']);
                @endphp
                
                <section class="permission-group bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-6"
                         x-data="{
                             accessIndex: {{ $accessRoleIndex }},
                             viewIndex: {{ $viewRoleIndex }},
                             roleValues: {{ json_encode(array_values($roleValues)) }},
                             get accessValue() { return this.roleValues[this.accessIndex] || this.roleValues[0]; },
                             get viewValue() { return this.roleValues[this.viewIndex] || this.roleValues[0]; },
                             init() {
                                 this.$watch('accessIndex', (newValue, oldValue) => {
                                     if (parseInt(newValue) < parseInt(this.viewIndex)) {
                                         this.$nextTick(() => {
                                             this.accessIndex = this.viewIndex;
                                         });
                                     }
                                 });
                                 this.$watch('viewIndex', (newValue, oldValue) => {
                                     if (parseInt(this.accessIndex) < parseInt(newValue)) {
                                         this.$nextTick(() => {
                                             this.accessIndex = newValue;
                                         });
                                     }
                                 });
                             }
                         }">
                    <header class="permission-group__header mb-6">
                        <h3 class="permission-group__title text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $item['title'] }}
                            <span class="permission-group__key text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                                ({{ $item['menuKey'] }})
                            </span>
                        </h3>
                    </header>

                    <div class="permission-group__content grid md:grid-cols-2 gap-8">
                        <!-- View Permissions (閲覧権限) -->
                        <fieldset class="permission-section">
                            <legend class="permission-section__title text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('admin/members/settings.roles.view_roles') }}
                            </legend>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                {{ __('admin/members/settings.roles.view_roles_help') }}
                            </p>
                            
                            <div>
                                <input type="hidden" 
                                       name="permissions[{{ $item['menuKey'] }}][view_roles]" 
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
                            <legend class="permission-section__title text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('admin/members/settings.roles.access_roles') }}
                            </legend>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                {{ __('admin/members/settings.roles.access_roles_help') }}
                            </p>
                            
                            <div>
                                <input type="hidden" 
                                       name="permissions[{{ $item['menuKey'] }}][access_roles]" 
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
                </section>
            @endforeach
        </div>
    </div>
</div>
