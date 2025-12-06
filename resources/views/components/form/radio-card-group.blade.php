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

{{--
ラベルと説明付きのラジオボタンカードグループコンポーネント

使用例:
<x-form.radio-card-group
    name="preset"
    :options="[
        ['value' => 'strict', 'label' => '厳格', 'description' => '最も安全な設定', 'icon' => 'fas fa-shield-alt', 'color' => 'green'],
        ['value' => 'balanced', 'label' => 'バランス', 'description' => '推奨設定', 'icon' => 'fas fa-balance-scale', 'color' => 'blue'],
        ['value' => 'development', 'label' => '開発', 'description' => '開発用', 'icon' => 'fas fa-code', 'color' => 'yellow', 'badge' => '開発専用'],
    ]"
    :value="$currentValue"
    xModel="preset"
    :columns="4"
/>

オプション配列の各要素:
- value: (必須) ラジオボタンの値
- label: (必須) 表示ラベル
- description: (任意) 説明文
- icon: (任意) FontAwesomeアイコンクラス
- color: (任意) アクティブ時の色 (blue, green, yellow, orange, red, gray) デフォルト: blue
- badge: (任意) バッジテキスト（開発専用など）
- badgeColor: (任意) バッジの色 (yellow, red, green, blue, gray) デフォルト: yellow
- disabled: (任意) 無効化フラグ
- features: (任意) 機能リスト（配列）
--}}

@props([
    'name' => null,
    'options' => [],
    'value' => null,
    'xModel' => null,
    'columns' => 4,        // グリッドの列数 (1, 2, 3, 4)
    'direction' => 'grid', // 'grid', 'row', 'col'
    'disabled' => false,
    'class' => '',
])

@php
    $currentValue = old($name, $value);
    $modelVar = $xModel ?: null;
    
    // レイアウトクラス
    $layoutClasses = match($direction) {
        'row' => 'flex flex-wrap gap-4',
        'col' => 'flex flex-col gap-3',
        default => 'grid gap-4 ' . match((int)$columns) {
            1 => 'grid-cols-1',
            2 => 'grid-cols-1 md:grid-cols-2',
            3 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3',
            4 => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4',
            default => 'grid-cols-1 md:grid-cols-2 xl:grid-cols-4',
        },
    };
    
    // 色のマッピング
    $colorClasses = [
        'blue' => [
            'border' => 'border-blue-500',
            'ring' => 'ring-blue-500',
            'bg' => 'bg-blue-50 dark:bg-blue-900/20',
            'text' => 'text-blue-600 dark:text-blue-400',
        ],
        'green' => [
            'border' => 'border-green-500',
            'ring' => 'ring-green-500',
            'bg' => 'bg-green-50 dark:bg-green-900/20',
            'text' => 'text-green-600 dark:text-green-400',
        ],
        'yellow' => [
            'border' => 'border-yellow-500',
            'ring' => 'ring-yellow-500',
            'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
            'text' => 'text-yellow-600 dark:text-yellow-400',
        ],
        'orange' => [
            'border' => 'border-orange-500',
            'ring' => 'ring-orange-500',
            'bg' => 'bg-orange-50 dark:bg-orange-900/20',
            'text' => 'text-orange-600 dark:text-orange-400',
        ],
        'red' => [
            'border' => 'border-red-500',
            'ring' => 'ring-red-500',
            'bg' => 'bg-red-50 dark:bg-red-900/20',
            'text' => 'text-red-600 dark:text-red-400',
        ],
        'purple' => [
            'border' => 'border-purple-500',
            'ring' => 'ring-purple-500',
            'bg' => 'bg-purple-50 dark:bg-purple-900/20',
            'text' => 'text-purple-600 dark:text-purple-400',
        ],
        'gray' => [
            'border' => 'border-gray-500',
            'ring' => 'ring-gray-500',
            'bg' => 'bg-gray-50 dark:bg-gray-900/20',
            'text' => 'text-gray-600 dark:text-gray-400',
        ],
    ];
    
    // バッジ色のマッピング
    $badgeColorClasses = [
        'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'green' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    ];
@endphp

<div class="{{ $layoutClasses }} {{ $class }}" x-data="{ selected: '{{ $currentValue }}' }">
    @foreach ($options as $option)
        @php
            $optionValue = $option['value'] ?? '';
            $optionLabel = $option['label'] ?? '';
            $optionDescription = $option['description'] ?? null;
            $optionIcon = $option['icon'] ?? null;
            $optionColor = $option['color'] ?? 'blue';
            $optionBadge = $option['badge'] ?? null;
            $optionBadgeColor = $option['badgeColor'] ?? 'yellow';
            $optionDisabled = $option['disabled'] ?? false;
            $optionFeatures = $option['features'] ?? [];
            
            $colors = $colorClasses[$optionColor] ?? $colorClasses['blue'];
            $badgeClass = $badgeColorClasses[$optionBadgeColor] ?? $badgeColorClasses['yellow'];
            
            $isDisabled = $disabled || $optionDisabled;
        @endphp
        
        <label class="relative flex cursor-pointer rounded-lg border p-4 shadow-sm focus:outline-none transition-all {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
               @if ($modelVar)
                   :class="{{ $modelVar }} === '{{ $optionValue }}' 
                       ? '{{ $colors['border'] }} ring-2 {{ $colors['ring'] }} {{ $colors['bg'] }}' 
                       : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'"
               @else
                   :class="selected === '{{ $optionValue }}' 
                       ? '{{ $colors['border'] }} ring-2 {{ $colors['ring'] }} {{ $colors['bg'] }}' 
                       : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'"
               @endif
               @click="selected = '{{ $optionValue }}'"
        >
            <input type="radio" 
                   name="{{ $name }}" 
                   value="{{ $optionValue }}"
                   @if ($modelVar) x-model="{{ $modelVar }}" @else x-model="selected" @endif
                   @if (!$modelVar && $currentValue == $optionValue) checked @endif
                   @if ($isDisabled) disabled @endif
                   class="sr-only">
            <span class="flex flex-1">
                <span class="flex flex-col">
                    <span class="flex items-center gap-2 text-sm font-medium {{ $colors['text'] }}">
                        @if ($optionIcon)
                            <i class="{{ $optionIcon }}"></i>
                        @endif
                        {{ __($optionLabel) }}
                        @if ($optionBadge)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                {{ __($optionBadge) }}
                            </span>
                        @endif
                    </span>
                    @if ($optionDescription)
                        <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __($optionDescription) }}
                        </span>
                    @endif
                    @if (!empty($optionFeatures))
                        <ul class="mt-2 text-xs text-gray-500 dark:text-gray-400 space-y-0.5 list-disc list-inside">
                            @foreach ($optionFeatures as $feature)
                                <li>{{ __($feature) }}</li>
                            @endforeach
                        </ul>
                    @endif
                </span>
            </span>
            <span class="pointer-events-none absolute -inset-px rounded-lg" 
                  @if ($modelVar)
                      :class="{{ $modelVar }} === '{{ $optionValue }}' ? 'border-2 {{ $colors['border'] }}' : 'border border-transparent'"
                  @else
                      :class="selected === '{{ $optionValue }}' ? 'border-2 {{ $colors['border'] }}' : 'border border-transparent'"
                  @endif
                  aria-hidden="true"></span>
        </label>
    @endforeach
</div>
