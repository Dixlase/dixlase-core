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
@section('title', __('admin/auth.login.title'))
@section('header', __('admin/auth.login.header'))
@section('description', __('admin/auth.login.description'))

@section('content')
    {{-- メール認証待ちメッセージ --}}
    @if(session('email_verification_pending') || session('info'))
        <x-message
            type="info"
            :message="session('info') ?? __('account.verify_email_login_required')"
        />
    @endif

    <div x-data="loginFlow()" x-cloak>
        {{-- ステップ1: 識別子入力 --}}
        <div x-show="step === 1" x-transition>
            <form @submit.prevent="checkIdentifier">
                @csrf

                <x-captcha
                    :enabled="$captchaEnabled ?? false"
                    :widget="$captchaWidget ?? null"
                />

                <!-- メールアドレスまたはアカウント名 -->
                <div class="mb-4">
                    <label for="login" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('admin/auth.login.login_field') }}
                    </label>
                    <input
                        type="text"
                        id="login"
                        name="login"
                        x-model="identifier"
                        required
                        autofocus
                        autocomplete="username"
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                        :class="{ 'border-red-500': errors.login }"
                    >
                    <p x-show="errors.login" x-text="errors.login" class="mt-1 text-sm text-red-600 dark:text-red-400"></p>
                </div>

                <!-- 続けるボタン -->
                <div class="flex flex-col items-center justify-center mt-6">
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        <span x-show="!loading">{{ __('admin/auth.login.continue') }}</span>
                        <span x-show="loading" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('common.processing') }}
                        </span>
                    </button>
                </div>
            </form>
        </div>

        {{-- ステップ2: 認証方法選択 --}}
        <div x-show="step === 2" x-transition>
            {{-- 識別子表示 --}}
            <div class="mb-6 p-3 bg-gray-50 dark:bg-gray-800 rounded-md">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400" x-text="identifier"></span>
                    <button
                        type="button"
                        @click="resetFlow"
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                    >
                        {{ __('admin/auth.login.change_account') }}
                    </button>
                </div>
            </div>

            {{-- パスキー認証ボタン --}}
            <div x-show="hasPasskey" class="mb-4">
                <button
                    type="button"
                    @click="loginWithPasskey"
                    :disabled="loading"
                    class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-indigo-500 dark:hover:bg-indigo-600 flex items-center justify-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    {{ __('admin/auth.login.login_with_passkey') }}
                </button>
                <div class="mt-4 mb-4 flex items-center">
                    <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
                    <span class="px-3 text-sm text-gray-500 dark:text-gray-400">{{ __('common.or') }}</span>
                    <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
                </div>
            </div>

            {{-- パスワードログインフォーム --}}
            <form method="POST" action="{{ route('admin.login') }}">
                @csrf
                <input type="hidden" name="login" x-model="identifier">

                <!-- パスワード -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('common.password') }}
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                    >
                </div>

                <!-- Remember Me -->
                <x-form.checkbox
                    id="remember_me"
                    name="remember"
                    label="admin/auth.login.remember_me"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-500 dark:bg-gray-800 dark:text-indigo-400"
                />

                <!-- ボタンとパスワードリセットリンク -->
                <div class="flex flex-col items-center justify-center mt-6">
                    <x-form.button
                        type="submit"
                        variant="primary"
                        :label="__('common.login')"
                        class="dark:focus:ring-offset-gray-800 mb-4"
                    />

                    @if (($canResetPassword ?? false) && Route::has('admin.password.request'))
                        <a class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:underline" href="{{ route('admin.password.request') }}">
                            {{ __('admin/auth.login.forgot_password') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection


@section('back_link')
    <a class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:underline" href="{{ route('welcome') }}">
        {{ __('admin/auth.login.back_to_welcome') }}
    </a>
@endsection

@push('scripts')
<script>
function loginFlow() {
    return {
        step: 1,
        identifier: '',
        hasPasskey: false,
        loading: false,
        errors: {},

        async checkIdentifier() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await fetch('{{ route('admin.login.check-identifier') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        login: this.identifier
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    this.hasPasskey = data.has_passkey;
                    this.step = 2;
                } else {
                    if (data.errors) {
                        this.errors = data.errors;
                    } else if (data.message) {
                        this.errors.login = data.message;
                    }
                }
            } catch (error) {
                console.error('Identifier check error:', error);
                this.errors.login = '{{ __('common.error_occurred') }}';
            } finally {
                this.loading = false;
            }
        },

        async loginWithPasskey() {
            this.loading = true;
            this.errors = {};

            try {
                // チャレンジを取得
                const challengeResponse = await fetch('{{ route('admin.login.passkey.challenge') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        login: this.identifier
                    })
                });

                const challengeData = await challengeResponse.json();

                if (!challengeResponse.ok) {
                    this.errors.login = challengeData.error || '{{ __('common.error_occurred') }}';
                    this.loading = false;
                    return;
                }

                // WebAuthn認証を実行
                const credential = await navigator.credentials.get({
                    publicKey: challengeData.publicKey
                });

                if (!credential) {
                    this.errors.login = '{{ __('auth.failed') }}';
                    this.loading = false;
                    return;
                }

                // 認証情報を送信
                const verifyResponse = await fetch('{{ route('admin.login.passkey.verify') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id: credential.id,
                        rawId: this.arrayBufferToBase64(credential.rawId),
                        type: credential.type,
                        response: {
                            authenticatorData: this.arrayBufferToBase64(credential.response.authenticatorData),
                            clientDataJSON: this.arrayBufferToBase64(credential.response.clientDataJSON),
                            signature: this.arrayBufferToBase64(credential.response.signature),
                            userHandle: credential.response.userHandle ? this.arrayBufferToBase64(credential.response.userHandle) : null
                        }
                    })
                });

                const verifyData = await verifyResponse.json();

                if (verifyResponse.ok && verifyData.success) {
                    // ログイン成功 - リダイレクト
                    window.location.href = verifyData.redirect;
                } else {
                    this.errors.login = verifyData.error || '{{ __('auth.failed') }}';
                    this.loading = false;
                }
            } catch (error) {
                console.error('Passkey login error:', error);
                
                // ユーザーがキャンセルした場合
                if (error.name === 'NotAllowedError') {
                    this.errors.login = '{{ __('admin/auth.login.passkey_cancelled') }}';
                } else {
                    this.errors.login = '{{ __('common.error_occurred') }}';
                }
                
                this.loading = false;
            }
        },

        arrayBufferToBase64(buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            return btoa(binary);
        },

        resetFlow() {
            this.step = 1;
            this.identifier = '';
            this.hasPasskey = false;
            this.errors = {};
        }
    }
}
</script>
@endpush
