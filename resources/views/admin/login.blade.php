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

@extends('layouts.auth')
@section('title', __('admin/auth.login.title'))
@section('header', __('admin/auth.login.header'))
@section('description', __('admin/auth.login.description'))

@section('content')
    {{-- Configurable login-screen notice (base settings › admin panel; empty = hidden) --}}
    @if(! empty($loginNotice ?? null))
        <x-ui-message type="info" :message="$loginNotice" />
    @endif

    {{-- Email verification pending message --}}
    @if(session('email_verification_pending') || session('info'))
        <x-ui-message
            type="info"
            :message="session('info') ?? __('account.verify_email_login_required')"
        />
    @endif

    <x-auth.login-form
        :routeCheckIdentifier="route('admin.login.check-identifier')"
        :routePasskeyChallenge="route('admin.login.passkey.challenge')"
        :routePasskeyVerify="route('admin.login.passkey.verify')"
        :routeLogin="route('admin.login')"
        :routePasswordRequest="Route::has('admin.password.request') ? route('admin.password.request') : null"
        :captchaEnabled="$captchaEnabled ?? false"
        :captchaWidget="$captchaWidget ?? null"
        :passkeyEnabled="$passkeyEnabled ?? false"
        :canResetPassword="$canResetPassword ?? false"
        translationPrefix="admin/auth.login"
        :oldLogin="old('login')"
    />
@endsection


@section('back_link')
    <a class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:underline" href="{{ $backUrl ?? url('/') }}">
        {{ __('admin/auth.login.back_to_welcome') }}
    </a>
@endsection

