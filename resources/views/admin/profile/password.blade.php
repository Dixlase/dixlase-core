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

    <form method="POST" action="{{ route('admin.profile.password.update') }}" id="profile-password-form">
        @csrf

        <!-- パスワード設定 -->
        <section class="transition-colors-unified">
            <h2>{{ __('common.password_settings') }}</h2>
            
            <fieldset>
                <legend>{{ __('admin/profile.password_change_only') }}</legend>
                <x-password-tools
                    name="password"
                    id="profile_password"
                    :required="false"
                    :minLength="$passwordMinLength"
                    :requireUppercase="$passwordRequireUppercase"
                    :requireLowercase="true"
                    :requireNumber="true"
                    :requireSymbol="$passwordRequireSymbol"
                    :showConfirmation="true"
                    :showConfirmationOnChange="true"
                />
            </fieldset>
        </section>

    </form>

@endsection

@section('save')
    <x-save
        id_confirmation="confirmProfilePasswordModal"
        :label="__('common.update')"
        :title="__('admin/profile.confirm_title')"
        :message="__('admin/profile.confirm_message')"
        :confirm_label="__('common.update')"
        :cancel_label="__('common.cancel')"
        form="profile-password-form"
    />
@endsection
