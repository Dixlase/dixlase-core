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

@extends('layouts.auth')
@section('title', __('auth.verification_required'))
@section('header', __('auth.verification_required'))

@section('content')
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('auth.verification_notice_message') }}
    </div>

    @if (session('resent'))
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('auth.verification_link_sent') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('admin.verification.send') }}">
            @csrf
            @include('components.form.button', [
                'type' => 'submit',
                'variant' => 'primary',
                'label' => __('auth.resend_verification_email')
            ])
        </form>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            @include('components.form.button', [
                'type' => 'submit',
                'variant' => 'secondary',
                'label' => __('common.logout')
            ])
        </form>
    </div>
@endsection
