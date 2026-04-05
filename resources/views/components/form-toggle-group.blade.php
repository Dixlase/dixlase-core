{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-toggle-group />

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

{{--
オプション配列の形式:
- シンプル形式: ['value' => 'label', ...]
- 拡張形式: [
    ['value' => 'xxx', 'label' => 'Label', 'description' => '説明', 'icon' => 'fas fa-xxx'],
    ...
  ]
--}}

@props([
    'name' => '',
    'options' => [],
    'values' => [],
    'disabled' => false,
    'xBindDisabled' => null,  // Alpine.js動的disabled用
    'class' => '',
    'flexDirection' => 'col',
    'cardStyle' => false,  // カード形式で表示
    'color' => 'blue',  // ON時の背景色
])

@php
    // 色のマッピング（ライト/ダークモード対応）
    $colorClasses = [
        'primary' => 'peer-checked:bg-blue-600 dark:peer-checked:bg-blue-500',
        'secondary' => 'peer-checked:bg-gray-600 dark:peer-checked:bg-gray-400',
        'success' => 'peer-checked:bg-green-600 dark:peer-checked:bg-green-500',
        'warning' => 'peer-checked:bg-yellow-500 dark:peer-checked:bg-yellow-400',
        'danger' => 'peer-checked:bg-red-600 dark:peer-checked:bg-red-500',
        'blue' => 'peer-checked:bg-blue-600 dark:peer-checked:bg-blue-500',
        'green' => 'peer-checked:bg-green-600 dark:peer-checked:bg-green-500',
        'yellow' => 'peer-checked:bg-yellow-500 dark:peer-checked:bg-yellow-400',
        'orange' => 'peer-checked:bg-orange-500 dark:peer-checked:bg-orange-400',
        'red' => 'peer-checked:bg-red-600 dark:peer-checked:bg-red-500',
        'purple' => 'peer-checked:bg-purple-600 dark:peer-checked:bg-purple-500',
        'gray' => 'peer-checked:bg-gray-600 dark:peer-checked:bg-gray-400',
    ];
    
    // disabled時の色マッピング
    $disabledColorClasses = [
        'primary' => 'bg-blue-900 dark:bg-blue-900',
        'secondary' => 'bg-gray-700 dark:bg-gray-700',
        'success' => 'bg-green-900 dark:bg-green-900',
        'warning' => 'bg-yellow-800 dark:bg-yellow-800',
        'danger' => 'bg-red-900 dark:bg-red-900',
        'blue' => 'bg-blue-900 dark:bg-blue-900',
        'green' => 'bg-green-900 dark:bg-green-900',
        'yellow' => 'bg-yellow-800 dark:bg-yellow-800',
        'orange' => 'bg-orange-800 dark:bg-orange-800',
        'red' => 'bg-red-900 dark:bg-red-900',
        'purple' => 'bg-purple-900 dark:bg-purple-900',
        'gray' => 'bg-gray-700 dark:bg-gray-700',
    ];
    
    $checkedClass = $colorClasses[$color] ?? $colorClasses['blue'];
    $disabledCheckedClass = $disabledColorClasses[$color] ?? $disabledColorClasses['blue'];
@endphp

<div @class([
    'flex',
    'gap-3',
    'flex-col' => $flexDirection == 'col',
    'flex-row flex-wrap' => $flexDirection == 'row',
    $class,
]) @if($xBindDisabled) :class="{ 'opacity-50': {{ $xBindDisabled }} }" @endif>
    @foreach ($options as $key => $option)
        @php
            // シンプル形式と拡張形式の両方に対応
            if (is_array($option)) {
                $optionValue = $option['value'] ?? $key;
                $optionLabel = $option['label'] ?? '';
                $optionDescription = $option['description'] ?? null;
                $optionIcon = $option['icon'] ?? null;
            } else {
                $optionValue = $key;
                $optionLabel = $option;
                $optionDescription = null;
                $optionIcon = null;
            }
            $isChecked = in_array($optionValue, $values);
            $toggleId = $name . '_' . $optionValue;
        @endphp
        
        @if($cardStyle)
            {{-- カード形式 --}}
            <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors {{ $disabled ? 'opacity-50' : '' }}">
                <label for="{{ $toggleId }}" class="flex items-center gap-3 {{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                    <div class="relative inline-flex items-center flex-shrink-0">
                        <input type="checkbox"
                               id="{{ $toggleId }}"
                               name="{{ $name }}[]"
                               value="{{ $optionValue }}"
                               {{ $isChecked ? 'checked' : '' }}
                               @if($disabled) disabled @endif
                               @if($xBindDisabled) x-bind:disabled="{{ $xBindDisabled }}" @endif
                               class="sr-only peer">
                        <div class="w-11 h-6 rounded-full transition-colors peer-focus:outline-none
                            {{ $disabled && $isChecked ? $disabledCheckedClass : '' }}
                            {{ $disabled && !$isChecked ? 'bg-gray-300 dark:bg-gray-700' : '' }}
                            {{ !$disabled ? 'bg-gray-200 dark:bg-gray-600 ' . $checkedClass : '' }}
                        "></div>
                        <div class="absolute left-1 top-1 w-4 h-4 bg-white border border-gray-300 rounded-full transition-all peer-checked:translate-x-full peer-checked:border-white"></div>
                    </div>
                    @if ($optionIcon)
                        <i class="{{ $optionIcon }} text-gray-500 dark:text-gray-400"></i>
                    @endif
                    <div class="flex-1">
                        <span class="font-medium {{ $disabled ? 'text-gray-400 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ __($optionLabel) }}
                        </span>
                        @if ($optionDescription)
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __($optionDescription) }}</p>
                        @endif
                    </div>
                </label>
            </div>
        @else
            {{-- シンプル形式 --}}
            <div class="flex items-center space-x-3">
                <label for="{{ $toggleId }}" class="relative inline-flex items-center {{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                    <input type="checkbox"
                           id="{{ $toggleId }}"
                           name="{{ $name }}[]"
                           value="{{ $optionValue }}"
                           {{ $isChecked ? 'checked' : '' }}
                           @if($disabled) disabled @endif
                           @if($xBindDisabled) x-bind:disabled="{{ $xBindDisabled }}" @endif
                           class="sr-only peer">
                    <div class="w-11 h-6 rounded-full transition-colors peer-focus:outline-none
                        {{ $disabled && $isChecked ? $disabledCheckedClass : '' }}
                        {{ $disabled && !$isChecked ? 'bg-gray-300 dark:bg-gray-700' : '' }}
                        {{ !$disabled ? 'bg-gray-200 dark:bg-gray-600 ' . $checkedClass : '' }}
                    "></div>
                    <div class="absolute left-1 top-1 w-4 h-4 bg-white border border-gray-300 rounded-full transition-all peer-checked:translate-x-full peer-checked:border-white"></div>
                </label>
                @if ($optionIcon)
                    <i class="{{ $optionIcon }} text-gray-500 dark:text-gray-400"></i>
                @endif
                <div>
                    <span class="text-sm {{ $disabled ? 'text-gray-400 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ __($optionLabel) }}
                    </span>
                    @if ($optionDescription)
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __($optionDescription) }}</p>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>
