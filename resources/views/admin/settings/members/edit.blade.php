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

@extends('layouts.admin')

@section('content')
    @include('admin.settings.members.partials.members-form', [
        'member' => $member,
        'requirePassword' => false,
        'passwordMinLength' => $passwordMinLength,
        'passwordRequireUppercase' => $passwordRequireUppercase,
        'passwordRequireSymbol' => $passwordRequireSymbol,
        'roles' => $roles,
        'twoFactorMode' => $twoFactorMode,
        'enabledTwoFactorMethods' => $enabledTwoFactorMethods,
        'defaultTwoFactorMethod' => $defaultTwoFactorMethod,
        'isInitialAdmin' => $isInitialAdmin,
        'isMailServerTested' => $isMailServerTested,
        'formAction' => route('admin.settings.members.update', ['member' => $member->id]),
        'formMethod' => 'PATCH',
        'formId' => 'update-form',
        'includeForm' => true
    ])

    <!-- 認証メール送信確認モーダル -->
    @include('components.modal', [
        'id' => 'verificationEmailModal',
        'title' => __('admin.settings.members.form.send_verification_email_title'),
        'message' => __('admin.settings.members.form.send_verification_email_confirm'),
        'confirm_label' => __('common.send'),
        'cancel_label' => __('common.cancel'),
        'icon_type' => 'info',
        'confirm_color' => 'blue',
    ])

    <!-- Passkey削除確認モーダル -->
    <x-modal 
        id="deletePasskeyModal"
        :title="__('admin.profile.confirm_delete_passkey_title')"
        message=""
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
    />

    <!-- Passkey一括削除確認モーダル -->
    <x-modal 
        id="deleteAllPasskeysModal"
        :title="__('admin.profile.confirm_delete_all_passkeys_title')"
        :message="__('admin.profile.confirm_delete_all_passkeys_message')"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
    />
@endsection

@section('save')
    <!-- 保存ボタンとモーダル -->
    @include('components.save', [
        'id_confirmation' => 'confirmationModal',
        'label' => __('common.update'),
        'title' => __('common.update_confirmation'),
        'message' => __('admin.settings.members.edit.confirm_message'),
        'confirm_label' => __('common.update'),
        'cancel_label' => __('common.cancel'),
        'form' => 'update-form',
    ])
@endsection

@push('scripts')
<script>
let currentMemberId = null;
let currentCredentialId = null;
let currentPasskeyName = '';

function sendVerificationEmail(memberId) {
    // メンバーIDを保存
    currentMemberId = memberId;
    // モーダルを開く
    window.ModalManager.open('verificationEmailModal');
}

function confirmSendVerificationEmail() {
    if (!currentMemberId) {
        return;
    }

    const button = document.getElementById('send-verification-email-btn');
    const buttonText = button ? (button.querySelector('span') || button) : null;
    const originalText = buttonText ? buttonText.textContent : '';
    
    // モーダルを閉じる
    window.ModalManager.close('verificationEmailModal');
    
    // ボタンを無効化
    if (button) {
        button.disabled = true;
        if (buttonText) {
            buttonText.textContent = '{{ __("common.sending") }}...';
        }
    }
    
    fetch(`/admin/settings/members/${currentMemberId}/send-verification-email`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // リダイレクト（フラッシュメッセージを表示）
            window.location.href = data.redirect;
        } else {
            // エラーメッセージを表示
            alert(data.message || '{{ __("admin.settings.members.messages.verification_email_failed") }}');
            if (button) {
                button.disabled = false;
                if (buttonText) {
                    buttonText.textContent = originalText;
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('{{ __("common.error_occurred") }}');
        if (button) {
            button.disabled = false;
            if (buttonText) {
                buttonText.textContent = originalText;
            }
        }
    });
    
    currentMemberId = null;
}

// Passkey削除モーダルを開く
function openDeletePasskeyModal(credentialId, name) {
    currentCredentialId = credentialId;
    currentPasskeyName = name;
    
    // モーダルのメッセージを更新
    const modal = document.getElementById('deletePasskeyModal');
    if (modal) {
        const messageElement = modal.querySelector('.modal-message');
        if (messageElement) {
            messageElement.textContent = `「${name}」を削除してもよろしいですか？`;
        }
    }
    
    openModal('deletePasskeyModal');
}

// Passkey削除実行
window.revokePasskey = function() {
    if (!currentCredentialId) return;

    const memberId = {{ $member->id }};
    const url = `/admin/settings/members/${memberId}/passkey/${currentCredentialId}`;

    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        closeModal('deletePasskeyModal');
        if (data.success) {
            sessionStorage.setItem('flash_success', data.message);
            location.reload();
        } else {
            sessionStorage.setItem('flash_error', data.message);
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        closeModal('deletePasskeyModal');
        sessionStorage.setItem('flash_error', '{{ __('admin.profile.passkey_delete_error') }}');
        location.reload();
    });
};

// Passkey一括削除実行
window.revokeAllPasskeys = function() {
    const memberId = {{ $member->id }};
    
    fetch(`/admin/settings/members/${memberId}/passkey/all`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        closeModal('deleteAllPasskeysModal');
        if (data.success) {
            sessionStorage.setItem('flash_success', data.message);
            location.reload();
        } else {
            sessionStorage.setItem('flash_error', data.message);
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        closeModal('deleteAllPasskeysModal');
        sessionStorage.setItem('flash_error', '{{ __('admin.profile.passkey_delete_all_error') }}');
        location.reload();
    });
};

// モーダルの確認ボタンにイベントリスナーを追加
document.addEventListener('DOMContentLoaded', function() {
    // 認証メール送信モーダル
    const verificationModal = document.getElementById('verificationEmailModal');
    if (verificationModal) {
        const buttons = verificationModal.querySelectorAll('button');
        const confirmButton = buttons[1];
        
        if (confirmButton) {
            confirmButton.removeAttribute('onclick');
            confirmButton.addEventListener('click', function(e) {
                e.preventDefault();
                confirmSendVerificationEmail();
            });
        }
    }

    // Passkey削除モーダル
    const deletePasskeyModal = document.getElementById('deletePasskeyModal');
    if (deletePasskeyModal) {
        const buttons = deletePasskeyModal.querySelectorAll('.modal-actions button');
        if (buttons.length >= 2) {
            buttons[1].addEventListener('click', revokePasskey);
        }
    }

    // Passkey一括削除モーダル
    const deleteAllPasskeysModal = document.getElementById('deleteAllPasskeysModal');
    if (deleteAllPasskeysModal) {
        const buttons = deleteAllPasskeysModal.querySelectorAll('.modal-actions button');
        if (buttons.length >= 2) {
            buttons[1].addEventListener('click', revokeAllPasskeys);
        }
    }
});
</script>
@endpush
