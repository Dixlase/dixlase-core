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

{{-- Confirm the member's identity before a sensitive operation (#675). --}}
@extends('layouts.auth')

@section('title', __('admin/recent-auth.title'))
@section('icon')
["fas fa-user-shield"]
@endsection
@section('header', __('admin/recent-auth.title'))
@section('description', __('admin/recent-auth.description', ['minutes' => $minutes]))

@section('content')
    <div x-data="recentAuth" x-cloak class="space-y-6"
        data-route-passkey-options="{{ route('admin.recent-auth.passkey.options') }}"
        data-route-passkey-verify="{{ route('admin.recent-auth.passkey.verify') }}"
        data-trans-passkey-failed="{{ __('admin/recent-auth.passkey_failed') }}"
        data-trans-passkey-cancelled="{{ __('admin/recent-auth.passkey_cancelled') }}"
        data-trans-error="{{ __('admin/recent-auth.error') }}">

        @if ($hasPassword)
            <form method="POST" action="{{ route('admin.recent-auth.password') }}" class="space-y-4">
                @csrf
                <div>
                    <x-form-label for="recent-auth-password" :text="__('admin/recent-auth.password_label')" />
                    <x-form-text id="recent-auth-password" name="password" type="password"
                        autocomplete="current-password" :required="true" :showPasswordToggle="true" class="w-full" />
                    <x-form-error name="password" />
                </div>
                <x-form-button type="submit" variant="primary" class="w-full justify-center"
                    :label="__('admin/recent-auth.submit')" />
            </form>
        @endif

        @if ($canUsePasskey)
            @if ($hasPassword)
                <p class="text-center text-sm text-gray-500 dark:text-gray-400">{{ __('admin/recent-auth.or') }}</p>
            @endif
            <div>
                <x-form-button type="button" variant="secondary" class="w-full justify-center"
                    icon="fas fa-fingerprint" xClick="usePasskey" xDisabled="loading"
                    :label="__('admin/recent-auth.use_passkey')" />
                <p x-show="error" x-text="error" role="alert"
                    class="mt-2 text-center text-sm text-red-600 dark:text-red-400"></p>
            </div>
        @endif

        @if (! $hasPassword && ! $canUsePasskey)
            <x-ui-message type="warning" :message="__('admin/recent-auth.no_method')" />
        @endif
    </div>
@endsection

@section('back_link')
    <a href="{{ $cancelUrl }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('admin/recent-auth.cancel') }}
    </a>
@endsection
