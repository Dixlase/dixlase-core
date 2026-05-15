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
      (see LICENSE.commercial, or contact info@dixlase.org).

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
<div class="mx-auto" x-data="adminModeSettings()">

<form id="mode-form" action="{{ route('admin.settings.base.mode.update') }}" method="POST">
    @csrf

    <!-- モード選択 -->
    <section class="mb-8">
        <h2>{{ __('admin/settings/base/mode.mode_selection') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('admin/settings/base/mode.mode_selection_description') }}</p>

        <x-form-radio-card-group
            name="admin_mode"
            :options="[
                [
                    'value' => '0',
                    'label' => __('admin/settings/base/mode.simple_mode'),
                    'description' => __('admin/settings/base/mode.simple_mode_description'),
                    'icon' => 'fas fa-magic',
                    'color' => 'green',
                    'badgeColor' => 'green',
                    'features' => [
                        __('admin/settings/base/mode.simple_feature_auto'),
                        __('admin/settings/base/mode.simple_feature_clean'),
                        __('admin/settings/base/mode.simple_feature_customize'),
                    ],
                ],
                [
                    'value' => '1',
                    'label' => __('admin/settings/base/mode.advanced_mode'),
                    'description' => __('admin/settings/base/mode.advanced_mode_description'),
                    'icon' => 'fas fa-cogs',
                    'color' => 'blue',
                    'badgeColor' => 'blue',
                    'features' => [
                        __('admin/settings/base/mode.advanced_feature_full'),
                        __('admin/settings/base/mode.advanced_feature_control'),
                    ],
                ],
            ]"
            :value="(string) $currentMode->value"
            xModel="selectedMode"
            :columns="2"
            color="blue"
            variant="filled"
            :showCheck="true"
        />

        {{-- Simple mode notice (only displayed when currently in detailed mode) --}}
        <div x-show="selectedMode === '0' && originalMode === '1'" x-transition x-cloak
             class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <div class="flex items-start gap-2">
                <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 text-sm"></i>
                <div class="text-sm text-yellow-800 dark:text-yellow-200">
                    <p class="font-medium mb-1">{{ __('admin/settings/base/mode.switch_to_simple_warning') }}</p>
                    <ul class="space-y-0.5 text-xs">
                        <li class="flex items-center gap-1.5">
                            <i class="fas fa-circle text-yellow-400 text-[4px]"></i>
                            {{ __('admin/settings/base/mode.simple_caution_settings_reset') }}
                        </li>
                        <li class="flex items-center gap-1.5">
                            <i class="fas fa-circle text-yellow-400 text-[4px]"></i>
                            {{ __('admin/settings/base/mode.simple_caution_menu_hidden') }}
                        </li>
                        <li class="flex items-center gap-1.5">
                            <i class="fas fa-circle text-yellow-400 text-[4px]"></i>
                            {{ __('admin/settings/base/mode.simple_caution_auto_optimize') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Detailed mode notice (only displayed when currently in simple mode) --}}
        <div x-show="selectedMode === '1' && originalMode === '0'" x-transition x-cloak
             class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <div class="flex items-start gap-2">
                <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 text-sm"></i>
                <div class="text-sm text-yellow-800 dark:text-yellow-200">
                    <p class="font-medium mb-1">{{ __('admin/settings/base/mode.switch_to_advanced_warning') }}</p>
                    <ul class="space-y-0.5 text-xs">
                        <li class="flex items-center gap-1.5">
                            <i class="fas fa-circle text-yellow-400 text-[4px]"></i>
                            {{ __('admin/settings/base/mode.advanced_caution_all_visible') }}
                        </li>
                        <li class="flex items-center gap-1.5">
                            <i class="fas fa-circle text-yellow-400 text-[4px]"></i>
                            {{ __('admin/settings/base/mode.advanced_caution_manual') }}
                        </li>
                        <li class="flex items-center gap-1.5">
                            <i class="fas fa-circle text-yellow-400 text-[4px]"></i>
                            {{ __('admin/settings/base/mode.advanced_caution_knowledge') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- 詳細モード: 説明 -->
    <section x-show="selectedMode === '1'" x-transition x-cloak class="mb-8">
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                <div>
                    <p class="text-sm text-blue-800 dark:text-blue-200">{{ __('admin/settings/base/mode.advanced_info') }}</p>
                </div>
            </div>
        </div>
    </section>

</form>

<!-- モード切替警告モーダル -->
<x-ui-modal
    id="modeSwitchModal"
    :title="__('admin/settings/base/mode.switch_modal_title')"
    message=""
    iconType="warning"
    confirmColor="yellow"
    :dismissible="true"
>
    <div class="modal-message text-left">
        {{-- Switch to simple mode --}}
        <template x-if="pendingMode === '0'">
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">{{ __('admin/settings/base/mode.switch_to_simple_warning') }}</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-exclamation-circle text-yellow-500 mt-0.5"></i>
                        {{ __('admin/settings/base/mode.switch_to_simple_warn_1') }}
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-eye-slash text-yellow-500 mt-0.5"></i>
                        {{ __('admin/settings/base/mode.switch_to_simple_warn_2') }}
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-magic text-yellow-500 mt-0.5"></i>
                        {{ __('admin/settings/base/mode.switch_to_simple_warn_3') }}
                    </li>
                </ul>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>{{ __('admin/settings/base/mode.switch_to_simple_note') }}
                </p>
            </div>
        </template>
        {{-- Switch to detailed mode --}}
        <template x-if="pendingMode === '1'">
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">{{ __('admin/settings/base/mode.switch_to_advanced_warning') }}</p>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-eye text-yellow-500 mt-0.5"></i>
                        {{ __('admin/settings/base/mode.switch_to_advanced_warn_1') }}
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-eraser text-yellow-500 mt-0.5"></i>
                        {{ __('admin/settings/base/mode.switch_to_advanced_warn_2') }}
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-wrench text-yellow-500 mt-0.5"></i>
                        {{ __('admin/settings/base/mode.switch_to_advanced_warn_3') }}
                    </li>
                </ul>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>{{ __('admin/settings/base/mode.switch_to_advanced_note') }}
                </p>
            </div>
        </template>
    </div>
    <x-slot name="footer">
        <x-form-button
            type="button"
            variant="secondary"
            :label="__('admin/settings/base/mode.switch_cancel')"
            @click="cancelSwitch()"
            class="mx-2"
        />
        <x-form-button
            type="button"
            variant="warning"
            :label="__('admin/settings/base/mode.switch_confirm')"
            @click="confirmSwitch()"
            class="mx-2"
        />
    </x-slot>
</x-ui-modal>

</div>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('admin/settings/base/mode.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="mode-form"
    />
@endsection

@push('scripts')
<div id="mode-settings"
     data-current-mode="{{ $currentMode->value }}"
     style="display:none;"></div>
@endpush
