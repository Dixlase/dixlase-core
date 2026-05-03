{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-radio-card-group />

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

{{--
ラベルと説明付きのラジオボタンカードグループコンポーネント

使用例:
<x-form-radio-card-group
    name="preset"
    :options="[
        ['value' => 'strict', 'label' => '厳格', 'description' => '最も安全な設定', 'icon' => 'fas fa-shield-alt', 'color' => 'green'],
        ['value' => 'balanced', 'label' => 'バランス', 'description' => '推奨設定', 'icon' => 'fas fa-balance-scale', 'color' => 'blue'],
        ['value' => 'development', 'label' => '開発', 'description' => '開発用', 'icon' => 'fas fa-code', 'color' => 'yellow', 'badge' => '開発専用'],
    ]"
    :value="$currentValue"
    xModel="preset"
    :columns="4"
    color="primary"
    variant="filled"
    :showCheck="true"
/>

オプション配列の各要素:
- value: (必須) ラジオボタンの値
- label: (必須) 表示ラベル
- description: (任意) 説明文
- icon: (任意) FontAwesomeアイコンクラス
- color: (任意) 個別オプションの色（グローバル設定を上書き）
- badge: (任意) バッジテキスト（開発専用など）
- badgeColor: (任意) バッジの色 (yellow, red, green, blue, gray) デフォルト: yellow
- disabled: (任意) 無効化フラグ
- features: (任意) 機能リスト（配列）

グローバルプロパティ:
- color: 選択時の色 (primary, secondary, success, warning, danger, blue, green, yellow, orange, red, purple, gray)
- variant: スタイル (filled=背景色あり, outlined=ボーダーのみ)
- showCheck: チェックアイコンを表示するか
--}}

@props([
    'name' => null,
    'options' => [],
    'value' => null,
    'xModel' => null,
    'columns' => 4,           // グリッドの列数 (1, 2, 3, 4)
    'direction' => 'grid',    // 'grid', 'row', 'col'
    'disabled' => false,
    'class' => '',
    'color' => 'primary',     // 選択時の色
    'variant' => 'filled',    // 'filled' (背景色あり) or 'outlined' (ボーダーのみ)
    'showCheck' => true,      // チェックアイコンを表示
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
            3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
            4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
            default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        },
    };
    
    // 色のマッピング（ライト/ダークモード対応）
    $colorClasses = [
        'primary' => [
            'border' => 'border-blue-600 dark:border-blue-500',
            'ring' => 'ring-blue-600 dark:ring-blue-500',
            'bg' => 'bg-blue-50 dark:bg-blue-900/30',
            'text' => 'text-blue-700 dark:text-blue-300',
            'check' => 'text-blue-600 dark:text-blue-400',
        ],
        'secondary' => [
            'border' => 'border-gray-600 dark:border-gray-400',
            'ring' => 'ring-gray-600 dark:ring-gray-400',
            'bg' => 'bg-gray-100 dark:bg-gray-700/50',
            'text' => 'text-gray-700 dark:text-gray-200',
            'check' => 'text-gray-600 dark:text-gray-300',
        ],
        'success' => [
            'border' => 'border-green-600 dark:border-green-500',
            'ring' => 'ring-green-600 dark:ring-green-500',
            'bg' => 'bg-green-50 dark:bg-green-900/30',
            'text' => 'text-green-700 dark:text-green-300',
            'check' => 'text-green-600 dark:text-green-400',
        ],
        'warning' => [
            'border' => 'border-yellow-500 dark:border-yellow-400',
            'ring' => 'ring-yellow-500 dark:ring-yellow-400',
            'bg' => 'bg-yellow-50 dark:bg-yellow-900/30',
            'text' => 'text-yellow-700 dark:text-yellow-300',
            'check' => 'text-yellow-600 dark:text-yellow-400',
        ],
        'danger' => [
            'border' => 'border-red-600 dark:border-red-500',
            'ring' => 'ring-red-600 dark:ring-red-500',
            'bg' => 'bg-red-50 dark:bg-red-900/30',
            'text' => 'text-red-700 dark:text-red-300',
            'check' => 'text-red-600 dark:text-red-400',
        ],
        'blue' => [
            'border' => 'border-blue-500 dark:border-blue-400',
            'ring' => 'ring-blue-500 dark:ring-blue-400',
            'bg' => 'bg-blue-50 dark:bg-blue-900/20',
            'text' => 'text-blue-600 dark:text-blue-300',
            'check' => 'text-blue-600 dark:text-blue-400',
        ],
        'green' => [
            'border' => 'border-green-500 dark:border-green-400',
            'ring' => 'ring-green-500 dark:ring-green-400',
            'bg' => 'bg-green-50 dark:bg-green-900/30',
            'text' => 'text-green-600 dark:text-green-300',
            'check' => 'text-green-600 dark:text-green-400',
        ],
        'yellow' => [
            'border' => 'border-yellow-500 dark:border-yellow-400',
            'ring' => 'ring-yellow-500 dark:ring-yellow-400',
            'bg' => 'bg-yellow-50 dark:bg-yellow-900/30',
            'text' => 'text-yellow-600 dark:text-yellow-300',
            'check' => 'text-yellow-600 dark:text-yellow-400',
        ],
        'orange' => [
            'border' => 'border-orange-500 dark:border-orange-400',
            'ring' => 'ring-orange-500 dark:ring-orange-400',
            'bg' => 'bg-orange-50 dark:bg-orange-900/30',
            'text' => 'text-orange-600 dark:text-orange-300',
            'check' => 'text-orange-600 dark:text-orange-400',
        ],
        'red' => [
            'border' => 'border-red-500 dark:border-red-400',
            'ring' => 'ring-red-500 dark:ring-red-400',
            'bg' => 'bg-red-50 dark:bg-red-900/30',
            'text' => 'text-red-600 dark:text-red-300',
            'check' => 'text-red-600 dark:text-red-400',
        ],
        'purple' => [
            'border' => 'border-purple-500 dark:border-purple-400',
            'ring' => 'ring-purple-500 dark:ring-purple-400',
            'bg' => 'bg-purple-50 dark:bg-purple-900/30',
            'text' => 'text-purple-600 dark:text-purple-300',
            'check' => 'text-purple-600 dark:text-purple-400',
        ],
        'gray' => [
            'border' => 'border-gray-500 dark:border-gray-400',
            'ring' => 'ring-gray-500 dark:ring-gray-400',
            'bg' => 'bg-gray-100 dark:bg-gray-700/50',
            'text' => 'text-gray-600 dark:text-gray-300',
            'check' => 'text-gray-600 dark:text-gray-400',
        ],
    ];
    
    // バッジ色のマッピング
    $badgeColorClasses = [
        'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
        'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
        'green' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
        'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
    ];
    
    // グローバル色設定
    $globalColors = $colorClasses[$color] ?? $colorClasses['primary'];
