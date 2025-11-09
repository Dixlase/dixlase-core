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
    'modalId' => 'passkeyResultModal',
])

<div id="{{ $modalId }}" class="modal">
    <div class="modal-overlay"></div>
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-content">
            <div id="{{ $modalId }}_icon" class="modal-icon modal-icon--success">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
            </div>
            
            <div class="modal-body">
                <h2 id="{{ $modalId }}_title" class="modal-title"></h2>
                <div class="modal-message">
                    <p id="{{ $modalId }}_message"></p>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <x-form.button
                type="button"
                variant="primary"
                :label="__('common.close')"
                onclick="closeModal('{{ $modalId }}')"
                class="mx-2"
            />
        </div>
    </div>
</div>

@push('scripts')
<script>
// Passkey結果表示モーダルマネージャー
if (typeof window.PasskeyResultModal === 'undefined') {
    window.PasskeyResultModal = {
        // コールバック関数を保存
        callbacks: {},
        
        // 成功モーダルを表示
        showSuccess: function(modalId, title, message, callback) {
            this.show(modalId, 'success', title, message, callback);
        },
        
        // エラーモーダルを表示
        showError: function(modalId, title, message, callback) {
            this.show(modalId, 'danger', title, message, callback);
        },
        
        // モーダルを表示
        show: function(modalId, type, title, message, callback) {
            this.callbacks[modalId] = callback;
            
            const iconElement = document.getElementById(modalId + '_icon');
            const titleElement = document.getElementById(modalId + '_title');
            const messageElement = document.getElementById(modalId + '_message');
            
            if (iconElement) {
                // アイコンタイプを設定
                iconElement.className = 'modal-icon modal-icon--' + type;
                const iconClass = type === 'success' ? 'fa-check-circle' : 
                                 type === 'danger' ? 'fa-times-circle' : 
                                 type === 'warning' ? 'fa-exclamation-triangle' : 
                                 'fa-info-circle';
                iconElement.querySelector('i').className = 'fas ' + iconClass;
            }
            
            if (titleElement) {
                titleElement.textContent = title;
            }
            
            if (messageElement) {
                messageElement.textContent = message;
            }
            
            openModal(modalId);
        },
        
        // モーダルを閉じる
        close: function(modalId) {
            closeModal(modalId);
            
            if (this.callbacks[modalId]) {
                this.callbacks[modalId]();
                delete this.callbacks[modalId];
            }
        }
    };
}
</script>
@endpush
