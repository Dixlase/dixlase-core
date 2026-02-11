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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- かんたんモード --}}
            <label class="block cursor-pointer">
                <input type="radio" name="admin_mode" value="0" x-model="selectedMode" class="sr-only peer">
                <div class="border-2 rounded-lg p-5 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-gray-300 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-700">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                            <i class="fas fa-magic text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin/settings/base/mode.simple_mode') }}</h3>
                                <span class="inline-block px-2 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded-full">{{ __('admin/settings/base/mode.recommended') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/base/mode.simple_mode_description') }}</p>
                            <ul class="mt-3 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-500 text-xs"></i>
                                    {{ __('admin/settings/base/mode.simple_feature_auto') }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-500 text-xs"></i>
                                    {{ __('admin/settings/base/mode.simple_feature_clean') }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-500 text-xs"></i>
                                    {{ __('admin/settings/base/mode.simple_feature_customize') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </label>

            {{-- 詳細モード --}}
            <label class="block cursor-pointer">
                <input type="radio" name="admin_mode" value="1" x-model="selectedMode" class="sr-only peer">
                <div class="border-2 rounded-lg p-5 transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 border-gray-300 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-700">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                            <i class="fas fa-cogs text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin/settings/base/mode.advanced_mode') }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/base/mode.advanced_mode_description') }}</p>
                            <ul class="mt-3 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-500 text-xs"></i>
                                    {{ __('admin/settings/base/mode.advanced_feature_full') }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-500 text-xs"></i>
                                    {{ __('admin/settings/base/mode.advanced_feature_control') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </label>
        </div>
    </section>

    <!-- かんたんモード: メニュー表示カスタマイズ -->
    <section x-show="selectedMode === '0' || selectedMode === 0" x-transition class="mb-8">
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
    <section x-show="selectedMode === '1' || selectedMode === 1" x-transition class="mb-8">
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
<script>
function adminModeSettings() {
    const defaults = @json(collect($simpleDefaults)->map(fn($v) => $v instanceof \App\Enums\MenuVisibility ? $v->value : (int) $v)->toArray());
    const saved = @json($currentVisibilities);

    return {
        selectedMode: '{{ $currentMode->value }}',
        visibilities: { ...saved },

        resetToDefaults() {
            this.visibilities = { ...defaults };
        }
    };
}
</script>
@endpush
