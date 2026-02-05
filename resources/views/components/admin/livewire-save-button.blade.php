{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the  implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'type' => 'button',                              // Button type (button, submit, reset)
    'disabled' => false,                             // Disable button
    'class' => '',                                   // Custom class
    'label' => __('common.save'),                    // Button text
    'title' => __('common.save_confirmation_title'), // Modal title
    'message' => __('common.save_confirmation_message'), // Modal message
    'confirmLabel' => __('common.save'),             // Confirm button text
    'confirm_label' => null,                         // 後方互換性
    'cancelLabel' => __('common.cancel'),            // Cancel button text
    'cancel_label' => null,                          // 後方互換性
    'wireClick' => 'save',                           // Livewireメソッド名
    'showConfirmation' => 'showSaveConfirmation',    // モーダル表示状態のプロパティ名
    'backUrl' => null,                               // Back button URL (optional)
    'back_url' => null,                              // 後方互換性
    'backLabel' => __('common.back'),                // Back button text
    'back_label' => null,                            // 後方互換性
    'iconType' => 'info',                            // Icon type for modal
    'icon_type' => null,                             // 後方互換性
    'confirmColor' => 'blue',                        // Confirm button color
    'confirm_color' => null,                         // 後方互換性
])

@php
// 後方互換性: ケバブケースとスネークケースの統一
$confirmLabel = $confirm_label ?? $confirmLabel;
$cancelLabel = $cancel_label ?? $cancelLabel;
$backUrl = $back_url ?? $backUrl;
$backLabel = $back_label ?? $backLabel;
$iconType = $icon_type ?? $iconType;
$confirmColor = $confirm_color ?? $confirmColor;
@endphp

<div class="{{ $class }}">
    <!-- 保存ボタン -->
    <button
        type="button"
        wire:click="$set('{{ $showConfirmation }}', true)"
        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors inline-flex items-center gap-2"
    >
        <i class="fas fa-save"></i>
        {{ $label }}
    </button>

    <!-- 確認モーダル -->
    <x-ui-livewire-modal 
        wire:model="{{ $showConfirmation }}" 
        :title="$title"
        :message="$message"
        :iconType="$iconType"
        :confirmColor="$confirmColor"
    >
        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    wire:click="$set('{{ $showConfirmation }}', false)"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md transition-colors"
                >
                    {{ $cancelLabel }}
                </button>
                <button
                    type="button"
                    wire:click="{{ $wireClick }}"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors"
                >
                    {{ $confirmLabel }}
                </button>
            </div>
        </x-slot>
    </x-ui-livewire-modal>
</div>
