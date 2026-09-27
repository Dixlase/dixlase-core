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

@extends('layouts.install')

@section('title', __('install/step4.mail_title'))
@section('header', __('install/step4.mail_header'))
@section('description')
    {!! __('install/step4.mail_description') !!}
@endsection

@section('content')
<form action="{{ route('install.mail.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- メールサーバー設定セクション -->
    <section aria-labelledby="mail-server-heading">
        <h2 id="mail-server-heading" class="sr-only">{{ __('install/step4.mail_server_settings') }}</h2>
        
        <x-mail-server.form
            :settings="[]"
            context="install"
            :admin_email="$admin_email"
            :mailers="$mailers"
            :encryptions="$encryptions"
        />
    </section>

    <!-- メール接続テストセクション -->
    <section aria-labelledby="mail-test-heading">
        <h2 id="mail-test-heading" class="sr-only">{{ __('install/step4.mail_connection_test') }}</h2>
        
        <x-mail-server.test
            context="install"
            :connectionTestRoute="route('install.mail.test-connection')"
            :mailTestRoute="route('install.mail.test-send')"
            :showStatus="false"
            :testStatus="$testStatus"
        />
    </section>

    <!-- フォームナビゲーション -->
    <nav aria-label="{{ __('install/common.form_navigation') }}" class="flex justify-center mt-6">
        <a href="{{ route('install.database') }}"
            class="bg-gray-500 dark:bg-gray-600 text-white py-2 px-4 mx-4 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 transition">
            {{ __('install/common.back') }}
        </a>
        <x-form-button
            type="submit"
            variant="primary"
            :label="__('install/common.next')"
            class="mx-4"
        />
    </nav>
</form>

@endsection
