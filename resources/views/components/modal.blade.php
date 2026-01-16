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
    'id' => 'confirmationModal', // モーダルのID
    'title' => '確認',          // モーダルのタイトル
    'message' => 'この操作を実行しますか？', // モーダルのメッセージ
    'confirmLabel' => '確認',     // 確認ボタンのテキスト
    'confirm_label' => null,      // 後方互換性
    'cancelLabel' => 'キャンセル', // キャンセルボタンのテキスト
    'cancel_label' => null,       // 後方互換性
    'closeLabel' => null,         // 閉じるボタンのテキスト（設定すると閉じるボタンのみモード）
    'close_label' => null,        // 後方互換性
    'class' => '',               // モーダルのカスタムクラス
    // ↓ チェックボックス用追加パラメータ
    'checkbox' => false,          // チェックボックスを表示するかどうか
    'checkboxName' => 'remove_db_data', // name属性
    'checkbox_name' => null,      // 後方互換性
    'checkboxLabel' => 'データベースを削除する', // チェックボックスのラベル
    'checkbox_label' => null,     // 後方互換性
    'form' => null,              // フォームのID
    // ↓ 新しいカスタマイズパラメータ
    'iconType' => 'info',         // アイコンタイプ: warning, danger, info, success
    'icon_type' => null,          // 後方互換性
    'confirmColor' => 'blue',     // 確認ボタンの色: blue, red, green, yellow
    'confirm_color' => null,      // 後方互換性
    'closeOnly' => false,         // 閉じるボタンのみ表示モード
    'close_only' => null,         // 後方互換性
    'dismissible' => true,        // 背景クリックで閉じるかどうか（デフォルト: true）
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
        'info' => 'primary',      // 青
        'warning' => 'warning',   // 黄色
        'danger' => 'danger',     // 赤
        'success' => 'success'    // 緑
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

<div id="{{ $id }}" class="modal">
    <div class="modal-overlay" @if($dismissible) onclick="closeModal('{{ $id }}')" @endif></div>
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-content">
            @if(!$hasCustomContent)
                {{-- 標準モード：既存の確認ダイアログ --}}
                <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-full {{ $iconColorClass }}">
                    <i class="{{ $iconClass }} text-3xl" aria-hidden="true"></i>
                </div>
                
                <div class="modal-body">
                    <h2 class="modal-title">{{ $title }}</h2>
                    <div class="modal-message">
                        <p>{!! $message !!}</p>
                    </div>

                    @if($checkbox)
                        <div class="modal-checkbox">
                            <label>
                                <input type="checkbox" name="{{ $checkboxName }}" value="1" />
                                <span class="text-left">{!! $checkboxLabel !!}</span>
                            </label>
                        </div>
                    @endif
                </div>
            @else
                {{-- カスタムモード：slotコンテンツを使用 --}}
                {{ $slot }}
            @endif
        </div>
        
        <div class="modal-actions">
            @if(!$hasCustomFooter)
                @if($closeOnly || $closeLabel)
                    {{-- 閉じるボタンのみモード --}}
                    <x-form.button
                        type="button"
                        variant="secondary"
                        :label="$closeLabel ?? __('common.close')"
                        onclick="closeModal('{{ $id }}')"
                        class="mx-2"
                    />
                @else
                    {{-- 標準フッター（確認・キャンセル） --}}
                    <x-form.button
                        type="button"
                        variant="secondary"
                        :label="$cancelLabel ?? __('common.cancel')"
                        onclick="closeModal('{{ $id }}')"
                        class="mx-2"
                    />
                    <x-form.button
                        :type="$form ? 'submit' : 'button'"
                        :variant="$confirm_variant ?? 'primary'"
                        :label="$confirmLabel ?? __('common.confirm')"
                        :form="$form"
                        class="mx-2"
                    />
                @endif
            @else
                {{-- カスタムフッター --}}
                {{ $footer }}
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script @cspNonce>
// モーダルマネージャーが未初期化の場合のみ実行（重複実行を防ぐ）
if (typeof window.ModalManager === 'undefined') {
    // デバッグモード（本番環境では false に設定）
    window.MODAL_DEBUG = false;

    // モーダル関数をグローバルスコープで定義（名前空間を使用）
    window.ModalManager = {
        // モーダルを開く関数
        open: function(modalId) {
            if (window.MODAL_DEBUG) console.log('ModalManager.open called with:', modalId);
            
            const modal = document.getElementById(modalId);
            if (window.MODAL_DEBUG) console.log('Modal element found:', modal);
            
            if (modal) {
                // アニメーションを有効化
                modal.classList.add('modal--animate');
                
                // 少し遅延してから表示（アニメーション準備のため）
                requestAnimationFrame(() => {
                    modal.classList.add('modal--visible');
                    if (window.MODAL_DEBUG) console.log('Added modal--visible class');
                });
            } else {
                if (window.MODAL_DEBUG) console.error('Modal not found with ID:', modalId);
            }
        },

        // モーダルを閉じる関数
        close: function(modalId) {
            if (window.MODAL_DEBUG) console.log('ModalManager.close called with:', modalId);
            
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('modal--visible');
                
                // アニメーション完了後にアニメーションクラスを削除
                setTimeout(() => {
                    modal.classList.remove('modal--animate');
                    if (window.MODAL_DEBUG) console.log('Modal closed and animation disabled');
                }, 300); // transition duration と同じ時間
            } else {
                if (window.MODAL_DEBUG) console.error('Modal not found with ID:', modalId);
            }
        },

        // フォーム送信関数
        submitForm: function(formId) {
            if (window.MODAL_DEBUG) console.log('ModalManager.submitForm called with:', formId);
            
            const form = document.getElementById(formId);
            if (form) {
                form.submit();
            } else {
                if (window.MODAL_DEBUG) console.error('Form not found with ID:', formId);
            }
        }
    };

    // 後方互換性のための関数エイリアス
    window.openModal = function(modalId) {
        window.ModalManager.open(modalId);
    };

    window.closeModal = function(modalId) {
        window.ModalManager.close(modalId);
    };

    window.submitModalForm = function(formId) {
        window.ModalManager.submitForm(formId);
    };

    // ESCキーでモーダルを閉じる（DOMContentLoaded後に設定）
    document.addEventListener('DOMContentLoaded', function() {
        if (window.MODAL_DEBUG) console.log('Modal script loaded - DOMContentLoaded');
        if (window.MODAL_DEBUG) console.log('ModalManager available:', typeof window.ModalManager);
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const visibleModal = document.querySelector('.modal--visible');
                if (visibleModal && visibleModal.id) {
                    if (window.MODAL_DEBUG) console.log('ESC pressed, closing modal:', visibleModal.id);
                    window.ModalManager.close(visibleModal.id);
                }
            }
        });
    });
}
</script>
@endpush
