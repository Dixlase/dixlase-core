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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'entity' => null,
    'entityType' => 'member', // 'member' or 'user'
    'isInitialAdmin' => false,
    'showDescriptions' => false,
    'errors' => null,
])

@php
    $prefix = $entityType === 'member' ? 'admin/members' : 'dixlase-users::admin/users/form';
    
    // ステータス値を取得
    if ($entityType === 'member') {
        $statusValue = old('status', (string) ($entity->status->value ?? 1));
    } else {
        $statusValue = old('status', (string) ($entity->status ?? 1));
    }
    
    // メンバーのステータスオプション（有効/無効のみ）
    $memberStatusOptions = [
        ['value' => '1', 'label' => 'components.status.active', 'icon' => 'fas fa-check-circle', 'color' => 'green'],
        ['value' => '0', 'label' => 'components.status.inactive', 'icon' => 'fas fa-times-circle', 'color' => 'gray'],
    ];
    
    // ユーザーのステータスオプション（有効/無効/停止）
    $userStatusOptions = [
        ['value' => '1', 'label' => 'dixlase-users::admin/users/form.status_active', 'icon' => 'fas fa-check-circle', 'color' => 'green'],
        ['value' => '0', 'label' => 'dixlase-users::admin/users/form.status_inactive', 'icon' => 'fas fa-times-circle', 'color' => 'gray'],
        ['value' => '2', 'label' => 'dixlase-users::admin/users/form.status_suspended', 'icon' => 'fas fa-ban', 'color' => 'red'],
    ];
    
    $statusOptions = $entityType === 'member' ? $memberStatusOptions : $userStatusOptions;
    $columns = $entityType === 'member' ? 2 : 3;
@endphp

<fieldset>
    <legend>
        {{ $entityType === 'member' ? __('admin/members/create.account_status') : __('dixlase-users::admin/users/form.status') }}
        @if($isInitialAdmin)
            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">（初期管理者のため変更不可）</span>
        @endif
    </legend>
    
    @if($isInitialAdmin)
        <input type="hidden" name="status" value="1">
        <p class="description-text">{{ __('admin/members/form.initial_admin_status_fixed') }}</p>
    @else
        <x-form.radio-card-group
            name="status"
            :options="$statusOptions"
            :value="$statusValue"
            :columns="$columns"
            class="mb-3"
        />
        
        @if($showDescriptions && $entityType === 'user')
            {{-- ユーザーステータスの説明 --}}
            <div class="my-4 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                <div class="flex items-start space-x-2">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 mt-0.5"></i>
                    <div>
                        <strong class="text-gray-900 dark:text-white">{{ __('dixlase-users::admin/users/form.status_active') }}:</strong>
                        <span>{{ __('dixlase-users::admin/users/form.status_active_description') }}</span>
                    </div>
                </div>
                <div class="flex items-start space-x-2">
                    <i class="fas fa-times-circle text-gray-600 dark:text-gray-400 mt-0.5"></i>
                    <div>
                        <strong class="text-gray-900 dark:text-white">{{ __('dixlase-users::admin/users/form.status_inactive') }}:</strong>
                        <span>{{ __('dixlase-users::admin/users/form.status_inactive_description') }}</span>
                    </div>
                </div>
                <div class="flex items-start space-x-2">
                    <i class="fas fa-ban text-red-600 dark:text-red-400 mt-0.5"></i>
                    <div>
                        <strong class="text-gray-900 dark:text-white">{{ __('dixlase-users::admin/users/form.status_suspended') }}:</strong>
                        <span>{{ __('dixlase-users::admin/users/form.status_suspended_description') }}</span>
                    </div>
                </div>
            </div>
        @endif
    @endif
    
    <x-form.error
        :messages="$errors?->get('status') ?? []"
    />
</fieldset>
