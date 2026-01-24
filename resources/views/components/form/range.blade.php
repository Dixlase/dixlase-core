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
    'labelColors' => [],  // ラベルごとのアクティブ色 [0 => 'green', 1 => 'yellow', 2 => 'orange', 3 => 'red']
    'showValue' => true,  // 現在値を表示するか
    'showLabels' => true, // ラベルを表示するか
    'xModel' => null,
    'onchange' => null,
    'ariaLabel' => null,
    'ariaDescribedby' => null,
    'defaultValue' => null, // デフォルト値（変更検出用）
    'modifiedColor' => 'amber', // 変更時の色
])

@php
    $inputId = $id ?? $name;
    $currentValue = old($name, $value);
@endphp

@php
    // xModelが指定されている場合は親のx-dataを使用するため、ここではx-dataを出力しない
    $hasExternalXData = !empty($xModel);
@endphp
<div class="range-slider-container {{ $class }}" @if(!$hasExternalXData) x-data="{ rangeValue: {{ $currentValue }} }" @endif>
    {{-- Range Input --}}
    <input type="range"
        @if ($inputId) id="{{ $inputId }}" @endif
        @if ($name) name="{{ $name }}" @endif
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

    @php
        // xModelが指定されている場合はその変数名を使用、そうでなければrangeValueを使用
        $modelVar = $xModel ?: 'rangeValue';
        
        // ラベル数を取得
        $labelCount = count($labels);
        
        // 色のマッピング（Tailwind CSSクラス）
        $colorClasses = [
            'blue' => [
                'text' => 'text-blue-600 dark:text-blue-400',
                'bg' => 'bg-blue-600 dark:bg-blue-400',
            ],
            'green' => [
                'text' => 'text-green-600 dark:text-green-400',
                'bg' => 'bg-green-600 dark:bg-green-400',
            ],
            'yellow' => [
                'text' => 'text-yellow-600 dark:text-yellow-400',
                'bg' => 'bg-yellow-500 dark:bg-yellow-400',
            ],
            'orange' => [
                'text' => 'text-orange-600 dark:text-orange-400',
                'bg' => 'bg-orange-500 dark:bg-orange-400',
            ],
            'red' => [
                'text' => 'text-red-600 dark:text-red-400',
                'bg' => 'bg-red-600 dark:bg-red-400',
            ],
            'amber' => [
                'text' => 'text-amber-600 dark:text-amber-400',
                'bg' => 'bg-amber-500 dark:bg-amber-400',
            ],
        ];
    @endphp

    {{-- Labels Below Slider --}}
    @if ($showLabels && !empty($labels))
        <div class="relative mt-2 text-xs text-gray-500 dark:text-gray-400" style="margin-left: 0.625rem; margin-right: 0.625rem;">
            @foreach ($labels as $labelValue => $labelText)
                @php
                    // ラベルの位置を計算（0% ～ 100%）
                    $position = $labelCount > 1 ? ($labelValue - $min) / ($max - $min) * 100 : 50;
                    
                    // このラベルの色を取得（指定がなければblue、デフォルト値と異なる場合はmodifiedColor）
                    $labelColor = $labelColors[$labelValue] ?? 'blue';
                    $modifiedColorClass = $colorClasses[$modifiedColor]['text'] ?? $colorClasses['amber']['text'];
                    $modifiedBgClass = $colorClasses[$modifiedColor]['bg'] ?? $colorClasses['amber']['bg'];
                    $textColorClass = $colorClasses[$labelColor]['text'] ?? $colorClasses['blue']['text'];
                    $bgColorClass = $colorClasses[$labelColor]['bg'] ?? $colorClasses['blue']['bg'];
                @endphp
                <span class="absolute flex flex-col items-center cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                      style="left: {{ $position }}%; transform: translateX(-50%);"
                      @click="{{ $modelVar }} = {{ $labelValue }}"
                      :class="{ 
                          @if($defaultValue !== null)
                          '{{ $modifiedColorClass }} font-semibold': {{ $modelVar }} == {{ $labelValue }} && {{ $modelVar }} != {{ $defaultValue }},
                          @endif
                          '{{ $textColorClass }} font-semibold': {{ $modelVar }} == {{ $labelValue }} @if($defaultValue !== null) && {{ $modelVar }} == {{ $defaultValue }} @endif
                      }">
                    <span class="w-1 h-1 mb-1 rounded-full"
                          :class="
                              {{ $modelVar }} == {{ $labelValue }} 
                              @if($defaultValue !== null)
                              ? ({{ $modelVar }} != {{ $defaultValue }} ? '{{ $modifiedBgClass }}' : '{{ $bgColorClass }}')
                              @else
                              ? '{{ $bgColorClass }}'
                              @endif
                              : 'bg-gray-300 dark:bg-gray-600'
                          "></span>
                    <span class="whitespace-nowrap">{{ __($labelText) }}</span>
                </span>
            @endforeach
            {{-- スペーサー（ラベルの高さを確保） --}}
            <span class="invisible">dummy</span>
        </div>
    @endif

    {{-- Current Value Display --}}
    @if ($showValue && empty($labels))
        <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ $min }}</span>
            <span class="font-medium text-blue-600 dark:text-blue-400" x-text="{{ $modelVar }}"></span>
            <span>{{ $max }}</span>
        </div>
    @endif
</div>
