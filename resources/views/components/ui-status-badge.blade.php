{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
Website: https://exc-d.com

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
    'status' => '',
    'label' => '',
    'variant' => '',
    'size' => 'sm',
])

@php
    // バリアント（色）の設定
    $variantClasses = match($variant) {
        'draft', 'gray' => 'status-badge--gray',
        'published', 'green' => 'status-badge--green',
        'scheduled', 'yellow' => 'status-badge--yellow',
        'danger', 'red' => 'status-badge--red',
        'info', 'blue' => 'status-badge--blue',
        'warning', 'orange' => 'status-badge--orange',
        default => 'status-badge--gray',
    };

    // サイズの設定
    $sizeClasses = match($size) {
        'xs' => 'status-badge--xs',
        'sm' => 'status-badge--sm',
        'md' => 'status-badge--md',
        'lg' => 'status-badge--lg',
        default => 'status-badge--sm',
    };

    // ステータス値から自動的にバリアントを決定（variantが指定されていない場合）
    if (!$variant && $status) {
        $variantClasses = match($status) {
            'draft' => 'status-badge--gray',
            'published' => 'status-badge--green',
            'scheduled' => 'status-badge--yellow',
            'active' => 'status-badge--green',
            'inactive' => 'status-badge--gray',
            'pending' => 'status-badge--yellow',
            'approved' => 'status-badge--green',
            'rejected' => 'status-badge--red',
            'cancelled' => 'status-badge--red',
            default => 'status-badge--gray',
        };
    }
@endphp

<span {{ $attributes->merge([
    'class' => "status-badge {$variantClasses} {$sizeClasses}"
]) }}>
    {{ $label ?: $status }}
</span>
