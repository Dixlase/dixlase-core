{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the  implied warranty of
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
                    'color' => 'blue',
                    'badge' => __('admin/settings/base/mode.recommended'),
                    'badgeColor' => 'blue',
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
                    'color' => 'gray',
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

        {{-- かんたんモード注意事項（現在詳細モードの場合のみ表示） --}}
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

        {{-- 詳細モード注意事項（現在かんたんモードの場合のみ表示） --}}
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

    <!-- かんたんモード: メニュー表示カスタマイズ -->
    <section x-show="selectedMode === '0'" x-transition x-cloak class="mb-8">
        <h2>{{ __('admin/settings/base/mode.menu_customize') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ __('admin/settings/base/mode.menu_customize_description') }}</p>

        <!-- 表示レベル凡例 -->
        <div class="flex flex-wrap gap-3 mb-6 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            @foreach ($menuVisibilityCases as $vis)
                <span class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                    <i class="{{ $vis->iconClass() }}"></i>
                    {{ __($vis->translationKey()) }}
                </span>
            @endforeach
        </div>

        <!-- メニュー項目リスト -->
        <div class="space-y-3">
            @foreach ($menuItems as $menuKey => $menuItem)
                @php
                    $isLocked = $menuItem['locked'] ?? false;
                    $allowedVisibilities = $menuItem['allowed_visibilities'] ?? \App\Enums\MenuVisibility::cases();
                    $currentValue = $currentVisibilities[$menuKey] ?? \App\Enums\MenuVisibility::Full->value;
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <!-- 親メニュー -->
                    <div class="flex items-center justify-between p-4 {{ isset($menuItem['children']) ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                        <div class="flex items-center gap-3">
                            <i class="{{ $menuItem['icon'] }} text-gray-500 dark:text-gray-400 w-5 text-center"></i>
                            <span class="font-medium text-gray-900 dark:text-white">{{ __($menuItem['text_key']) }}</span>
                            @if ($isLocked)
                                <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded">{{ __('admin/settings/base/mode.always_visible') }}</span>
                            @endif
                        </div>

                        @if (!$isLocked)
                            <select
                                name="menu_visibilities[{{ $menuKey }}]"
                                x-model="visibilities['{{ $menuKey }}']"
                                class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                                @foreach ($allowedVisibilities as $vis)
                                    <option value="{{ $vis->value }}" {{ $currentValue === $vis->value ? 'selected' : '' }}>
                                        {{ __($vis->translationKey()) }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="menu_visibilities[{{ $menuKey }}]" value="{{ \App\Enums\MenuVisibility::Full->value }}">
                            <span class="text-sm text-gray-400 dark:text-gray-500">
                                <i class="{{ \App\Enums\MenuVisibility::Full->iconClass() }} mr-1"></i>
                                {{ __(\App\Enums\MenuVisibility::Full->translationKey()) }}
                            </span>
                        @endif
                    </div>

                    <!-- 子メニュー -->
                    @if (isset($menuItem['children']))
                        <div class="bg-gray-50 dark:bg-gray-800/50">
                            @foreach ($menuItem['children'] as $childKey => $childItem)
                                @php
                                    $childFullKey = $menuKey . '.' . $childKey;
                                    $childAllowed = $childItem['allowed_visibilities'] ?? \App\Enums\MenuVisibility::cases();
                                    $childCurrentValue = $currentVisibilities[$childFullKey] ?? \App\Enums\MenuVisibility::Full->value;
                                @endphp
                                <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700 first:border-t-0">
                                    <div class="flex items-center gap-3 pl-6">
                                        <i class="{{ $childItem['icon'] }} text-gray-400 dark:text-gray-500 w-5 text-center text-sm"></i>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __($childItem['text_key']) }}</span>
                                    </div>
                                    <select
                                        name="menu_visibilities[{{ $childFullKey }}]"
                                        x-model="visibilities['{{ $childFullKey }}']"
                                        class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        @foreach ($childAllowed as $vis)
                                            <option value="{{ $vis->value }}" {{ $childCurrentValue === $vis->value ? 'selected' : '' }}>
                                                {{ __($vis->translationKey()) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- リセットボタン -->
        <div class="mt-4 text-right">
            <button type="button" @click="resetToDefaults()" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
                <i class="fas fa-undo mr-1"></i>{{ __('admin/settings/base/mode.reset_to_defaults') }}
            </button>
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
    <div class="text-center mb-4">
        <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400">
            <i class="fas fa-exclamation-triangle text-3xl"></i>
        </div>
    </div>
    <div class="modal-body">
        <h2 class="modal-title">{{ __('admin/settings/base/mode.switch_modal_title') }}</h2>
        <div class="modal-message text-left">
            {{-- かんたんモードへの切替 --}}
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
            {{-- 詳細モードへの切替 --}}
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
<script @cspNonce>
function adminModeSettings() {
    const defaults = @json(collect($simpleDefaults)->map(fn($v) => $v instanceof \App\Enums\MenuVisibility ? $v->value : (int) $v)->toArray());
    const saved = @json($currentVisibilities);
    const original = '{{ $currentMode->value }}';

    return {
        selectedMode: original,
        originalMode: original,
        pendingMode: null,
        _skipWatch: false,
        visibilities: { ...saved },

        init() {
            this.$watch('selectedMode', (newVal, oldVal) => {
                if (this._skipWatch) {
                    this._skipWatch = false;
                    return;
                }
                // モードが変わった場合のみモーダルを表示
                if (newVal !== this.originalMode) {
                    this.pendingMode = newVal;
                    // 一旦元に戻してモーダルで確認
                    this._skipWatch = true;
                    this.selectedMode = this.originalMode;
                    this.$nextTick(() => {
                        this.openSwitchModal();
                    });
                }
            });
        },

        openSwitchModal() {
            const modal = document.getElementById('modeSwitchModal');
            if (modal) {
                const alpineData = Alpine.$data(modal);
                if (alpineData) {
                    alpineData.show = true;
                }
            }
        },

        closeSwitchModal() {
            const modal = document.getElementById('modeSwitchModal');
            if (modal) {
                const alpineData = Alpine.$data(modal);
                if (alpineData) {
                    alpineData.show = false;
                }
            }
        },

        confirmSwitch() {
            this._skipWatch = true;
            this.selectedMode = this.pendingMode;
            this.pendingMode = null;
            this.closeSwitchModal();
        },

        cancelSwitch() {
            this.pendingMode = null;
            this.closeSwitchModal();
        },

        resetToDefaults() {
            this.visibilities = { ...defaults };
        }
    };
}
</script>
@endpush
