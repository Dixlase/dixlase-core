{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-auth.account-verification />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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
    'entity' => null,
    'entityType' => 'member', // 'member' or 'user'
    'sendRoute' => null,
    'isMailServerTested' => true,
    'isEdit' => false,
    'errors' => null,
    'translationPrefix' => null, // コントローラーから渡される翻訳プレフィックス
])

@php
    // 翻訳プレフィックスが渡されていない場合はメンバー用のみフォールバック（後方互換性）
    $prefix = $translationPrefix ?? 'admin/members/form';
    
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
        {{-- When creating new --}}
        <x-form-radio-card-group
            name="email_verified"
            :options="$emailVerificationOptionsCreate"
            :value="$emailVerifiedValueCreate"
            :columns="2"
            :disabled="!$isMailServerTested"
        />
        <p class="description-text">{{ __($prefix . '.account_verification_help_create') }}</p>
    @else
        {{-- When editing --}}
        <x-form-radio-card-group
            name="email_verified"
            :options="$emailVerificationOptionsEdit"
            :value="$emailVerifiedValueEdit"
            :columns="2"
            :disabled="!$isMailServerTested"
        />
        <p class="description-text">{{ __($prefix . '.account_verification_help_edit') }}</p>
        
        {{-- Send verification email button (only when editing) --}}
        <div class="my-4">
            @if($isMailServerTested)
                <x-form-button
                    type="button"
                    variant="secondary"
                    size="sm"
                    :label="__($prefix . '.send_verification_email_button')"
                    icon="fas fa-envelope"
                    id="send-verification-email-btn"
                    @click="sendVerificationEmail({{ $entity->id }})"
                    data-send-route="{{ $sendRoute }}"
                    data-sending-text="{{ __('common.sending') }}..."
                    data-error-message="{{ __('common.error_occurred') }}"
                />
            @else
                <x-form-button
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
        <x-ui-message
            type="info"
            :message="__($prefix . '.account_verification_disabled')"
        />
    @endif
    
    <x-form-error
        :messages="$errors?->get('email_verified') ?? []"
    />
</fieldset>

<!-- 認証メール送信確認モーダル -->
@if($isEdit)
    <x-ui-modal
        id="verificationEmailModal"
        :title="__($prefix . '.send_verification_email_title')"
        :message="__($prefix . ($entityType === 'member' ? '.send_verification_email_confirm' : '.send_verification_email_message'))"
        :confirm-label="__('common.send')"
        :cancel-label="__('common.cancel')"
        icon-type="info"
        confirm-color="blue"
    />
@endif
