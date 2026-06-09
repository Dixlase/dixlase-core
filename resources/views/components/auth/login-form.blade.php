{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-auth.login-form />

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

@props([
    'routeCheckIdentifier' => '',
    'routePasskeyChallenge' => '',
    'routePasskeyVerify' => '',
    'routeLogin' => '',
    'routePasswordRequest' => null,
    'captchaEnabled' => false,
    'captchaWidget' => null,
    'passkeyEnabled' => false,
    'canResetPassword' => false,
    'translationPrefix' => 'admin/auth.login',
    'oldLogin' => '',
])

<div x-data="loginFlow()" x-cloak
    data-route-check-identifier="{{ $routeCheckIdentifier }}"
    data-route-passkey-challenge="{{ $routePasskeyChallenge }}"
    data-route-passkey-verify="{{ $routePasskeyVerify }}"
    data-trans-error-occurred="{{ __('common.error_occurred') }}"
    data-trans-auth-failed="{{ __('auth.failed') }}"
    data-trans-passkey-cancelled="{{ __($translationPrefix . '.passkey_cancelled') }}"
    data-old-login="{{ $oldLogin }}"
>
    {{-- Alpine.js error message --}}
    <div x-show="errors.login" x-transition class="mb-6 p-4 font-semibold text-red-800 bg-red-100 border border-red-200 rounded-xl dark:text-red-200 dark:bg-red-900 dark:border-red-700">
        <span x-text="errors.login"></span>
    </div>

    {{-- Step 1: Identifier input --}}
    <div x-show="step === 1" x-transition>
        <form @submit.prevent="checkIdentifier">
            @csrf

            <x-captcha
                :enabled="$captchaEnabled"
                :widget="$captchaWidget"
            />

            <!-- メールアドレスまたはアカウント名 -->
            <x-form-input-with-label
                id="login"
                name="login"
                :label="__($translationPrefix . '.login_field')"
                type="text"
                :required="true"
                :autofocus="true"
                autocomplete="username"
                xModel="identifier"
            />

            <!-- 続けるボタン -->
            <div class="mt-6">
                <x-form-button
                    type="submit"
                    variant="primary"
                    size="md"
                    xDisabled="loading || !identifier.trim()"
                    class="w-full"
                >
                    <span x-show="!loading">{{ __($translationPrefix . '.continue') }}</span>
                    <span x-show="loading" class="flex items-center justify-center">
                        <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('common.processing') }}
                    </span>
                </x-form-button>
            </div>

            @if($passkeyEnabled)
                <!-- 区切り線 -->
                <div class="flex items-center my-4">
                    <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
                    <span class="px-4 text-sm text-gray-500 dark:text-gray-400">{{ __('common.or') }}</span>
                    <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
                </div>

                <!-- パスキーボタン -->
                <div class="mt-4">
                    <x-form-button
                        type="button"
                        variant="primary"
                        size="md"
                        xClick="loginWithPasskeyDirect"
                        xDisabled="loading || !identifier.trim()"
                        class="w-full"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        {{ __($translationPrefix . '.login_with_passkey') }}
                    </x-form-button>
                </div>
            @endif
        </form>
    </div>

    {{-- Step 2: Authentication method selection --}}
    <div x-show="step === 2" x-transition>
        {{-- Identifier display --}}
        <div class="mb-6 p-3 bg-gray-50 dark:bg-gray-800 rounded-md">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400" x-text="identifier"></span>
                <x-form-button
                    type="button"
                    variant="ghost"
                    size="xs"
                    xClick="resetFlow"
                    :label="__($translationPrefix . '.change_account')"
                />
            </div>
        </div>

        {{-- Password login form --}}
        <form method="POST" action="{{ $routeLogin }}">
            @csrf
            <input type="text" name="login" x-model="identifier" autocomplete="username" class="sr-only" tabindex="-1" aria-hidden="true" readonly>

            <!-- パスワード -->
            <x-form-input-with-label
                id="password"
                name="password"
                :label="__('common.password')"
                type="password"
                :required="true"
                autocomplete="current-password"
            />

            <!-- Remember Me -->
            <x-form-checkbox
                id="remember_me"
                name="remember"
                :label="$translationPrefix . '.remember_me'"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-500 dark:bg-gray-800 dark:text-indigo-400"
            />

            <!-- ボタンとパスワードリセットリンク -->
            <div class="flex flex-col items-center justify-center mt-6">
                <x-form-button
                    type="submit"
                    variant="primary"
                    :label="__('common.login')"
                    class="w-full dark:focus:ring-offset-gray-800 mb-4"
                />

                @if ($canResetPassword && $routePasswordRequest)
                    <a class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:underline" href="{{ $routePasswordRequest }}">
                        {{ __($translationPrefix . '.forgot_password') }}
                    </a>
                @endif
            </div>
        </form>

        {{-- Passkey authentication button --}}
        @if($passkeyEnabled)
            <div x-show="hasPasskey" class="mt-6">
                <!-- 区切り線 -->
                <div class="mb-4 flex items-center">
                    <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
                    <span class="px-3 text-sm text-gray-500 dark:text-gray-400">{{ __('common.or') }}</span>
                    <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
                </div>

                <x-form-button
                    type="button"
                    variant="primary"
                    size="md"
                    xClick="loginWithPasskey"
                    xDisabled="loading"
                    class="w-full"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    {{ __($translationPrefix . '.login_with_passkey') }}
                </x-form-button>
            </div>
        @endif
    </div>
</div>
