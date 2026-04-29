{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-ui-modal-vanilla />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

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

@props([
    'id' => 'confirmationModal',
    'title' => '確認',
    'message' => 'この操作を実行しますか？',
    'confirmLabel' => '確認',
    'confirm_label' => null,
    'cancelLabel' => 'キャンセル',
    'cancel_label' => null,
    'closeLabel' => null,
    'close_label' => null,
    'class' => '',
    'checkbox' => false,
    'checkboxName' => 'remove_db_data',
    'checkbox_name' => null,
    'checkboxLabel' => 'データベースを削除する',
    'checkbox_label' => null,
    'form' => null,
    'iconType' => 'info',
    'icon_type' => null,
    'confirmColor' => 'blue',
    'confirm_color' => null,
    'closeOnly' => false,
    'close_only' => null,
    'dismissible' => true,
])

@php
// 後方互換性: ケバブケースとスネークケースの統一
$iconType = $icon_type ?? $iconType;
$confirmColor = $confirm_color ?? $confirmColor;
$confirmLabel = $confirm_label ?? $confirmLabel;
$cancelLabel = $cancel_label ?? $cancelLabel;
$closeLabel = $close_label ?? $closeLabel;
$closeOnly = $close_only ?? $closeOnly;
$checkboxName = $checkbox_name ?? $checkboxName;
$checkboxLabel = $checkbox_label ?? $checkboxLabel;

$iconClasses = [
    'warning' => 'fas fa-exclamation-triangle',
    'danger' => 'fas fa-times-circle',
    'info' => 'fas fa-info-circle',
    'success' => 'fas fa-check-circle'
];
$iconClass = $iconClasses[$iconType] ?? $iconClasses['warning'];

// アイコンの色を設定
$iconColorClasses = [
    'warning' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
    'danger' => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
    'info' => 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
    'success' => 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400'
];
$iconColorClass = $iconColorClasses[$iconType] ?? $iconColorClasses['info'];

// icon_typeに応じて確認ボタンのvariantを自動設定
$iconTypeToVariant = [
    'info' => 'primary',
    'warning' => 'warning',
    'danger' => 'danger',
    'success' => 'success'
];
$confirm_variant = $iconTypeToVariant[$iconType] ?? 'primary';

// confirm_colorが指定されている場合はそれを優先
$colorToVariant = [
    'blue' => 'primary',
    'red' => 'danger',
    'green' => 'success',
    'yellow' => 'warning'
];
if ($confirmColor && isset($colorToVariant[$confirmColor])) {
    $confirm_variant = $colorToVariant[$confirmColor];
}

// slotが使用されているかチェック
$hasCustomContent = !empty(trim($slot ?? ''));
$hasCustomFooter = isset($footer) && !empty(trim($footer ?? ''));
@endphp

<div id="{{ $id }}" 
     class="modal fixed inset-0 z-50 overflow-y-auto hidden"
     data-dismissible="{{ $dismissible ? 'true' : 'false' }}"
     {{ $attributes }}>
    <div class="modal-overlay fixed inset-0 bg-white/80 dark:bg-black/50 transition-opacity duration-300 opacity-0"></div>
    <div class="modal-container flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="modal-content inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full opacity-0 scale-95">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                @if(!$hasCustomContent)
                    {{-- 標準モード：既存の確認ダイアログ --}}
                    <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-full {{ $iconColorClass }}">
                        <i class="{{ $iconClass }} text-3xl" aria-hidden="true"></i>
                    </div>
                    
                    <div class="modal-body mt-3 text-center sm:mt-5">
                        <h2 class="modal-title text-lg leading-6 font-medium text-gray-900 dark:text-white">{{ $title }}</h2>
                        <div class="modal-message mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{!! $message !!}</p>
                        </div>

                        @if($checkbox)
                            <div class="modal-checkbox mt-4">
                                <label class="flex items-center justify-center">
                                    <input type="checkbox" name="{{ $checkboxName }}" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{!! $checkboxLabel !!}</span>
                                </label>
                            </div>
                        @endif
                    </div>
                @else
                    {{-- カスタムモード：slotコンテンツを使用 --}}
                    {{ $slot }}
                @endif
            </div>
            
            <div class="modal-actions bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                @if(!$hasCustomFooter)
                    @if($closeOnly || $closeLabel)
                        {{-- 閉じるボタンのみモード --}}
                        <x-form-button
                            type="button"
                            variant="secondary"
                            :label="$closeLabel ?? __('common.close')"
                            class="modal-close-btn w-full sm:w-auto"
                        />
                    @else
                        {{-- 標準フッター（確認・キャンセル） --}}
                        @if($form)
                            <x-form-button
                                type="submit"
                                variant="{{ $confirm_variant ?? 'primary' }}"
                                :label="$confirmLabel ?? __('common.confirm')"
                                form="{{ $form }}"
                                class="modal-confirm-btn w-full sm:w-auto"
                            />
                        @else
                            <x-form-button
                                type="button"
                                variant="{{ $confirm_variant ?? 'primary' }}"
                                :label="$confirmLabel ?? __('common.confirm')"
                                class="modal-confirm-btn w-full sm:w-auto"
                            />
                        @endif
                        <x-form-button
                            type="button"
                            variant="secondary"
                            :label="$cancelLabel ?? __('common.cancel')"
                            class="modal-cancel-btn w-full sm:w-auto"
                        />
                    @endif
                @else
                    {{-- カスタムフッター --}}
                    {{ $footer }}
                @endif
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
// Vanilla JS Modal Manager (CSP Strict Mode Compatible)
(function() {
    'use strict';
    
    class ModalManager {
        constructor() {
            this.modals = new Map();
            this.init();
        }
        
        init() {
            document.addEventListener('DOMContentLoaded', () => {
                this.registerModals();
                this.setupEventListeners();
            });
        }
        
        registerModals() {
            const modalElements = document.querySelectorAll('.modal');
            modalElements.forEach(modal => {
                const modalId = modal.id;
                if (modalId) {
                    this.modals.set(modalId, {
                        element: modal,
                        overlay: modal.querySelector('.modal-overlay'),
                        content: modal.querySelector('.modal-content'),
                        isOpen: false
                    });
                }
            });
        }
        
        setupEventListeners() {
            this.modals.forEach((modal, modalId) => {
                // オーバーレイクリック
                if (modal.overlay) {
                    modal.overlay.addEventListener('click', () => {
                        const dismissible = modal.element.dataset.dismissible === 'true';
                        if (dismissible) {
                            this.close(modalId);
                        }
                    });
                }
                
                // キャンセルボタン
                const cancelBtn = modal.element.querySelector('.modal-cancel-btn');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', () => this.close(modalId));
                }
                
                // 閉じるボタン
                const closeBtn = modal.element.querySelector('.modal-close-btn');
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => this.close(modalId));
                }
                
                // 確認ボタン（フォーム送信の場合）
                const confirmBtn = modal.element.querySelector('.modal-confirm-btn');
                if (confirmBtn && confirmBtn.hasAttribute('form')) {
                    confirmBtn.addEventListener('click', (e) => {
                        const formId = confirmBtn.getAttribute('form');
                        const form = document.getElementById(formId);
                        if (form) {
                            form.submit();
                        }
                        this.close(modalId);
                    });
                }
            });
            
            // ESCキーで閉じる
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeAll();
                }
            });
        }
        
        open(modalId) {
            const modal = this.modals.get(modalId);
            if (!modal || modal.isOpen) return;
            
            modal.element.classList.remove('hidden');
            modal.isOpen = true;
            
            // アニメーション
            requestAnimationFrame(() => {
                modal.overlay.style.opacity = '1';
                modal.content.style.opacity = '1';
                modal.content.style.transform = 'scale(1)';
            });
            
            // body スクロール防止
            document.body.style.overflow = 'hidden';
        }
        
        close(modalId) {
            const modal = this.modals.get(modalId);
            if (!modal || !modal.isOpen) return;
            
            // アニメーション
            modal.overlay.style.opacity = '0';
            modal.content.style.opacity = '0';
            modal.content.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                modal.element.classList.add('hidden');
                modal.isOpen = false;
                
                // すべてのモーダルが閉じている場合のみ body スクロール復元
                const anyOpen = Array.from(this.modals.values()).some(m => m.isOpen);
                if (!anyOpen) {
                    document.body.style.overflow = '';
                }
            }, 300);
        }
        
        closeAll() {
            this.modals.forEach((modal, modalId) => {
                if (modal.isOpen) {
                    this.close(modalId);
                }
            });
        }
    }
    
    // グローバルに公開
    window.modalManager = new ModalManager();
})();
</script>
@endpush
@endonce