@endphp

<div class="{{ $layoutClasses }} {{ $class }}" x-data="{ selected: '{{ $currentValue }}' }">
    @foreach ($options as $option)
        @php
            $optionValue = $option['value'] ?? '';
            $optionLabel = $option['label'] ?? '';
            $optionDescription = $option['description'] ?? null;
            $optionIcon = $option['icon'] ?? null;
            $optionColor = $option['color'] ?? null;
            $optionBadge = $option['badge'] ?? null;
            $optionBadgeColor = $option['badgeColor'] ?? 'yellow';
            $optionDisabled = $option['disabled'] ?? false;
            $optionFeatures = $option['features'] ?? [];
            
            // 個別色設定があればそれを使用、なければグローバル設定
            $colors = $optionColor ? ($colorClasses[$optionColor] ?? $globalColors) : $globalColors;
            $badgeClass = $badgeColorClasses[$optionBadgeColor] ?? $badgeColorClasses['yellow'];
            
            $isDisabled = $disabled || $optionDisabled;
            
            // variant に応じた選択時のクラス
            $selectedClasses = $variant === 'outlined' 
                ? $colors['border'] . ' ring-3 ' . $colors['ring']
                : $colors['border'] . ' ring-3 ' . $colors['ring'] . ' ' . $colors['bg'];
        @endphp
        
        <label class="relative flex rounded-lg border p-4 pr-6 shadow-sm focus:outline-none transition-all duration-150 {{ $isDisabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
               @if ($modelVar)
                   :class="{{ $modelVar }} === '{{ $optionValue }}' 
                       ? '{{ $selectedClasses }}' 
                       : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 {{ $isDisabled ? '' : 'hover:border-gray-300 dark:hover:border-gray-500' }}'"
               @else
                   :class="selected === '{{ $optionValue }}' 
                       ? '{{ $selectedClasses }}' 
                       : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 {{ $isDisabled ? '' : 'hover:border-gray-300 dark:hover:border-gray-500' }}'"
               @endif
               @if (!$isDisabled) @click="selected = '{{ $optionValue }}'" @endif
        >
            <input type="radio" 
                   name="{{ $name }}" 
                   value="{{ $optionValue }}"
                   @if ($modelVar) x-model="{{ $modelVar }}" @else x-model="selected" @endif
                   @if (!$modelVar && $currentValue == $optionValue) checked @endif
                   @if ($isDisabled) disabled @endif
                   class="sr-only">
            
            <span class="flex flex-1">
                <span class="flex flex-col justify-center">
                    <span class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white"
                          @if ($modelVar)
                              :class="{{ $modelVar }} === '{{ $optionValue }}' ? '{{ $colors['text'] }}' : 'text-gray-900 dark:text-white'"
                          @else
                              :class="selected === '{{ $optionValue }}' ? '{{ $colors['text'] }}' : 'text-gray-900 dark:text-white'"
                          @endif
                    >
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
            
            {{-- チェックアイコン --}}
            @if ($showCheck)
                <span class="absolute top-1 right-2 flex items-center justify-center"
                      @if ($modelVar)
                          x-show="{{ $modelVar }} === '{{ $optionValue }}'"
                      @else
                          x-show="selected === '{{ $optionValue }}'"
                      @endif
                      x-transition:enter="transition ease-out duration-100"
                      x-transition:enter-start="opacity-0 scale-75"
                      x-transition:enter-end="opacity-100 scale-100"
                      x-transition:leave="transition ease-in duration-75"
                      x-transition:leave-start="opacity-100 scale-100"
                      x-transition:leave-end="opacity-0 scale-75"
                >
                    <i class="fas fa-check-circle text-lg {{ $colors['check'] }}"></i>
                </span>
            @endif
            
            {{-- ボーダーオーバーレイ --}}
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
