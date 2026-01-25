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

@props([
    'name' => 'password',
    'id' => 'password',
    'required' => true,

    // 新しく追加するパスワードルール（JSにも渡す）
    'minLength' => 8,
    'requireUppercase' => true,
    'requireLowercase' => true,
    'requireNumber' => true,
    'requireSymbol' => false,

    // 推奨長（デフォルト12）
    'recommendedLength' => 12,

    // 確認欄の表示を制御（true = 表示, false = 非表示）
    'showConfirmation' => false,
    
    // 変更時のみ確認欄を表示（true = 入力時に確認欄を表示, false = 常に表示）
    'showConfirmationOnChange' => false,
])

@php
    // 長さの要件（常に必須）
    $lengthBase = $minLength < $recommendedLength
        ? __('components.password_messages.requirements.length_full', ['min' => $minLength, 'recommended' => $recommendedLength])
        : __('components.password_messages.requirements.length_simple', ['min' => $minLength]);
    $lengthText = $lengthBase . '（' . __('common.required') . '）';

    // 小文字の要件（必須 or 任意）
    if ($requireLowercase) {
        $lowercaseText = __('components.password_messages.requirements.lowercase') . '（' . __('common.required') . '）';
    } else {
        $lowercaseText = __('components.password_messages.requirements.lowercase_optional_note') . '（' . __('common.optional') . '）';
    }

    // 数字の要件（必須 or 任意）
    if ($requireNumber) {
        $numberText = __('components.password_messages.requirements.number') . '（' . __('common.required') . '）';
    } else {
        $numberText = __('components.password_messages.requirements.number_optional_note') . '（' . __('common.optional') . '）';
    }

    // 大文字の要件（必須 or 任意）
    if ($requireUppercase) {
        $uppercaseText = __('components.password_messages.requirements.uppercase') . '（' . __('common.required') . '）';
    } else {
        $uppercaseText = __('components.password_messages.requirements.uppercase_optional_note') . '（' . __('common.optional') . '）';
    }

    // 記号の要件（必須 or 任意）
    if ($requireSymbol) {
        $symbolText = __('components.password_messages.requirements.symbol') . '（' . __('common.required') . '）';
    } else {
        $symbolText = __('components.password_messages.requirements.symbol_optional_note') . '（' . __('common.optional') . '）';
    }
@endphp

