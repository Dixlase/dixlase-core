{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-form-button />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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

@props([
    'type' => 'button',      // ボタンのタイプ (button, submit, reset, link)
    'variant' => 'primary',  // ボタンの色バリエーション (primary, secondary, success, warning, danger)
    'size' => 'md',          // ボタンのサイズ (xs, sm, md, lg)
    'class' => '',           // カスタムクラス
    'label' => null,         // ボタンのテキスト（nullの場合はスロットを使用）
    'icon' => null,          // アイコンクラス (例: 'fas fa-save')
    'iconPosition' => 'left', // アイコンの位置 (left, right)
    'onclick' => null,       // onclick属性を追加
    'disabled' => false,     // ボタンを無効にする
    'form' => null,          // フォームのID
    'id' => null,            // ボタンのID
    'href' => null,          // リンク先URL (type='link'の時に使用)
    'xClick' => null,        // Alpine.js @click
    'xDisabled' => null,     // Alpine.js :disabled
    'xShow' => null,         // Alpine.js x-show
])

@php
    // バリエーションに応じたクラス設定
    $variantClasses = [
        // disabled:bg-blue-400 — the shared disabled:opacity-50 fades whatever
        // background the variant has. blue-600 sits on the violet side of the
        // oklch hue wheel, so at 50% over a light card it reads as a pale
        // indigo/periwinkle rather than "light blue". Swapping to blue-400 for
        // the disabled state keeps the same fade mechanism as every other
        // variant but lands on a clearly blue tint.
        'primary' => 'bg-blue-600 text-white hover:bg-blue-900 focus:ring-blue-500 disabled:bg-blue-400',
        'secondary' => 'bg-gray-200 dark:bg-gray-500 text-gray-900 dark:text-white hover:bg-gray-700 focus:ring-gray-500',
        'tertiary' => 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white focus:ring-gray-300',
        'ghost' => 'bg-transparent text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white focus:ring-gray-300',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        'light' => 'bg-gray-100 text-gray-800 hover:bg-gray-300 focus:ring-gray-500 border border-gray-300',
        'dark' => 'bg-gray-800 text-white hover:bg-gray-700 focus:ring-gray-500',
    ];
    
    // サイズに応じたクラス設定
    $sizeClasses = [
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];
    
    $buttonClasses = [
        'inline-flex items-center justify-center',
        'font-semibold rounded-md shadow-sm',
        'focus:outline-none focus:ring-2 focus:ring-offset-2',
        'transition-colors duration-200',
        // pointer-events-none stops a disabled <button> from reacting to hover
        // (the variant hover:* colours otherwise still fire on a disabled
        // element). Anchors have no :disabled state, so links are unaffected.
        'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none',
        $variantClasses[$variant] ?? $variantClasses['primary'],
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $class
    ];
@endphp

@if ($type === 'link')
<a href="{{ $href }}"
    @if ($id) id="{{ $id }}" @endif
    @if ($xShow) x-show="{{ $xShow }}" @endif
    class="{{ implode(' ', $buttonClasses) }} {{ $disabled ? 'pointer-events-none' : '' }}"
    >
    @if ($icon && $iconPosition === 'left')
        <i class="{{ $icon }} {{ ($label || $slot->isNotEmpty()) ? 'mr-2' : '' }}"></i>
    @endif
    
    @if ($label)
        {{ $label }}
    @else
        {{ $slot }}
    @endif
    
    @if ($icon && $iconPosition === 'right')
        <i class="{{ $icon }} {{ ($label || $slot->isNotEmpty()) ? 'ml-2' : '' }}"></i>
    @endif
</a>
@else
<button type="{{ $type }}"
    @if ($id) id="{{ $id }}" @endif
    @if ($onclick) onclick="{{ $onclick }}" @endif
    @if ($xClick) @click="{{ $xClick }}" @endif
    @if ($xDisabled) :disabled="{{ $xDisabled }}" @endif
    @if ($xShow) x-show="{{ $xShow }}" @endif
    @if ($form) form="{{ $form }}" @endif
    class="{{ implode(' ', $buttonClasses) }}"
    @if ($disabled) disabled @endif
    {{ $attributes->except(['type', 'variant', 'size', 'class', 'label', 'icon', 'iconPosition', 'onclick', 'disabled', 'form', 'id', 'href', 'xClick', 'xDisabled', 'xShow']) }}
    >
    @if ($icon && $iconPosition === 'left')
        <i class="{{ $icon }} {{ ($label || $slot->isNotEmpty()) ? 'mr-2' : '' }}"></i>
    @endif
    
    @if ($label)
        {{ $label }}
    @else
        {{ $slot }}
    @endif
    
    @if ($icon && $iconPosition === 'right')
        <i class="{{ $icon }} {{ ($label || $slot->isNotEmpty()) ? 'ml-2' : '' }}"></i>
    @endif
</button>
@endif
