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
    'id' => null,                    // ツールチップのID（省略時は自動生成）
    'title' => '',                   // ツールチップのタイトル
    'position' => 'bottom',          // 表示位置: top, bottom, left, right
    'trigger' => 'click',            // トリガー: click, hover
    'width' => 'w-72',               // 幅のTailwindクラス
    'closable' => true,              // 閉じるボタンを表示
])

@php
    $tooltipId = $id ?? 'tooltip-' . uniqid();
    
    $positionClasses = [
        'top' => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        'bottom' => 'top-full left-0 mt-2',
        'left' => 'right-full top-1/2 -translate-y-1/2 mr-2',
        'right' => 'left-full top-1/2 -translate-y-1/2 ml-2',
    ];
    $positionClass = $positionClasses[$position] ?? $positionClasses['bottom'];
@endphp

<div class="tooltip-container relative inline-block" data-tooltip-id="{{ $tooltipId }}" data-tooltip-trigger="{{ $trigger }}">
    {{-- トリガー要素（slotで指定） --}}
    <div class="tooltip-trigger cursor-pointer" 
         @if($trigger === 'click') onclick="toggleTooltip('{{ $tooltipId }}')" @endif
         @if($trigger === 'hover') onmouseenter="showTooltip('{{ $tooltipId }}')" onmouseleave="hideTooltip('{{ $tooltipId }}')" @endif
    >
        {{ $trigger_slot ?? $slot }}
    </div>
    
    {{-- ツールチップ本体 --}}
    <div id="{{ $tooltipId }}" 
         class="tooltip-content hidden absolute {{ $positionClass }} {{ $width }} bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl p-3"
         style="z-index: 9999;"
         @if($trigger === 'hover') onmouseenter="showTooltip('{{ $tooltipId }}')" onmouseleave="hideTooltip('{{ $tooltipId }}')" @endif
    >
        @if($title || $closable)
            <div class="flex items-center justify-between mb-2">
                @if($title)
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</span>
                @else
                    <span></span>
                @endif
                @if($closable && $trigger === 'click')
                    <button type="button" onclick="hideTooltip('{{ $tooltipId }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 ml-2">
                        <i class="fas fa-times"></i>
                    </button>
                @endif
            </div>
        @endif
        
        {{-- コンテンツ --}}
        <div class="tooltip-body text-sm text-gray-600 dark:text-gray-400">
            {{ $content ?? '' }}
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
// ツールチップマネージャー
if (typeof window.TooltipManager === 'undefined') {
    window.TooltipManager = {
        show: function(tooltipId) {
            const tooltip = document.getElementById(tooltipId);
            if (tooltip) {
                // 他のクリックトリガーのツールチップを閉じる
                document.querySelectorAll('.tooltip-content').forEach(t => {
                    const container = t.closest('.tooltip-container');
                    if (container && container.dataset.tooltipTrigger === 'click' && t.id !== tooltipId) {
                        t.classList.add('hidden');
                    }
                });
                tooltip.classList.remove('hidden');
            }
        },
        
        hide: function(tooltipId) {
            const tooltip = document.getElementById(tooltipId);
            if (tooltip) {
                tooltip.classList.add('hidden');
            }
        },
        
        toggle: function(tooltipId) {
            const tooltip = document.getElementById(tooltipId);
            if (tooltip) {
                if (tooltip.classList.contains('hidden')) {
                    this.show(tooltipId);
                } else {
                    this.hide(tooltipId);
                }
            }
        }
    };
    
    // グローバル関数
    window.showTooltip = function(tooltipId) {
        window.TooltipManager.show(tooltipId);
    };
    
    window.hideTooltip = function(tooltipId) {
        window.TooltipManager.hide(tooltipId);
    };
    
    window.toggleTooltip = function(tooltipId) {
        window.TooltipManager.toggle(tooltipId);
    };
    
    // ツールチップ外クリックで閉じる
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.tooltip-container')) {
            document.querySelectorAll('.tooltip-content').forEach(t => {
                const container = t.closest('.tooltip-container');
                if (container && container.dataset.tooltipTrigger === 'click') {
                    t.classList.add('hidden');
                }
            });
        }
    });
}
</script>
@endpush
@endonce
