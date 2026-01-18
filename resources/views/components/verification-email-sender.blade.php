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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'entityId' => null,
    'entityType' => 'member', // 'member' or 'user'
    'sendRoute' => null,
    'buttonId' => 'send-verification-email-btn',
    'showButton' => false,
    'isMailServerTested' => true,
    'isEdit' => false,
])

@php
    $modalTitle = $entityType === 'member' 
        ? __('admin/members/form.send_verification_email_title')
        : __('users-plugin::admin/users/form.send_verification_email_title');
    
    $modalMessage = $entityType === 'member'
        ? __('admin/members/form.send_verification_email_confirm')
        : __('users-plugin::admin/users/form.send_verification_email_message');
    
    $buttonLabel = $entityType === 'member'
        ? __('admin/members/form.send_verification_email_button')
        : __('users-plugin::admin/users/form.send_verification_email_button');
@endphp

@if($showButton && $isEdit)
    <!-- 認証メール送信ボタン -->
    <div class="my-4">
        @if($isMailServerTested)
            <x-form.button
                type="button"
                variant="secondary"
                size="sm"
                :label="$buttonLabel"
                icon="fas fa-envelope"
                :id="$buttonId"
                onclick="sendVerificationEmail({{ $entityId }})"
            />
        @else
            <x-form.button
                type="button"
                variant="secondary"
                size="sm"
                :label="$buttonLabel"
                icon="fas fa-envelope"
                :id="$buttonId"
                :disabled="true"
            />
        @endif
    </div>
    @if(!$isMailServerTested)
        <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-2">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            {{ $entityType === 'member' ? __('admin/members/form.mail_server_not_tested') : __('users-plugin::admin/users/form.mail_server_not_tested') }}
        </p>
    @endif
@endif

<!-- 認証メール送信確認モーダル -->
<x-ui.modal
    id="verificationEmailModal"
    :title="$modalTitle"
    :message="$modalMessage"
    :confirm-label="__('common.send')"
    :cancel-label="__('common.cancel')"
    icon-type="info"
    confirm-color="blue"
/>

@push('scripts')
<script @cspNonce>
(function() {
    let currentEntityId = {{ $entityId ?? 'null' }};

    window.sendVerificationEmail = function(entityId) {
        currentEntityId = entityId || {{ $entityId ?? 'null' }};
        window.ModalManager.open('verificationEmailModal');
    };

    document.addEventListener('DOMContentLoaded', function() {
        const verificationModal = document.getElementById('verificationEmailModal');
        
        if (verificationModal) {
            const buttons = verificationModal.querySelectorAll('button');
            const confirmButton = buttons[1]; // 2番目のボタンが確認ボタン
            
            if (confirmButton) {
                confirmButton.removeAttribute('onclick');
                confirmButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    confirmSendVerificationEmail();
                });
            }
        }
    });

    function confirmSendVerificationEmail() {
        if (!currentEntityId) {
            return;
        }

        const button = document.getElementById('{{ $buttonId }}');
        const buttonText = button ? (button.querySelector('span') || button) : null;
        const originalText = buttonText ? buttonText.textContent : '';
        
        window.ModalManager.close('verificationEmailModal');
        
        if (button) {
            button.disabled = true;
            if (buttonText) {
                buttonText.textContent = '{{ __("common.sending") }}...';
            }
        }
        
        const route = '{{ $sendRoute }}'.replace(':id', currentEntityId);
        
        fetch(route, {
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
                window.location.href = data.redirect;
            } else {
                alert(data.message || '{{ __("common.error_occurred") }}');
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
        
        currentEntityId = null;
    }
})();
</script>
@endpush
