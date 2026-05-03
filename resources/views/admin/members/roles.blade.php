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

@extends('layouts.admin')

@section('content')

<form method="POST" action="{{ route('admin.members.roles.update') }}" id="member-roles-form" class="permission-management permission-form" novalidate>
    @csrf
    
    {{-- コア機能の権限設定 --}}
    <div class="permission-section-wrapper mb-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                <i class="fas fa-cog text-indigo-600 dark:text-indigo-400"></i>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin/members/roles.core_permissions') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/members/roles.core_permissions_description') }}</p>
            </div>
        </div>
        
        {{-- 除外項目の説明 --}}
        <x-ui-message type="info" :message="__('admin/members/roles.excluded_items_note')" />
        
        <div class="permission-groups mt-6">
            @foreach ($permissions as $key => $item)
                @include('admin.members.partials.roles-permission-accordion', [
                    'key' => $key,
                    'item' => $item,
                    'menuList' => $menuList,
                    'permissionsFlat' => $permissionsFlat,
                    'roles' => $roles,
                    'prefix' => '',
                    'depth' => 0
                ])
            @endforeach
        </div>
    </div>
    
    {{-- プラグインの権限設定 --}}
    @if (!empty($pluginPermissionGroups))
        <div class="permission-section-wrapper">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <i class="fas fa-puzzle-piece text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin/members/roles.plugin_permissions') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/members/roles.plugin_permissions_description') }}</p>
                </div>
            </div>
            
            <div class="permission-groups space-y-2 mb-10">
                @foreach ($pluginPermissionGroups as $pluginGroup)
                    <div class="plugin-permission-group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm" x-data="{ open: false }">
                        {{-- プラグインヘッダー --}}
                        <button 
                            type="button"
                            class="w-full px-6 py-4 text-left flex items-center hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg focus:outline-none"
                            @click="open = !open"
                            :class="{ 'border-b border-gray-200 dark:border-gray-600 rounded-t-lg rounded-b-none': open }"
                        >
                            <i class="fas fa-chevron-right w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform duration-200 mr-3"
                               :class="{ 'rotate-90': open }"></i>
                            <i class="fas fa-puzzle-piece w-5 h-5 text-purple-600 dark:text-purple-400 mr-3"></i>
                            <div class="flex-grow">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $pluginGroup['name'] }}</span>
                                @if ($pluginGroup['description'])
                                    @php
                                        $description = is_array($pluginGroup['description']) 
                                            ? ($pluginGroup['description'][app()->getLocale()] ?? $pluginGroup['description']['ja'] ?? $pluginGroup['description']['en'] ?? '')
                                            : $pluginGroup['description'];
                                    @endphp
                                    @if ($description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $description }}</p>
                                    @endif
                                @endif
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-mono ml-2">({{ $pluginGroup['slug'] }})</span>
                        </button>
                        
                        {{-- プラグイン権限コンテンツ --}}
                        <div x-show="open" x-collapse class="p-4">
                            <div class="space-y-2">
                                @foreach ($pluginGroup['permissions'] as $key => $item)
                                    @include('admin.members.partials.roles-plugin-permission-accordion', [
                                        'key' => $key,
                                        'item' => $item,
                                        'pluginSlug' => $pluginGroup['slug'],
                                        'pluginNav' => $pluginGroup['nav'],
                                        'permissionsFlat' => $pluginGroup['permissionsFlat'],
                                        'roles' => $roles,
                                        'prefix' => '',
                                        'depth' => 0
                                    ])
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</form>

@endsection


@section('save')
    <!-- 更新ボタンとモーダル-->
    <x-admin.save-button
        id="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="member-roles-form"
    />
@endsection


