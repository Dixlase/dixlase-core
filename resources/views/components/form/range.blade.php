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
    'id' => null,
    'name' => null,
    'value' => 0,
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'disabled' => false,
    'class' => '',
    'labels' => [],       // ラベル配列 [0 => 'Low', 1 => 'Medium', 2 => 'High']
    'showValue' => true,  // 現在値を表示するか
    'showLabels' => true, // ラベルを表示するか
    'xModel' => null,
    'onchange' => null,
    'ariaLabel' => null,
    'ariaDescribedby' => null,
])

@php
    $inputId = $id ?? $name;
    $currentValue = old($name, $value);
@endphp

<div class="range-slider-container {{ $class }}" x-data="{ rangeValue: {{ $currentValue }} }">
    {{-- Range Input --}}
    <input type="range"
        id="{{ $inputId }}"
        name="{{ $name }}"
        min="{{ $min }}"
        max="{{ $max }}"
        step="{{ $step }}"
        @if ($disabled) disabled @endif
        @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
        @if ($ariaDescribedby) aria-describedby="{{ $ariaDescribedby }}" @endif
        @if ($xModel) x-model="{{ $xModel }}" @else x-model="rangeValue" @endif
        @if ($onchange) @change="{{ $onchange }}" @endif
        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 accent-blue-600 dark:accent-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        value="{{ $currentValue }}"
    >

    {{-- Labels Below Slider --}}
    @if ($showLabels && !empty($labels))
        <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
            @foreach ($labels as $labelValue => $labelText)
                <span class="flex flex-col items-center cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                      @click="rangeValue = {{ $labelValue }}; $refs.rangeInput && ($refs.rangeInput.value = {{ $labelValue }})"
                      :class="{ 'text-blue-600 dark:text-blue-400 font-semibold': rangeValue == {{ $labelValue }} }">
                    <span class="w-1 h-1 mb-1 rounded-full"
                          :class="rangeValue == {{ $labelValue }} ? 'bg-blue-600 dark:bg-blue-400' : 'bg-gray-300 dark:bg-gray-600'"></span>
                    {{ __($labelText) }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- Current Value Display --}}
    @if ($showValue && empty($labels))
        <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ $min }}</span>
            <span class="font-medium text-blue-600 dark:text-blue-400" x-text="rangeValue"></span>
            <span>{{ $max }}</span>
        </div>
    @endif
</div>

@push('styles')
<style>
/* Flowbite-inspired range slider styling */
.range-slider-container input[type="range"] {
    -webkit-appearance: none;
    appearance: none;
    background: transparent;
}

.range-slider-container input[type="range"]::-webkit-slider-runnable-track {
    height: 0.5rem;
    border-radius: 0.5rem;
    background: linear-gradient(to right, 
        rgb(37 99 235) 0%, 
        rgb(37 99 235) calc(var(--range-progress, 0) * 100%), 
        rgb(229 231 235) calc(var(--range-progress, 0) * 100%), 
        rgb(229 231 235) 100%
    );
}

.dark .range-slider-container input[type="range"]::-webkit-slider-runnable-track {
    background: linear-gradient(to right, 
        rgb(59 130 246) 0%, 
        rgb(59 130 246) calc(var(--range-progress, 0) * 100%), 
        rgb(55 65 81) calc(var(--range-progress, 0) * 100%), 
        rgb(55 65 81) 100%
    );
}

.range-slider-container input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 50%;
    background: rgb(37 99 235);
    cursor: pointer;
    margin-top: -0.375rem;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    transition: transform 0.15s ease-in-out;
}

.dark .range-slider-container input[type="range"]::-webkit-slider-thumb {
    background: rgb(59 130 246);
}

.range-slider-container input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.1);
}

.range-slider-container input[type="range"]::-moz-range-track {
    height: 0.5rem;
    border-radius: 0.5rem;
    background: rgb(229 231 235);
}

.dark .range-slider-container input[type="range"]::-moz-range-track {
    background: rgb(55 65 81);
}

.range-slider-container input[type="range"]::-moz-range-thumb {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 50%;
    background: rgb(37 99 235);
    cursor: pointer;
    border: none;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    transition: transform 0.15s ease-in-out;
}

.dark .range-slider-container input[type="range"]::-moz-range-thumb {
    background: rgb(59 130 246);
}

.range-slider-container input[type="range"]::-moz-range-thumb:hover {
    transform: scale(1.1);
}

.range-slider-container input[type="range"]::-moz-range-progress {
    height: 0.5rem;
    border-radius: 0.5rem;
    background: rgb(37 99 235);
}

.dark .range-slider-container input[type="range"]::-moz-range-progress {
    background: rgb(59 130 246);
}

.range-slider-container input[type="range"]:disabled::-webkit-slider-thumb {
    background: rgb(156 163 175);
    cursor: not-allowed;
}

.range-slider-container input[type="range"]:disabled::-moz-range-thumb {
    background: rgb(156 163 175);
    cursor: not-allowed;
}
</style>
@endpush
