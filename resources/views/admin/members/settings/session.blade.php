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
    <form method="POST" action="{{ route('admin.members.settings.session.update') }}" id="member-settings-form">
        @csrf
        <input type="hidden" name="settings_section" value="session">

        <!-- 管理メンバー用セッション設定 -->
        <section>
            <h2>{{ __('admin/members/settings/session.admin_settings') }}</h2>
            <p>
                {{ __('admin/members/settings/session.admin_settings_description') }}
            </p>

            <!-- セッション有効時間カスタマイズ有効/無効 -->
            <fieldset>
                <x-form.toggle
                    name="members_session_lifetime_enabled"
                    :label="__('admin/members/settings/session.lifetime_enabled')"
                    :checked="old('members_session_lifetime_enabled', $membersSessionLifetimeEnabled)"
                />
                <p class="mt-2">
                    {{ __('admin/members/settings/session.lifetime_enabled_help') }}
                </p>
            </fieldset>

            <!-- 管理メンバー用セッション有効時間 -->
            <fieldset>
                <legend>{{ __('admin/members/settings/session.lifetime') }}</legend>
                <div class="flex items-center">
                    <x-form.text
                        type="number"
                        name="members_session_lifetime"
                        :value="old('members_session_lifetime', $membersSessionLifetime)"
                        :min="1"
                        :max="43200"
                        class="input-common input-sm"
                    />
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin/members/settings/index.minutes') }}</span>
                </div>
                <p>
                    {{ __('admin/members/settings/session.lifetime_help') }}
                </p>
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
