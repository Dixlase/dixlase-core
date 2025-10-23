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

// モーダルの確認ボタンにイベントリスナーを追加
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('verificationEmailModal');
    if (modal) {
        // モーダル内の全てのボタンを検索
        const buttons = modal.querySelectorAll('button');
        
        // 2番目のボタン（確認ボタン）を取得
        // モーダルコンポーネントではキャンセルボタンが最初、確認ボタンが2番目
        const confirmButton = buttons[1];
        
        if (confirmButton) {
            // 既存のonclick属性を削除して新しいイベントリスナーを追加
            confirmButton.removeAttribute('onclick');
            confirmButton.addEventListener('click', function(e) {
                e.preventDefault();
                confirmSendVerificationEmail();
            });
        }
    }
});
</script>
@endpush
