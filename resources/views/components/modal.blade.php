{{--
This file is part of MySoftware.

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
    'confirm_label' => '確認',     // 確認ボタンのテキスト
    'cancel_label' => 'キャンセル', // キャンセルボタンのテキスト
    'class' => '',               // モーダルのカスタムクラス
    // ↓ チェックボックス用追加パラメータ
    'checkbox' => false,          // チェックボックスを表示するかどうか
    'checkbox_name' => 'remove_db_data', // name属性
    'checkbox_label' => 'データベースを削除する', // チェックボックスのラベル
    'form' => null,              // フォームのID
    // ↓ 新しいカスタマイズパラメータ
    'icon_type' => 'info',     // アイコンタイプ: warning, danger, info, success
    'confirm_color' => 'blue',     // 確認ボタンの色: blue, red, green, yellow
])

@php
    $iconClasses = [
        'warning' => 'fas fa-exclamation-triangle',
        'danger' => 'fas fa-times-circle',
        'info' => 'fas fa-info-circle',
        'success' => 'fas fa-check-circle'
    ];
    $iconClass = $iconClasses[$icon_type] ?? $iconClasses['warning'];
@endphp

<div id="{{ $id }}" class="modal" onclick="closeModal('{{ $id }}')">
    <div class="modal-overlay"></div>
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-content">
            <div class="modal-icon modal-icon--{{ $icon_type }}">
                <i class="{{ $iconClass }}" aria-hidden="true"></i>
            </div>
            
            <div class="modal-body">
                <h2 class="modal-title">{{ $title }}</h2>
                <div class="modal-message">
                    <p>{{ $message }}</p>
                </div>

                @if($checkbox)
                    <div class="modal-checkbox">
                        <label>
                            <input type="checkbox" name="{{ $checkbox_name }}" value="1" />
                            <span class="text-left">{!! $checkbox_label !!}</span>
                        </label>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="modal-actions">
            @include('components::form.button', [
                'type' => 'button',
                'label' => $cancel_label,
                'variant' => 'secondary',
                'onclick' => "closeModal('$id')",
                'class' => 'mx-2',
                'id' => null
            ])
            @include('components::form.button', [
                'type' => 'button',
                'label' => $confirm_label,
                'variant' => $confirm_color === 'blue' ? 'primary' : ($confirm_color === 'red' ? 'danger' : ($confirm_color === 'yellow' ? 'warning' : ($confirm_color === 'green' ? 'success' : 'primary'))),
                'onclick' => $form ? "submitModalForm('$form')" : null,
                'class' => 'mx-2',
                'id' => null
                
            ])
        </div>
    </div>
</div>

@push('scripts')
<script>
// デバッグモード（本番環境では false に設定）
const MODAL_DEBUG = false;

// モーダル関数をグローバルスコープで定義（名前空間を使用）
window.ModalManager = window.ModalManager || {
    // モーダルを開く関数
    open: function(modalId) {
        if (MODAL_DEBUG) console.log('ModalManager.open called with:', modalId);
        
        const modal = document.getElementById(modalId);
        if (MODAL_DEBUG) console.log('Modal element found:', modal);
        
        if (modal) {
            // アニメーションを有効化
            modal.classList.add('modal--animate');
            
            // 少し遅延してから表示（アニメーション準備のため）
            requestAnimationFrame(() => {
                modal.classList.add('modal--visible');
                if (MODAL_DEBUG) console.log('Added modal--visible class');
            });
        } else {
            if (MODAL_DEBUG) console.error('Modal not found with ID:', modalId);
        }
    },

    // モーダルを閉じる関数
    close: function(modalId) {
        if (MODAL_DEBUG) console.log('ModalManager.close called with:', modalId);
        
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('modal--visible');
            
            // アニメーション完了後にアニメーションクラスを削除
            setTimeout(() => {
                modal.classList.remove('modal--animate');
                if (MODAL_DEBUG) console.log('Modal closed and animation disabled');
            }, 300); // transition duration と同じ時間
        } else {
            if (MODAL_DEBUG) console.error('Modal not found with ID:', modalId);
        }
    },

    // フォーム送信関数
    submitForm: function(formId) {
        if (MODAL_DEBUG) console.log('ModalManager.submitForm called with:', formId);
        
        const form = document.getElementById(formId);
        if (form) {
            form.submit();
        } else {
            if (MODAL_DEBUG) console.error('Form not found with ID:', formId);
        }
    }
};

// 後方互換性のための関数エイリアス
function openModal(modalId) {
    window.ModalManager.open(modalId);
}

function closeModal(modalId) {
    window.ModalManager.close(modalId);
}

function submitModalForm(formId) {
    window.ModalManager.submitForm(formId);
}

// ESCキーでモーダルを閉じる（DOMContentLoaded後に設定）
document.addEventListener('DOMContentLoaded', function() {
    if (MODAL_DEBUG) console.log('Modal script loaded - DOMContentLoaded');
    if (MODAL_DEBUG) console.log('ModalManager available:', typeof window.ModalManager);
    
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const visibleModal = document.querySelector('.modal--visible');
            if (visibleModal && visibleModal.id) {
                if (MODAL_DEBUG) console.log('ESC pressed, closing modal:', visibleModal.id);
                window.ModalManager.close(visibleModal.id);
            }
        }
    });
});
</script>
@endpush
