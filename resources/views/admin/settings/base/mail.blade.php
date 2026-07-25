{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
<form id="base-mail-form" action="{{ route('admin.settings.base.mail.update') }}" method="POST" class="overflow-x-hidden">
    @csrf

    <!-- メールサーバー設定 -->
    <section>
        <h2>{{ __('admin/settings/base/mail.mail_server_settings') }}</h2>

        <x-mail-server.form
            :settings="$settings"
            :mailers="$mailers"
            :encryptions="$encryptions"
            context="admin"
        />

        <x-mail-server.test
            context="admin"
            :connectionTestRoute="route('admin.settings.base.mail.test-connection')"
            :mailTestRoute="route('admin.settings.base.mail.test-mail')"
            :showStatus="true"
            :testStatus="[
                'connection_tested' => $mailConnectionTested,
                'send_tested' => $mailSendTested,
                'receive_tested' => $mailReceiveTested,
                'connection_test_date' => $mailConnectionTestDate,
                'send_test_date' => $mailSendTestDate,
                'receive_test_date' => $mailReceiveTestDate
            ]"
        />
    </section>

    <!-- システム管理者メールアドレス -->
    <section>
        <h2>{{ __('admin/settings/base/mail.admin_email_settings') }}</h2>
        <p>{{ __('admin/settings/base/mail.admin_email_settings_description') }}</p>

        <!-- メールサーバー設定の確認メッセージ -->
        @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
            <x-ui-message
                type="warning"
                :message="__('admin/settings/base/mail.admin_email_mail_test_required')"
            />
        @endif

        <fieldset>
            <legend>{{ __('admin/settings/base/mail.admin_email') }}</legend>
            <x-form-text
                type="email"
                name="system_admin_email"
                :value="old('system_admin_email', $settings['system_admin_email'])"
                class="input-lg"
            />
            <p>{{ __('admin/settings/base/mail.admin_email_help') }}</p>
        </fieldset>
    </section>
</form>
</div>

@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="base-mail-form"
    />
@endsection

@section('scripts')
{{-- CSP support: Pass settings via data attributes --}}
<div data-mail-settings-config='@json($mailSettingsConfig)' style="display:none;"></div>
@endsection
