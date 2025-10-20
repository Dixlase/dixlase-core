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
function sendVerificationEmail(memberId) {
    const button = document.getElementById('send-verification-email-btn');
    const buttonText = button.querySelector('span') || button;
    const originalText = buttonText.textContent;
    
    // 確認ダイアログ
    if (!confirm('{{ __("admin.settings.members.form.send_verification_email_confirm") }}')) {
        return;
    }
    
    // ボタンを無効化
    button.disabled = true;
    buttonText.textContent = '{{ __("common.sending") }}...';
    
    fetch(`/admin/settings/members/${memberId}/send-verification-email`, {
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
            // 成功メッセージをセッションストレージに保存
            sessionStorage.setItem('verificationEmailSuccess', data.message);
            // ページをリロード
            window.location.href = data.redirect;
        } else {
            // エラーメッセージを表示
            showMessage('error', data.message || '{{ __("admin.settings.members.messages.verification_email_failed") }}');
            button.disabled = false;
            buttonText.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('error', '{{ __("common.error_occurred") }}');
        button.disabled = false;
        buttonText.textContent = originalText;
    });
}

// メッセージ表示関数
function showMessage(type, message) {
    // 既存のメッセージを削除
    const existingMessage = document.querySelector('.verification-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // メッセージ要素を作成
    const messageDiv = document.createElement('div');
    messageDiv.className = `verification-message alert alert-${type} mb-4`;
    messageDiv.style.cssText = 'padding: 1rem; margin-bottom: 1rem; border-radius: 0.375rem;';
    
    if (type === 'success') {
        messageDiv.style.backgroundColor = '#d1fae5';
        messageDiv.style.color = '#065f46';
        messageDiv.style.border = '1px solid #6ee7b7';
    } else {
        messageDiv.style.backgroundColor = '#fee2e2';
        messageDiv.style.color = '#991b1b';
        messageDiv.style.border = '1px solid #fca5a5';
    }
    
    messageDiv.textContent = message;
    
    // フォームの前に挿入
    const form = document.querySelector('form');
    if (form) {
        form.parentNode.insertBefore(messageDiv, form);
        // メッセージまでスクロール
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// ページロード時にセッションストレージのメッセージを確認
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = sessionStorage.getItem('verificationEmailSuccess');
    if (successMessage) {
        showMessage('success', successMessage);
        sessionStorage.removeItem('verificationEmailSuccess');
    }
});
</script>
@endpush
