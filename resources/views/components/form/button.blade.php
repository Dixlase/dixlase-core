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
    'type' => 'button',      // ボタンのタイプ (button, submit, reset, link)
    'variant' => 'primary',  // ボタンの色バリエーション (primary, secondary, success, warning, danger)
    'size' => 'md',          // ボタンのサイズ (xs, sm, md, lg)
    'class' => '',           // カスタムクラス
    'label' => 'Button',     // ボタンのテキスト
    'icon' => null,          // アイコンクラス (例: 'fas fa-save')
    'iconPosition' => 'left', // アイコンの位置 (left, right)
    'onclick' => null,       // onclick属性を追加
    'disabled' => false,     // ボタンを無効にする
    'form' => null,          // フォームのID
    'id' => null,            // ボタンのID
    'href' => null,          // リンク先URL (type='link'の時に使用)
])

@php
    // バリエーションに応じたクラス設定
    $variantClasses = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-900 focus:ring-blue-500',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
        'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
        'warning' => 'bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
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
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $variantClasses[$variant] ?? $variantClasses['primary'],
        $sizeClasses[$size] ?? $sizeClasses['md'],
        $class
    ];
@endphp

@if ($type === 'link')
<a href="{{ $href }}"
    @if ($id) id="{{ $id }}" @endif
    @if ($onclick) onclick="{{ $onclick }}" @endif
    class="{{ implode(' ', $buttonClasses) }} {{ $disabled ? 'pointer-events-none' : '' }}"
    >
    @if ($icon && $iconPosition === 'left')
        <i class="{{ $icon }} {{ $label ? 'mr-2' : '' }}"></i>
    @endif
    
    @if ($label)
        {{ $label }}
    @endif
    
    @if ($icon && $iconPosition === 'right')
        <i class="{{ $icon }} {{ $label ? 'ml-2' : '' }}"></i>
    @endif
</a>
@else
<button type="{{ $type }}"
    @if ($id) id="{{ $id }}" @endif
    @if ($onclick) onclick="{{ $onclick }}" @endif
    @if ($form) form="{{ $form }}" @endif
    class="{{ implode(' ', $buttonClasses) }}"
    @if ($disabled) disabled @endif
    >
    @if ($icon && $iconPosition === 'left')
        <i class="{{ $icon }} {{ $label ? 'mr-2' : '' }}"></i>
    @endif
    
    @if ($label)
        {{ $label }}
    @endif
    
    @if ($icon && $iconPosition === 'right')
        <i class="{{ $icon }} {{ $label ? 'ml-2' : '' }}"></i>
    @endif
</button>
@endif
