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

@extends('layouts.admin')

@section('content')

    <form method="POST" action="{{ route('admin.profile.update') }}" id="profile-form">
        @csrf

        <!-- 基本情報 -->
        <section class="transition-colors-unified">
            <h2>{{ __('common.basic_info') }}</h2>
            
            <fieldset>
                <legend>{{ __('common.account_name') }}</legend>
                <x-form.text
                    name="account_name"
                    :value="old('account_name', $member->account_name)"
                    :required="true"
                    pattern="^[a-zA-Z0-9]+$"
                    minlength="3"
                    maxlength="20"
                    class="w-full"
                />
                <p class="description-text">{!! __('admin/profile.account_name_help') !!}</p>
                @error('account_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset>
                <legend>{{ __('common.display_name') }}</legend>
                <x-form.text
                    name="display_name"
                    :value="old('display_name', $member->display_name)"
                    class="w-full"
                />
                <p class="description-text">{{ __('admin/profile.display_name_help') }}</p>
                @error('display_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset>
                <legend>{{ __('common.description') }}</legend>
                <x-form.textarea
                    name="description"
                    :value="old('description', $member->description)"
                    :rows="3"
                    class="w-full"
                />
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-email-input
                id="profile_email"
                name="email"
                :value="old('email', $member->email)"
                :required="false"
                :showConfirmation="true"
                :showConfirmationOnChange="true"
            />
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            @error('email_confirmation')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            
            @if($hasPendingEmail)
                <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        {!! __('admin/profile.pending_email_notice', ['email' => $pendingEmail]) !!}
                    </p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                        {{ __('admin/profile.current_email', ['email' => $member->email]) }}
                    </p>
                </div>
            @else
                <p class="description-text">
                    @if($isMailServerTested)
                        {!! __('admin/profile.email_change_help') !!}
                    @else
                        {!! __('admin/profile.email_change_help_no_mail') !!}
                    @endif
                </p>
            @endif

            <fieldset>
                <legend>{{ __('common.locale') }}</legend>
                <x-form.select
                    name="locale"
                    :options="$localeOptions"
                    :value="old('locale', $member->locale?->value)"
                    :nullable="true"
                    :nullLabel="__('admin/profile.use_system_default')"
                />
                @error('locale')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p>{{ __('admin/profile.language_help') }}</p>
            </fieldset>
        </section>

        <!-- パスワード設定 -->
        <section class="transition-colors-unified">
            <h2>{{ __('common.password_settings') }}</h2>
            
            <fieldset>
                <legend>{{ __('admin/profile.password_change_only') }}</legend>
                <x-password-tools
                    name="password"
                    id="profile_password"
                    :required="false"
                    :minLength="$passwordMinLength"
                    :requireUppercase="$passwordRequireUppercase"
                    :requireLowercase="true"
                    :requireNumber="true"
                    :requireSymbol="$passwordRequireSymbol"
                    :showConfirmation="true"
                    :showConfirmationOnChange="true"
                />
            </fieldset>
        </section>

        <!-- 外観設定 -->
        @php
            $appearanceValue = old('appearance', (string) ($member->appearance->value ?? 0));
        @endphp

        <section class="transition-colors-unified">
            <h2>{{ __('common.appearance_settings') }}</h2>
            <div class="lg:w-1/2">
                <x-appearance-mode-selector
                    name="appearance"
                    :value="$appearanceValue"
                    :enableRealtimeSwitch="true"
                    :columns="3"
                    color="primary"
                    variant="filled"
                    :showCheck="true"
                />
            </div>
        </section>

        <!-- ログイン通知設定 -->
        @php
            $loginNotificationModeValue = $loginNotificationMode instanceof \App\Enums\AuthenticationMode 
                ? $loginNotificationMode->value 
                : ($loginNotificationMode ?? 1);
        @endphp

        <section class="transition-colors-unified">
            <h2>{{ __('auth.login_notification_mode.label') }}</h2>
            <x-login-notification-selector
                name="login_notification_mode"
                :value="old('login_notification_mode', (string) $loginNotificationModeValue)"
                :globalSetting="(int) ($loginNoticeGlobal ?? 0)"
                :excludeUseProfileSetting="true"
                :columns="3"
            />
        </section>

        <!-- 二段階認証設定（メールサーバー設定済みの場合のみ表示） -->
        @if($isMailServerTested && ($twoFaForceMode === \App\Enums\AuthenticationMode::UseProfileSetting->value || $twoFaCurrentGlobalMode))
            @php
                $member = Auth::guard('member')->user();
                $twoFaPasskeyGloballyEnabled = in_array(\App\Enums\TwoFaMethod::PASSKEY->value, array_keys($twoFaEnabledMethods ?? []));
            @endphp
            
            <section class="transition-colors-unified">
                <h2>{{ __('auth.two_fa_settings') }}</h2>

                <x-two-fa-auth-selector
                    name="two_fa_mode"
                    :value="old('two_fa_mode', (string) ($twoFaMode?->value ?? 0))"
                    :globalSetting="$twoFaForceMode"
                    :excludeUseProfileSetting="true"
                    :twoFaPasskeyGloballyEnabled="$twoFaPasskeyGloballyEnabled"
                    :twoFaPasskeyEnabled="$currentPasskeyEnabled"
                    :twoFaDefaultMethod="(string) (Auth::guard('member')->user()->two_fa_default_method ?? $twoFaDefaultMethod)"
                    :columns="3"
                />
            </section>

            {{-- パスキー設定セクション --}}
            <section class="transition-colors-unified">
                <h2>{{ __('common.passkey_mode.label') }}</h2>
                
                @if($isPasskeyEditable)
                    {{-- プロフィール設定に従う場合：トグルで編集可能 --}}
                    <fieldset>
                        <legend>{{ __('common.passkey_mode.help.profile_editable') }}</legend>
                        <x-form.toggle
                            name="two_fa_passkey_enabled"
                            :label="__('common.passkey_mode.options.enabled')"
                            :checked="old('two_fa_passkey_enabled', $currentPasskeyEnabled)"
                        />
                    </fieldset>
                @else
                    {{-- 全体設定で強制されている場合：表示のみ --}}
                    <fieldset>
                        <legend>
                            @if($forcedPasskeyValue === false)
                                {{ __('common.passkey_mode.help.profile_forced_disabled') }}
                            @else
                                {{ __('common.passkey_mode.help.profile_forced_enabled') }}
                            @endif
                        </legend>
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-md">
                            <i class="fas {{ $forcedPasskeyValue ? 'fa-check-circle text-green-600 dark:text-green-400' : 'fa-times-circle text-gray-400 dark:text-gray-600' }}"></i>
                            <span class="text-sm font-medium {{ $forcedPasskeyValue ? 'text-green-800 dark:text-green-200' : 'text-gray-600 dark:text-gray-400' }}">
                                {{ $forcedPasskeyValue ? __('common.passkey_mode.options.enabled') : __('common.passkey_mode.options.disabled') }}
                            </span>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-info-circle mr-1"></i>
                            {{ __('common.global_setting_fixed.two_fa', ['account_type' => __('admin/members/index.member')]) }}
                        </p>
                    </fieldset>
                @endif
            </section>
        @endif
    </form>

    <!-- 2FA管理セクション（メールサーバー設定済み、かつ二段階認証が有効の場合のみ表示） -->
    @if($isMailServerTested && $twoFaForceMode !== \App\Enums\AuthenticationMode::Disabled->value)
        <x-two-fa-management
            :twoFaPasskeyEnabled="$twoFaPasskeyEnabled"
            :twoFaPasskeyDevices="$twoFaPasskeyDevices"
            :twoFaHasRecoveryCodes="$twoFaHasRecoveryCodes"
            :twoFaRecoveryCodesCount="$twoFaRecoveryCodesCount"
            :twoFaTrustedDevices="$trustedDevices ?? collect()"
            :twoFaShowTrustedDevices="true"
            :routes="[
                'passkey_register_options' => route('admin.profile.passkey.register-options'),
                'passkey_register' => route('admin.profile.passkey.register'),
                'passkey_delete' => route('admin.profile.passkey.revoke', ':id'),
                'passkey_delete_all' => route('admin.profile.passkey.revoke-all'),
                'recovery_codes_generate' => route('admin.profile.recovery-codes.generate'),
                'trusted_device_delete' => route('admin.profile.trusted-device.revoke', ':id'),
                'trusted_device_delete_all' => route('admin.profile.trusted-device.revoke-all'),
            ]"
            :csrfToken="csrf_token()"
        />
    @endif

    {{-- セッションベースのモーダル --}}
    @if(session('auto_generated_recovery_codes'))
        @include('two-fa.partials.recovery-codes-modal', [
            'modalId' => 'profileAutoGeneratedRecoveryCodesModal',
            'title' => __('two_fa.recovery_codes.auto_generated_title'),
            'codes' => session('auto_generated_recovery_codes'),
            'isAutoGenerated' => true,
            'autoOpen' => true
        ])
    @endif

    @if(session('recovery_code_error'))
        @include('two-fa.partials.recovery-codes-modal', [
            'modalId' => 'recoveryCodeErrorModal',
            'error' => session('recovery_code_error'),
            'autoOpen' => true
        ])
    @endif

@endsection

@section('save')
    <x-save
        id_confirmation="confirmProfileModal"
        :label="__('common.update')"
        :title="__('admin/profile.confirm_title')"
        :message="__('admin/profile.confirm_message')"
        :confirm_label="__('common.update')"
        :cancel_label="__('common.cancel')"
        form="profile-form"
    />
@endsection

@push('scripts')
<!-- プロフィールページ専用のフォーム要素トランジション -->
<style>
    #profile-form input, 
    #profile-form textarea, 
    #profile-form select, 
    #profile-form button, 
    #profile-form fieldset, 
    #profile-form legend {
        transition: border-color var(--transition-duration) ease-in-out,
                   box-shadow var(--transition-duration) ease-in-out,
                   background-color var(--transition-duration) ease-in-out,
                   color var(--transition-duration) ease-in-out;
    }
</style>

<script @cspNonce>
    // === グローバル関数はコンポーネントで定義されています ===
    // openDeleteTrustedDeviceModal, revokeTrustedDevice, revokeAllTrustedDevices
    // openDeletePasskeyModal, revokePasskey, revokeAllPasskeys
    // registerPasskey, confirmGenerateRecoveryCodes

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
