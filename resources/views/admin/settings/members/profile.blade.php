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

@extends('admin::partials.layout')

@section('content')


    {{-- {{ __('admin.settings.members.profile.heading') }} --}}
    <form method="POST" action="{{ route('admin.settings.members.profile.update') }}" id="profile-form" class="mb-8">
        @csrf

        <!-- 名前 -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('admin.settings.members.profile.name') }}</label>
            <input type="text" name="name" value="{{ old('name', $member->name) }}"
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- 説明 -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('admin.settings.members.profile.description') }}</label>
            <textarea name="description"
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2"
                rows="3">{{ old('description', $member->description) }}</textarea>
            @error('description')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- メールアドレス -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('admin.settings.members.profile.email') }}</label>
            <input type="email" name="email" value="{{ old('email', $member->email) }}"
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- パスワード -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.settings.members.profile.password_change_only') }}</label>
            @include('components.form.password-tools', [
                'name' => 'password',
                'id' => 'profile_password',
                'required' => false,
                'minLength' => $passwordMinLength,
                'requireUppercase' => $passwordRequireUppercase,
                'requireLowercase' => true,
                'requireNumber' => true,
                'requireSymbol' => $passwordRequireSymbol,
                'showConfirmation' => true
            ])
        </div>

        <!-- 外観モードの設定 -->
        @php
            $appearanceValue = old('appearance', (string) ($member->appearance->value ?? 0));
        @endphp

        <div x-data="{
            localTheme: '{{ $appearanceValue }}',
            savedTheme: '{{ $appearanceValue }}',
            applyLocalTheme() {
                const isDark = this.localTheme === '2' || (this.localTheme === '0' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', isDark);
                document.documentElement.classList.toggle('light', !isDark);
            },
            resetToSavedTheme() {
                this.localTheme = this.savedTheme;
                this.applyLocalTheme();
            }
        }" x-init="
            // 初期化時に保存された値でDOMをリセット
            resetToSavedTheme();
            $watch('localTheme', () => applyLocalTheme());
        " class="mb-6" data-profile-theme>
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.settings.members.profile.appearance_mode') }}</label>

            <div class="flex gap-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="appearance" value="0" x-model="localTheme" class="form-radio text-indigo-600">
                    <span class="ml-2">{{ __('admin.settings.members.profile.appearance_auto') }}</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="appearance" value="1" x-model="localTheme" class="form-radio text-indigo-600">
                    <span class="ml-2">{{ __('admin.settings.members.profile.appearance_light') }}</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="appearance" value="2" x-model="localTheme" class="form-radio text-indigo-600">
                    <span class="ml-2">{{ __('admin.settings.members.profile.appearance_dark') }}</span>
                </label>
            </div>
        </div>


        <!-- ログイン通知の設定 -->
        <div class="mb-6">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                {{ __('admin.settings.members.profile.login_notification_setting') }}
            </label>


            @php
                // ログイン通知モードの表示用の value を決定
                //全体設定で0以外に場合は、全体設定を優先。ラジオボタンの表示もそれを反映させる
                $loginNotificationDisplayValue = in_array((int) $loginNoticeGlobal, [1, 2, 3])
                    ? (string) $loginNoticeGlobal
                    : (string) old('login_notification_mode', (string) $member->login_notification_mode ?? '1');
            @endphp


            @include('components.form.radio-group', [
                'name' => 'login_notification_mode',
                'options' => $loginNotificationOptions,
                'value' => $loginNotificationDisplayValue,
                'disabled' => in_array((int) $loginNoticeGlobal, [1, 2, 3]),
            ])

            @if ($loginNoticeGlobal !== 0)
                @php
                    $forceLoginNoticeName = __('admin.settings.members.login_notification_mode.options.' . $loginNoticeGlobal);
                @endphp
                <p class="text-sm mt-3 text-gray-600 dark:text-gray-400">
                    {{ __('admin.settings.members.force_setting_1') }}「{{ $forceLoginNoticeName }}」{{ __('admin.settings.members.force_setting_2') }}
                </p>
            @endif
        </div>

        <!-- 2段階認証の設定 -->
        <div class="mb-6">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                {{ __('admin.settings.members.profile.two_factor_setting') }}
            </label>

            @php
                // 2FA 表示用 value を決定
                //全体設定で0以外に場合は、全体設定を優先。ラジオボタンの表示もそれを反映させる
                $twoFactorDisplayValue = in_array((int) $force2fa, [1, 2, 3])
                    ? (string) $force2fa
                    : (string) old('two_factor_mode', $member->login_notification_mode ?? 1); // fallback: Disabled
            @endphp


            @include('components.form.radio-group', [
                'name' => 'two_factor_mode',
                'options' => $twoFactorOptions,
                'value' => $twoFactorDisplayValue,
                'disabled' => in_array((int) $force2fa, [1, 2, 3]),
            ])


            @php
                $force2fa_name = __('admin.settings.members.two_factor_mode.options.' . $force2fa);
            @endphp

            @if ((int) $force2fa !== 0)
                <p class="text-sm mt-3 text-gray-600 dark:text-gray-400">
                    {{__('admin.settings.members.force_setting_1')}} {{ $force2fa_name }}」{{__('admin.settings.members.force_setting_2')}}
                </p>
            @endif
        </div>
    </form>



@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // フォーム送信成功時にグローバルテーマストアを更新
    @if(session('success'))
        const savedAppearance = '{{ old('appearance', (string) ($member->appearance->value ?? 0)) }}';
        if (window.themeStore) {
            window.themeStore.theme = savedAppearance;
            window.themeStore.applyTheme();
        }
    @endif
});
</script>
@endpush

@section('save')
    <!-- {{ __('admin.settings.members.profile.update_button') }} -->
    @include('components::form.save', [
        'id' => 'confirmationModal',
        'label' => __('admin.settings.members.profile.update_button'),
        'onclick' => "openModal('confirmProfileModal')",
        'title' => __('admin.settings.members.profile.confirm_title'),
        'message' => __('admin.settings.members.profile.confirm_message'),
        'confirm_label' => __('admin.settings.members.profile.confirm_label'),
        'cancel_label' => __('admin.settings.members.profile.cancel_label'),
        'form' => 'profile-form',
    ])
@endsection