<div x-data="passwordTools({
        minLength: {{ $minLength }},
        recommendedLength: {{ $recommendedLength }},
        requireUppercase: {{ $requireUppercase ? 'true' : 'false' }},
        requireLowercase: {{ $requireLowercase ? 'true' : 'false' }},
        requireNumber: {{ $requireNumber ? 'true' : 'false' }},
        requireSymbol: {{ $requireSymbol ? 'true' : 'false' }},
        showConfirmation: {{ $showConfirmation ? 'true' : 'false' }},
        showConfirmationOnChange: {{ $showConfirmationOnChange ? 'true' : 'false' }}
    })"
    data-msg-error="{{ __('components.password_messages.error') }}"
    data-msg-weak="{{ __('components.password_messages.requirements.weak') }}"
    data-msg-normal="{{ __('components.password_messages.requirements.normal') }}"
    data-msg-strong="{{ __('components.password_messages.requirements.strong') }}"
    data-msg-very-strong="{{ __('components.password_messages.requirements.very_strong') }}">
    <div class="relative">
        <input
            :type="showPassword ? 'text' : 'password'"
            id="{{ $id }}"
            name="{{ $name }}"
            x-model="password"
            @if($required) required @endif
            autocomplete="new-password"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-800 dark:border-gray-500 dark:text-white password-input"
        />

        <!-- パスワードツールバー -->
        <div class="absolute top-0 right-2 h-full flex items-center gap-1" role="toolbar" aria-label="{{ __('components.password_messages.toolbar_label') }}">
            <!-- 自動生成ボタン -->
            <div class="group relative">
                <button 
                    type="button" 
                    @click="generatePassword()"
                    class="px-2 py-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 transition-colors"
                    aria-label="{{ __('components.password_messages.tooltip.generate') }}"
                    title="{{ __('components.password_messages.tooltip.generate') }}">
                    <i class="fa-solid fa-random" aria-hidden="true"></i>
                </button>
                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10 pointer-events-none" role="tooltip">
                    {{ __('components.password_messages.tooltip.generate') }}
                </span>
            </div>

            <!-- コピーボタン -->
            <div class="group relative">
                <button 
                    type="button" 
                    @click="copyPassword()"
                    class="px-2 py-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                    aria-label="{{ __('components.password_messages.tooltip.copy') }}"
                    title="{{ __('components.password_messages.tooltip.copy') }}">
                    <i class="fa-solid fa-copy" aria-hidden="true"></i>
                </button>
                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10 pointer-events-none" role="tooltip">
                    {{ __('components.password_messages.tooltip.copy') }}
                </span>
            </div>

            <!-- 表示切り替えボタン -->
            <div class="group relative">
                <button 
                    type="button" 
                    @click="togglePasswordVisibility()"
                    class="px-2 py-1 text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white transition-colors"
                    :aria-pressed="showPassword ? 'true' : 'false'"
                    aria-label="{{ __('components.password_messages.tooltip.toggle') }}"
                    title="{{ __('components.password_messages.tooltip.toggle') }}">
                    <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" aria-hidden="true"></i>
                </button>
                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10 pointer-events-none" role="tooltip">
                    {{ __('components.password_messages.tooltip.toggle') }}
                </span>
            </div>
        </div>
    </div>

    @if($showConfirmation)
        <fieldset x-show="shouldShowConfirmation" x-transition class="mt-4">
            <legend class="text-gray-700 dark:text-gray-300">{{ __('common.password') }}（{{ __('common.confirm') }}）</legend>
            <div class="relative">
                <input
                    :type="showPassword ? 'text' : 'password'"
                    id="{{ $id }}_confirmation"
                    name="{{ $name }}_confirmation"
                    x-model="passwordConfirmation"
                    :required="shouldShowConfirmation && {{ $required ? 'true' : 'false' }}"
                    autocomplete="new-password"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-800 dark:border-gray-500 dark:text-white password-confirmation-input"
                />
                <!-- パスワード一致判定アイコン -->
                <div x-show="showMatchIndicator" x-transition class="absolute top-0 right-2 h-full flex items-center" x-html="matchIconHtml">
                </div>
            </div>
        </fieldset>
    @endif



    <p x-text="strengthMessage" class="text-sm mt-1 text-gray-700 dark:text-gray-300 h-[1em]"></p>

    <div class="h-2 w-32 bg-gray-200 dark:bg-gray-700 rounded-lg mt-2">
        <div :class="strengthBarColor" class="h-2 rounded-lg transition-all" :style="`width: ${strengthBarWidth}`"></div>
    </div>

    <!-- パスワード要件リスト -->
    <ul class="text-sm mt-2 text-gray-700 dark:text-gray-300 space-y-1">
        <li class="flex items-center">
            <i :class="requirementIconClass('lowercase')" aria-hidden="true"></i>
            <span>{{ $lowercaseText }}</span>
        </li>
        <li class="flex items-center">
            <i :class="requirementIconClass('number')" aria-hidden="true"></i>
            <span>{{ $numberText }}</span>
        </li>
        <li class="flex items-center">
            <i :class="requirementIconClass('length')" aria-hidden="true"></i>
            <span>{{ $lengthText }}</span>
        </li>
        <li class="flex items-center">
            <i :class="requirementIconClass('uppercase')" aria-hidden="true"></i>
            <span>{{ $uppercaseText }}</span>
        </li>
        <li class="flex items-center">
            <i :class="requirementIconClass('symbol')" aria-hidden="true"></i>
            <span>{{ $symbolText }}</span>
        </li>
    </ul>
</div>






