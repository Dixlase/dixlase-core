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
    @include('admin.members.partials.members-form', [
        'member' => $member,
        'requirePassword' => false,
        'passwordMinLength' => $passwordMinLength,
        'passwordRequireUppercase' => $passwordRequireUppercase,
        'passwordRequireSymbol' => $passwordRequireSymbol,
        'roles' => $roles,
        'twoFaMode' => $twoFaMode,
        'twoFaEnabledMethods' => $twoFaEnabledMethods,
        'twoFaDefaultMethod' => $twoFaDefaultMethod,
        'isInitialAdmin' => $isInitialAdmin,
        'isMailServerTested' => $isMailServerTested,
        'formAction' => route('admin.members.update', ['member' => $member->id]),
        'formMethod' => 'POST',
        'formId' => 'update-form',
        'includeForm' => true
    ])
@endsection

@section('save')
    <!-- 保存ボタンとモーダル -->
    <x-save
        id_confirmation="confirmationModal"
        :label="__('common.update')"
        :title="__('admin/members/edit.confirm_title')"
        :message="__('admin/members/edit.confirm_message')"
        :confirm_label="__('common.update')"
        :cancel_label="__('common.cancel')"
        form="update-form"
    />
@endsection

@push('scripts')
<script @cspNonce>
let currentCredentialId = null;
let currentPasskeyName = '';

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
window.revokePasskey = function(event) {
    console.log('[Member Passkey Delete] Function called', {
        currentCredentialId: currentCredentialId,
        event: event
    });
    
    // イベントのデフォルト動作を防止
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    if (!currentCredentialId) {
        console.error('[Member Passkey Delete] No credential ID');
        return;
    }

    const memberId = {{ $member->id }};
    const url = `/admin/members/passkey/${memberId}/${currentCredentialId}`;
    
    console.log('[Member Passkey Delete] Sending DELETE request', {
        url: url,
        memberId: memberId,
        credentialId: currentCredentialId
    });

    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('[Member Passkey Delete] Response received', {
            status: response.status,
            ok: response.ok
        });
        return response.json();
    })
    .then(data => {
        console.log('[Member Passkey Delete] Response data', data);
        closeModal('deletePasskeyModal');
        if (data.success) {
            window.PasskeyResultModal.showSuccess(
                'passkeyResultModal',
                '{{ __('admin/profile.passkey_delete_success_title') }}',
                data.message,
                () => location.reload()
            );
        } else {
            window.PasskeyResultModal.showError(
                'passkeyResultModal',
                '{{ __('common.error') }}',
                data.message
            );
        }
    })
    .catch(error => {
        console.error('[Member Passkey Delete] Error:', error);
        closeModal('deletePasskeyModal');
        window.PasskeyResultModal.showError(
            'passkeyResultModal',
            '{{ __('common.error') }}',
            '{{ __('admin/profile.passkey_delete_error') }}'
        );
    });
};

// Passkey一括削除実行
window.revokeAllPasskeys = function(event) {
    console.log('[Member Passkey Delete All] Function called', { event: event });
    
    // イベントのデフォルト動作を防止
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const memberId = {{ $member->id }};
    const url = `/admin/members/passkey/${memberId}/all`;
    
    console.log('[Member Passkey Delete All] Sending DELETE request', {
        url: url,
        memberId: memberId
    });
    
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('[Member Passkey Delete All] Response received', {
            status: response.status,
            ok: response.ok
        });
        return response.json();
    })
    .then(data => {
        console.log('[Member Passkey Delete All] Response data', data);
        closeModal('deleteAllPasskeysModal');
        if (data.success) {
            window.PasskeyResultModal.showSuccess(
                'passkeyResultModal',
                '{{ __('admin/profile.passkey_delete_success_title') }}',
                data.message,
                () => location.reload()
            );
        } else {
            window.PasskeyResultModal.showError(
                'passkeyResultModal',
                '{{ __('common.error') }}',
                data.message
            );
        }
    })
    .catch(error => {
        console.error('[Member Passkey Delete All] Error:', error);
        closeModal('deleteAllPasskeysModal');
        window.PasskeyResultModal.showError(
            'passkeyResultModal',
            '{{ __('common.error') }}',
            '{{ __('admin/profile.passkey_delete_all_error') }}'
        );
    });
};

// モーダルの確認ボタンにイベントリスナーを追加
document.addEventListener('DOMContentLoaded', function() {
    // フラッシュメッセージの表示（削除機能はモーダルに移行したため不要）
    // const flashSuccess = sessionStorage.getItem('flash_success');
    // const flashError = sessionStorage.getItem('flash_error');
    // 
    // if (flashSuccess) {
    //     alert(flashSuccess);
    //     sessionStorage.removeItem('flash_success');
    // }
    // 
    // if (flashError) {
    //     alert(flashError);
    //     sessionStorage.removeItem('flash_error');
    // }

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
    const deletePasskeyBtn = document.querySelector('#deletePasskeyModal .modal-actions button[type="button"]:last-child');
    if (deletePasskeyBtn) {
        deletePasskeyBtn.addEventListener('click', revokePasskey);
    }

    // Passkey一括削除モーダル
    const deleteAllPasskeysBtn = document.querySelector('#deleteAllPasskeysModal .modal-actions button[type="button"]:last-child');
    if (deleteAllPasskeysBtn) {
        deleteAllPasskeysBtn.addEventListener('click', revokeAllPasskeys);
    }

    // 回復コード削除モーダル
    const deleteRecoveryCodesBtn = document.querySelector('#deleteRecoveryCodesModal .modal-actions button[type="button"]:last-child');
    if (deleteRecoveryCodesBtn) {
        deleteRecoveryCodesBtn.addEventListener('click', revokeRecoveryCodes);
    }
});

// 回復コード削除実行
window.revokeRecoveryCodes = function(event) {
    console.log('[Member Recovery Code Delete] Function called', { event: event });
    
    // イベントのデフォルト動作を防止
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const memberId = {{ $member->id }};
    const url = `/admin/members/recovery-codes/${memberId}`;
    
    console.log('[Member Recovery Code Delete] Sending DELETE request', {
        url: url,
        memberId: memberId
    });
    
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('[Member Recovery Code Delete] Response received', {
            status: response.status,
            ok: response.ok
        });
        return response.json();
    })
    .then(data => {
        console.log('[Member Recovery Code Delete] Response data', data);
        closeModal('deleteRecoveryCodesModal');
        if (data.success) {
            window.PasskeyResultModal.showSuccess(
                'passkeyResultModal',
                '{{ __('admin/members/form.recovery_codes_delete_success_title') }}',
                data.message,
                () => location.reload()
            );
        } else {
            window.PasskeyResultModal.showError(
                'passkeyResultModal',
                '{{ __('common.error') }}',
                data.message
            );
        }
    })
    .catch(error => {
        console.error('[Member Recovery Code Delete] Error:', error);
        closeModal('deleteRecoveryCodesModal');
        window.PasskeyResultModal.showError(
            'passkeyResultModal',
            '{{ __('common.error') }}',
            '{{ __('admin/members/form.recovery_codes_delete_error') }}'
        );
    });
};
</script>
@endpush
