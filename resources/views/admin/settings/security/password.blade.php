{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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
    <form id="security-password-form" method="POST" action="{{ route('admin.settings.security.password.update') }}">
        @csrf
        
        

        <!-- デフォルトパスワードポリシー -->
        <section>
            <h2>{{ __('admin/settings/security/password.default_password_policy') }}</h2>
            <p>{{ __('admin/settings/security/password.default_password_policy_description') }}</p>
        </section>

        <x-security.password-settings
            :minLength="$settings['password_min_length']"
            :requireUppercase="$settings['password_require_uppercase'] == '1'"
            :requireNumber="$settings['password_require_number'] == '1'"
            :requireSymbol="$settings['password_require_symbol'] == '1'"
            :resetEnabled="$settings['password_reset_enabled'] == '1'"
            :pwnedCheckEnabled="$settings['pwned_password_check_enabled'] == '1'"
            :showPwnedCheck="true"
            :minLengthOptions="$minLengthOptions"
            :isMailServerTested="true"
        />

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
        form="security-password-form"
    />
@endsection
