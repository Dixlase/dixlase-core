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
    'entity' => null,
    'entityType' => 'member', // 'member' or 'user'
    'sendRoute' => null,
    'isMailServerTested' => true,
    'isEdit' => false,
    'errors' => null,
])

@php
    $prefix = $entityType === 'member' ? 'admin/members/form' : 'users-plugin::admin/users/form';
    
    // 新規作成時のオプション
    $emailVerifiedValueCreate = old('email_verified', $isMailServerTested ? '0' : '1');
    $emailVerificationOptionsCreate = [
        ['value' => '0', 'label' => $prefix . '.account_verified_send_email'],
        ['value' => '1', 'label' => $prefix . '.account_verified'],
    ];
    
    // 編集時のオプション
    $emailVerifiedValueEdit = $isEdit && $entity 
        ? old('email_verified', $entity->hasVerifiedEmail() ? '1' : '0')
        : '0';
    $emailVerificationOptionsEdit = [
        ['value' => '0', 'label' => $prefix . '.account_unverified'],
        ['value' => '1', 'label' => $prefix . '.account_verified'],
    ];
@endphp

<fieldset>
    <legend>{{ __($prefix . '.account_verification') }}</legend>
    
    @if(!$isEdit)
        {{-- 新規作成時 --}}
        <x-form.radio-card-group
            name="email_verified"
            :options="$emailVerificationOptionsCreate"
            :value="$emailVerifiedValueCreate"
            :columns="2"
            :disabled="!$isMailServerTested"
        />
        <p class="description-text">{{ __($prefix . '.account_verification_help_create') }}</p>
    @else
        {{-- 編集時 --}}
        <x-form.radio-card-group
            name="email_verified"
            :options="$emailVerificationOptionsEdit"
            :value="$emailVerifiedValueEdit"
            :columns="2"
            :disabled="!$isMailServerTested"
        />
        <p class="description-text">{{ __($prefix . '.account_verification_help_edit') }}</p>
        
        {{-- 認証メール送信ボタン（編集時のみ） --}}
        <div class="my-4">
            @if($isMailServerTested)
                <x-form.button
                    type="button"
                    variant="secondary"
                    size="sm"
                    :label="__($prefix . '.send_verification_email_button')"
                    icon="fas fa-envelope"
                    id="send-verification-email-btn"
                    onclick="sendVerificationEmail({{ $entity->id }})"
                />
            @else
                <x-form.button
                    type="button"
                    variant="secondary"
                    size="sm"
                    :label="__($prefix . '.send_verification_email_button')"
                    icon="fas fa-envelope"
                    id="send-verification-email-btn"
                    :disabled="true"
                />
            @endif
        </div>
        @if(!$isMailServerTested)
            <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                {{ __($prefix . '.mail_server_not_tested') }}
            </p>
        @endif
    @endif

    @if(!$isMailServerTested)
        <x-message
            type="info"
            :message="__($prefix . '.account_verification_disabled')"
        />
    @endif
    
    <x-form.error
        :messages="$errors?->get('email_verified') ?? []"
    />
</fieldset>

<!-- 認証メール送信確認モーダル -->
@if($isEdit)
    <x-ui.modal
        id="verificationEmailModal"
        :title="__($prefix . '.send_verification_email_title')"
        :message="__($prefix . ($entityType === 'member' ? '.send_verification_email_confirm' : '.send_verification_email_message'))"
        :confirm-label="__('common.send')"
        :cancel-label="__('common.cancel')"
        icon-type="info"
        confirm-color="blue"
    />

    @push('scripts')
    <script @cspNonce>
    (function() {
        let currentEntityId = {{ $entity->id ?? 'null' }};

        window.sendVerificationEmail = function(entityId) {
            currentEntityId = entityId || {{ $entity->id ?? 'null' }};
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

            const button = document.getElementById('send-verification-email-btn');
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
@endif
