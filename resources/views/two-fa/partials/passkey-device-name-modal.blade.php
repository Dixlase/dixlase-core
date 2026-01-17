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

{{-- パーシャル用変数のデフォルト値設定 --}}
@php
    $modalId = $modalId ?? 'passkeyDeviceNameModal';
    $title = $title ?? null;
    $message = $message ?? null;
    $inputLabel = $inputLabel ?? null;
    $inputPlaceholder = $inputPlaceholder ?? '';
    $confirmLabel = $confirmLabel ?? null;
    $cancelLabel = $cancelLabel ?? null;
@endphp

<div id="{{ $modalId }}" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeModal('{{ $modalId }}')"></div>
    <div class="modal-container" onclick="event.stopPropagation()">
        <div class="modal-content">
            <div class="modal-icon modal-icon--info">
                <i class="fas fa-fingerprint" aria-hidden="true"></i>
            </div>
            
            <div class="modal-body">
                <h2 class="modal-title">{{ $title ?? __('admin/profile.passkey_device_name_title') }}</h2>
                <div class="modal-message">
                    <p>{{ $message ?? __('admin/profile.passkey_device_name_message') }}</p>
                </div>

                <div class="mt-4">
                    <label for="{{ $modalId }}_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $inputLabel ?? __('admin/profile.passkey_device_name_label') }}
                    </label>
                    <input 
                        type="text" 
                        id="{{ $modalId }}_input"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="{{ $inputPlaceholder }}"
                        maxlength="255"
                    />
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <x-form.button
                type="button"
                variant="secondary"
                :label="$cancelLabel ?? __('common.cancel')"
                onclick="window.PasskeyDeviceNameModal.cancel('{{ $modalId }}')"
                class="mx-2"
            />
            <x-form.button
                type="button"
                variant="primary"
                :label="$confirmLabel ?? __('common.ok')"
                onclick="window.PasskeyDeviceNameModal.confirm('{{ $modalId }}')"
                class="mx-2"
            />
        </div>
    </div>
</div>

@push('scripts')
<script @cspNonce>
// Passkeyデバイス名入力モーダルマネージャー
if (typeof window.PasskeyDeviceNameModal === 'undefined') {
    window.PasskeyDeviceNameModal = {
        // コールバック関数を保存
        callbacks: {},
        
        // モーダルを開く
        open: function(modalId, callback, defaultValue = '') {
            this.callbacks[modalId] = callback;
            const input = document.getElementById(modalId + '_input');
            if (input) {
                input.value = defaultValue;
            }
            openModal(modalId);
            
            // フォーカスを設定（少し遅延）
            setTimeout(() => {
                if (input) {
                    input.focus();
                    // デフォルト値がある場合は全選択
                    if (defaultValue) {
                        input.select();
                    }
                }
            }, 100);
        },
        
        // 確認ボタン
        confirm: function(modalId) {
            const input = document.getElementById(modalId + '_input');
            const value = input ? input.value.trim() : '';
            
            closeModal(modalId);
            
            if (this.callbacks[modalId]) {
                this.callbacks[modalId](value);
                delete this.callbacks[modalId];
            }
        },
        
        // キャンセルボタン
        cancel: function(modalId) {
            closeModal(modalId);
            
            if (this.callbacks[modalId]) {
                this.callbacks[modalId](null);
                delete this.callbacks[modalId];
            }
        }
    };
    
    // Enterキーで確認
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                const visibleModal = document.querySelector('.modal--visible');
                if (visibleModal && visibleModal.id.includes('passkeyDeviceNameModal')) {
                    event.preventDefault();
                    window.PasskeyDeviceNameModal.confirm(visibleModal.id);
                }
            }
        });
    });
}
</script>
@endpush
