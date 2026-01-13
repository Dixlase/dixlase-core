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
    'confirm_label' => '確認',     // 確認ボタンのテキスト
    'cancel_label' => 'キャンセル', // キャンセルボタンのテキスト
    'close_label' => null,        // 閉じるボタンのテキスト（設定すると閉じるボタンのみモード）
    'class' => '',               // モーダルのカスタムクラス
    // ↓ チェックボックス用追加パラメータ
    'checkbox' => false,          // チェックボックスを表示するかどうか
    'checkbox_name' => 'remove_db_data', // name属性
    'checkbox_label' => 'データベースを削除する', // チェックボックスのラベル
    'form' => null,              // フォームのID
    // ↓ 新しいカスタマイズパラメータ
    'icon_type' => 'info',     // アイコンタイプ: warning, danger, info, success
    'confirm_color' => 'blue',     // 確認ボタンの色: blue, red, green, yellow
    'close_only' => false,        // 閉じるボタンのみ表示モード
    'dismissible' => true,        // 背景クリックで閉じるかどうか（デフォルト: true）
])

@php
    $iconClasses = [
        'warning' => 'fas fa-exclamation-triangle',
        'danger' => 'fas fa-times-circle',
        'info' => 'fas fa-info-circle',
        'success' => 'fas fa-check-circle'
    ];
    $iconClass = $iconClasses[$icon_type] ?? $iconClasses['warning'];
    
    // icon_typeに応じて確認ボタンのvariantを自動設定（confirm_colorが指定されていない場合）
    if (!isset($confirm_variant)) {
        $iconTypeToVariant = [
            'info' => 'primary',      // 青
            'warning' => 'warning',   // 黄色
            'danger' => 'danger',     // 赤
            'success' => 'success'    // 緑
        ];
        $confirm_variant = $iconTypeToVariant[$icon_type] ?? 'primary';
    }
    
    // 後方互換性: confirm_colorが指定されている場合はそれを使用
    if (isset($confirm_color)) {
        $colorToVariant = [
            'blue' => 'primary',
            'red' => 'danger',
            'green' => 'success',
            'yellow' => 'warning'
        ];
        $confirm_variant = $colorToVariant[$confirm_color] ?? $confirm_variant;
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
                <div class="modal-icon modal-icon--{{ $icon_type }}">
                    <i class="{{ $iconClass }}" aria-hidden="true"></i>
                </div>
                
                <div class="modal-body">
                    <h2 class="modal-title">{{ $title }}</h2>
                    <div class="modal-message">
                        <p>{!! $message !!}</p>
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
            @else
                {{-- カスタムモード：slotコンテンツを使用 --}}
                {{ $slot }}
            @endif
        </div>
        
        <div class="modal-actions">
            @if(!$hasCustomFooter)
                @if($close_only || $close_label)
                    {{-- 閉じるボタンのみモード --}}
                    <x-form.button
                        type="button"
                        variant="secondary"
                        :label="$close_label ?? __('common.close')"
                        onclick="closeModal('{{ $id }}')"
                        class="mx-2"
                    />
                @else
                    {{-- 標準フッター（確認・キャンセル） --}}
                    <x-form.button
                        type="button"
                        variant="secondary"
                        :label="$cancel_label ?? __('common.cancel')"
                        onclick="closeModal('{{ $id }}')"
                        class="mx-2"
                    />
                    <x-form.button
                        :type="$form ? 'submit' : 'button'"
                        :variant="$confirm_variant ?? 'primary'"
                        :label="$confirm_label ?? __('common.confirm')"
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
