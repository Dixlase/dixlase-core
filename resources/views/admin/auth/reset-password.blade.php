{{--
This file is part of MySoftware.

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

@extends('admin::partials.layout-auth')
@section('title', __('admin.auth.reset_password.title'))
@section('header', __('admin.auth.reset_password.header'))
@section('description', __('admin.auth.reset_password.description'))

@section('content')
    <x-auth.reset-password-form 
        :action="route('admin.password.store')"
        :token="$request->route('token')"
        :email="$request->email"
        :email-label="__('admin.auth.reset_password.email')"
        :password-label="__('admin.auth.reset_password.password')"
        :submit-text="__('admin.auth.reset_password.reset_password_button')"
        :password-min-length="$passwordMinLength"
        :password-require-uppercase="$passwordRequireUppercase"
        :password-require-symbol="$passwordRequireSymbol"
    />
@endsection
