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
@section('title', __('admin.auth.forgot_password.title'))
@section('header', __('admin.auth.forgot_password.header'))
@section('description')
    {!! __('admin.auth.forgot_password.description') !!}
@endsection

@section('content')
    <x-auth.forgot-password-form 
        :action="route('admin.password.email')"
        :email-label="__('admin.auth.forgot_password.email')"
        :submit-text="__('admin.auth.forgot_password.send_reset_link')"
        :back-text="__('admin.auth.forgot_password.back_to_login')"
        :back-url="route('admin.login')"
    />
@endsection
