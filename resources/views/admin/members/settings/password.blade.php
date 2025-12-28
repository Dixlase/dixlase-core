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
<div class="max-w-7xl mx-auto">
    <form method="POST" action="{{ route('admin.members.settings.password.update') }}" id="member-settings-form">
        @csrf
        <input type="hidden" name="settings_section" value="password">

        <!-- パスワード条件設定 -->
        <section>
            <h2>{{ __('admin/members/settings/password.conditions') }}</h2>
            <fieldset>
                <legend>{{ __('admin/members/settings/password.min_length') }}</legend>
                <x-form.radio-card-group
                    name="password_min_length"
                    :options="$minLengthOptions"
                    :value="old('password_min_length', (string) $passwordMinLength)"
                    :columns="3"
                    class="mb-4"
                />
            </fieldset>

            <!-- 大文字 -->
            <fieldset>
                <x-form.toggle
                    name="password_require_uppercase"
                    :label="__('admin/members/settings/password.require_uppercase')"
                    :checked="old('password_require_uppercase', $passwordRequireUppercase)"
                />
            </fieldset>

            <!-- 数字 -->
            <fieldset>
                <x-form.toggle
                    name="password_require_number"
                    :label="__('admin/members/settings/password.require_number')"
                    :checked="old('password_require_number', $passwordRequireNumber)"
                />
            </fieldset>

            <!-- 記号 -->
            <fieldset>
                <x-form.toggle
                    name="password_require_symbol"
                    :label="__('admin/members/settings/password.require_symbol')"
                    :checked="old('password_require_symbol', $passwordRequireSymbol)"
                />
            </fieldset>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                {{ __('admin/members/settings/password.security_warning') }}
            </p>
        </section>

        <!-- パスワードリセット機能設定 -->
        <section>
            <h2>{{ __('admin/members/settings/password.reset_settings') }}</h2>
            @if(!$isMailServerTested)
                <x-message
                    type="warning"
                    :message="__('admin/members/settings.mail_server_test_warning', ['url' => route('admin.settings.base.mail')])"
                />
            @endif
            <fieldset>
                <x-form.toggle
                    name="password_reset_enabled"
                    :label="__('admin/members/settings/password.reset_enabled')"
                    :checked="old('password_reset_enabled', $passwordResetEnabled)"
                />
                <p class="mt-2">
                    {!! __('admin/members/settings/password.reset_help') !!}
                </p>
            </fieldset>
        </section>

        <!-- パスワード辞書攻撃対策設定 -->
        <section>
            <h2>{{ __('admin/members/settings/password.pwned_settings') }}</h2>

            <fieldset>
                <x-form.toggle
                    name="pwned_password_check_enabled"
                    :label="__('admin/members/settings/password.pwned_check_enabled')"
                    :checked="old('pwned_password_check_enabled', $pwnedPasswordCheckEnabled)"
                />
                <p class="mt-2">
                    {!! __('admin/members/settings/password.pwned_help') !!}
                </p>
                <!-- API情報 -->
                <x-message
                    type="info"
                    :message="__('admin/members/settings/password.pwned_api_info')"
                />
            </fieldset>
        </section>
    </form>
</div>
@endsection

@section('save')
    <x-form.button
        type="button"
        :label="__('common.update')"
        class="button-save"
        onclick="openModal('confirmationModal')"
    />
@endsection

@section('modals')
    <x-modal
        id="confirmationModal"
        :title="__('common.update_confirmation_title')"
        :message="__('common.update_confirmation_message')"
        :confirm_label="__('common.update')"
        :cancel_label="__('common.cancel')"
        form="member-settings-form"
    />
@endsection

@section('scripts')
    <script @cspNonce>
        document.addEventListener('DOMContentLoaded', function() {
            const modals = document.querySelectorAll('[id$="Modal"]');

            modals.forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });

            const confirmationModal = document.getElementById('confirmationModal');

            if (confirmationModal) {
                const confirmButton = confirmationModal.querySelector('button[type="submit"]');
                if (confirmButton) {
                    confirmButton.addEventListener('click', () => {
                        document.getElementById('member-settings-form').submit();
                    });
                }
            }
        });
    </script>
@endsection
