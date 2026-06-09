{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-ui-livewire-modal />

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
    'show' => false,              // モーダル表示状態（Livewireプロパティ）
    'title' => '確認',            // モーダルタイトル
    'message' => 'この操作を実行しますか？', // モーダルメッセージ
    'confirmLabel' => '確認',      // 確認ボタンのテキスト
    'confirm_label' => null,       // 後方互換性
    'cancelLabel' => 'キャンセル',  // キャンセルボタンのテキスト
    'cancel_label' => null,        // 後方互換性
    'closeLabel' => null,          // 閉じるボタンのテキスト
    'close_label' => null,         // 後方互換性
    'class' => '',                 // モーダルのカスタムクラス
    'maxWidth' => 'md',            // 最大幅: sm, md, lg, xl, 2xl
    'checkbox' => false,           // チェックボックスを表示するかどうか
    'checkboxName' => 'remove_db_data', // name属性
    'checkbox_name' => null,       // 後方互換性
    'checkboxLabel' => 'データベースを削除する', // チェックボックスのラベル
    'checkbox_label' => null,      // 後方互換性
    'iconType' => 'info',          // アイコンタイプ: warning, danger, info, success
    'icon_type' => null,           // 後方互換性
    'confirmColor' => 'blue',      // 確認ボタンの色: blue, red, green, yellow
    'confirm_color' => null,       // 後方互換性
    'closeOnly' => false,          // 閉じるボタンのみ表示モード
    'close_only' => null,          // 後方互換性
    'dismissible' => true,         // 背景クリックで閉じるかどうか
])

@php
// 後方互換性: ケバブケースとスネークケースの統一
$confirmLabel = $confirm_label ?? $confirmLabel;
$cancelLabel = $cancel_label ?? $cancelLabel;
$closeLabel = $close_label ?? $closeLabel;
$iconType = $icon_type ?? $iconType;
$confirmColor = $confirm_color ?? $confirmColor;
$closeOnly = $close_only ?? $closeOnly;
$checkboxName = $checkbox_name ?? $checkboxName;
$checkboxLabel = $checkbox_label ?? $checkboxLabel;

$maxWidthClasses = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
];
$maxWidthClass = $maxWidthClasses[$maxWidth] ?? $maxWidthClasses['md'];

$iconClasses = [
    'warning' => 'fas fa-exclamation-triangle',
    'danger' => 'fas fa-times-circle',
    'info' => 'fas fa-info-circle',
    'success' => 'fas fa-check-circle'
];
$iconClass = $iconClasses[$iconType] ?? $iconClasses['info'];

$iconColorClasses = [
    'warning' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
    'danger' => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
    'info' => 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
    'success' => 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400'
];
$iconColorClass = $iconColorClasses[$iconType] ?? $iconColorClasses['info'];
@endphp

@if($show)
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="modal-{{ uniqid() }}">
        <!-- 背景オーバーレイ -->
        <div 
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            wire:click="$set('{{ $attributes->wire('model')->value() }}', false)"
        ></div>

        <!-- モーダルコンテンツ -->
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl {{ $maxWidthClass }} w-full">
                <!-- ヘッダー -->
                @if($title)
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ $title }}
                            </h3>
                            <button
                                type="button"
                                wire:click="$set('{{ $attributes->wire('model')->value() }}', false)"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- ボディ -->
                <div class="px-6 py-4">
                    {{ $slot }}
                </div>

                <!-- フッター（オプション） -->
                @isset($footer)
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>
    </div>
@endif
