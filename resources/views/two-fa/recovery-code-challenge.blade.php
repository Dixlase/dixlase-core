{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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

@section('title', __('two-fa/recovery-code.title'))
@section('icon')
["fas fa-life-ring", "fas fa-key"]
@endsection
@section('header', __('two-fa/recovery-code.title'))
@section('description', __('two-fa/recovery-code.prompt'))

@section('content')
    @php
        $formAction = $action ?? route('admin.two-fa.recovery-code.confirm');
        $contextValue = $context ?? 'admin';
    @endphp
    <form method="POST" action="{{ $formAction }}" class="space-y-6" id="recoveryCodeForm">
        @csrf

        <!-- 回復コード入力 -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('two-fa/recovery-code.code_label') }}
            </label>
            
            <!-- 5桁×4ブロックの入力フィールド -->
            <div class="flex items-center justify-center gap-2">
                <input
                    type="text"
                    id="code1"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="12345"
                    required
                    autofocus
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
                <span class="text-2xl text-gray-400 dark:text-gray-500">-</span>
                <input
                    type="text"
                    id="code2"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="67890"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
                <span class="text-2xl text-gray-400 dark:text-gray-500">-</span>
                <input
                    type="text"
                    id="code3"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="12345"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
                <span class="text-2xl text-gray-400 dark:text-gray-500">-</span>
                <input
                    type="text"
                    id="code4"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="67890"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
            </div>

            <!-- 隠しフィールド（実際に送信される値） -->
            <input type="hidden" name="recovery_code" id="recovery_code">

            @error('recovery_code')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400 text-center">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                {{ __('two-fa/recovery-code.format_hint') }}
            </p>
        </div>

        <!-- CAPTCHA -->
        <x-captcha :enabled="$captchaEnabled ?? false" :widget="$captchaWidget ?? null" />

        <!-- 送信ボタン -->
        <div>
            <button
                type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600"
            >
                {{ __('two-fa/recovery-code.submit') }}
            </button>
        </div>
    </form>

    <!-- メール認証へのリンク -->
    @if(isset($emailChallengeRoute))
        <div class="mt-4 text-center">
            <a href="{{ route($emailChallengeRoute) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                <i class="fas fa-envelope mr-1"></i>{{ __('two-fa/email.use_email_code') }}
            </a>
        </div>
    @endif
@endsection

@section('back_link')
    @php
        $backRoute = $loginRoute ?? route('admin.login');
    @endphp
    <a href="{{ $backRoute }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('two-fa/common.back_to_login') }}
    </a>
@endsection

<script type="application/json" id="recovery-code-challenge-config">
{
    "translations": {
        "format_hint": "{{ __('two-fa/recovery-code.format_hint') }}"
    }
}
</script>
