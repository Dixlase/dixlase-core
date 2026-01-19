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

@props([
    'member' => null,
    'requirePassword' => false,
    'passwordMinLength' => 8,
    'passwordRequireUppercase' => false,
    'passwordRequireLowercase' => true,
    'passwordRequireNumber' => true,
    'passwordRequireSymbol' => false,
    'roles' => [],
    'twoFaMode' => null,
    'twoFaEnabledMethods' => [],
    'twoFaDefaultMethod' => null,
    'isInitialAdmin' => false,
    'isMailServerTested' => false,
    'formAction' => null,        // フォームのaction URL
    'formMethod' => 'POST',      // フォームのメソッド
    'formId' => 'member-form',   // フォームのID
    'includeForm' => true        // フォームタグを含めるかどうか
])

@if($includeForm && $formAction)
    <form id="{{ $formId }}" action="{{ $formAction }}" method="POST" class="mb-10">
        @csrf
        @if($formMethod === 'PATCH' || $formMethod === 'PUT')
            @method($formMethod)
        @endif
        @if(isset($member) && $member->id)
            <x-form.hidden
                name="id"
                :value="$member->id"
            />
        @endif
@endif

    <!-- 基本情報セクション -->
    <section>
        <h2>{{ __('common.basic_info') }}</h2>
        
        <fieldset>
            <legend>{{ __('common.account_name') }}</legend>
            <x-form.text
                id="account_name"
                name="account_name"
                :value="old('account_name', $member->account_name ?? '')"
                pattern="^[a-zA-Z0-9]+$"
                minlength="3"
                maxlength="20"
                class="w-full"
            />
            <p class="description-text">{{ __('admin/members/form.account_name_help') }}</p>
            <x-form.error
                :messages="$errors->get('account_name')"
            />
        </fieldset>

        <fieldset>
            <legend>{{ __('common.display_name') }}</legend>
            <x-form.text
                id="display_name"
                name="display_name"
                :value="old('display_name', $member->display_name ?? '')"
                maxlength="255"
                class="w-full"
            />
            <p class="description-text">{{ __('admin/members/form.display_name_help') }}</p>
            <x-form.error
                :messages="$errors->get('display_name')"
            />
        </fieldset>

        <fieldset>
            <legend>{{ __('common.description') }}</legend>
            <x-form.textarea
                id="description"
                name="description"
                :value="old('description', $member->description ?? '')"
                rows="3"
                class="w-full"
            />
            <x-form.error
                :messages="$errors->get('description')"
            />
        </fieldset>

        <div id="email-input-wrapper">
            <x-email-input
                id="email"
                name="email"
                :value="old('email', $member->email ?? '')"
                :required="false"
                :showConfirmation="true"
                :showConfirmationOnChange="isset($member) && $member->exists"
            />
            <x-form.error
                :messages="$errors->get('email')"
            />
            <div id="email-confirmation-wrapper" style="display: none;">
                <x-form.error
                    :messages="$errors->get('email_confirmation')"
                />
            </div>
        </div>
    </section>

    <!-- パスワード設定セクション -->
    <section>
        <h2>{{ __('common.password_settings') }}</h2>
        
        <fieldset>
            <legend>{{ $requirePassword ? __('common.password') : __('admin/profile.password_change_only') }}</legend>
            <x-password-tools
                id="password"
                name="password"
                :required="$requirePassword"
                :minLength="$passwordMinLength"
                :requireUppercase="$passwordRequireUppercase"
                :requireLowercase="$passwordRequireLowercase"
                :requireNumber="$passwordRequireNumber"
                :requireSymbol="$passwordRequireSymbol"
                :showConfirmation="true"
                :showConfirmationOnChange="isset($member) && $member->exists"
            />
            <x-form.error
                :messages="$errors->get('password')"
            />
        </fieldset>
    </section>

    <!-- アカウント設定セクション -->
    <section>
        <h2>{{ __('common.account_settings') }}</h2>
        
        <!-- アカウント認証設定 -->
        <x-auth.account-verification
            :entity="$member ?? null"
            entityType="member"
            :sendRoute="route('admin.members.send-verification-email', ['member' => ':id'])"
            :isMailServerTested="$isMailServerTested"
            :isEdit="isset($member) && $member->exists"
            :errors="$errors"
        />
        
        <!-- 言語設定 -->
        <fieldset>
            <legend>{{ __('common.locale') }}</legend>
            <x-form.select
                name="locale"
                :options="$localeOptions"
                :value="old('locale', $member->locale?->value ?? null)"
                :nullable="true"
                :nullLabel="__('admin/profile.use_system_default')"
            />
            <x-form.error
                :messages="$errors->get('locale')"
            />
            <p class="description-text">{{ __('admin/profile.language_help') }}</p>
        </fieldset>
        
        <!-- 外観モード -->
        <fieldset data-member-theme>
            <legend>{{ __('common.appearance_mode') }}</legend>
            @php
                $appearanceValue = old('appearance', (string) ($member->appearance->value ?? 0));
            @endphp
            <x-appearance-mode-selector
                name="appearance"
                :value="$appearanceValue"
                :enableRealtimeSwitch="false"
                :columns="3"
            />
            <x-form.error
                :messages="$errors->get('appearance')"
            />
        </fieldset>

        <!-- アカウントステータス -->
        <x-account-status
            :entity="$member ?? null"
            entityType="member"
            :isInitialAdmin="$isInitialAdmin"
            :errors="$errors"
        />

        <!-- 管理者ロール -->
        <fieldset>
            <legend>
                {{ __('common.role') }}
                @if($isInitialAdmin)
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">（初期管理者のため変更不可）</span>
                @endif
            </legend>
            
            @if($isInitialAdmin)
                <input type="hidden" name="role" value="{{ $roleSuperAdminValue }}">
                <p class="description-text">{{ __('admin/members/form.initial_admin_role_fixed') }}</p>
            @else
                @php
                    $roleValue = old('role', $member->role->value ?? $roleAdminValue);
                    $roleOptions = [];
                    foreach ($roles as $role) {
                        $roleOptions[] = [
                            'value' => $role->value,
                            'label' => $role->label(),
                            'icon' => 'fas fa-user-shield',
                        ];
                    }
                @endphp
                <x-form.radio-card-group
                    name="role"
                    :options="$roleOptions"
                    :value="$roleValue"
                    :columns="4"
                />
            @endif
            <x-form.error
                :messages="$errors->get('role')"
            />
        </fieldset>
    </section>

    <!-- 通知設定セクション -->
    <section>
        <h2>{{ __('common.notification_settings') }}</h2>
        @if(!$isMailServerTested)
            <x-message
                type="warning"
                :message="__('admin/members/form.mail_server_not_tested')"
            />
        @endif
        
        @php
            $currentLoginNotification = $member->login_notification_mode ?? \App\Enums\AuthenticationMode::Always->value;
            if ($currentLoginNotification instanceof \App\Enums\AuthenticationMode) {
                $currentLoginNotification = $currentLoginNotification->value;
            }
        @endphp
        
        <x-login-notification-selector
            name="login_notification_mode"
            :value="old('login_notification_mode', (string)$currentLoginNotification)"
            :globalSetting="$loginNotificationMode"
            :excludeUseProfileSetting="true"
            :columns="3"
        />
    </section>

    <!-- 二段階認証設定セクション -->
    <section>
        <h2>{{ __('auth.two_fa_settings') }}</h2>
        @if(!$isMailServerTested)
            <x-message
                type="warning"
                :message="__('admin/members/form.mail_server_not_tested')"
            />
        @endif
        
        @php
            $currentTwoFaMode = $member->two_fa_mode ?? \App\Enums\AuthenticationMode::Always->value;
            if ($currentTwoFaMode instanceof \App\Enums\AuthenticationMode) {
                $currentTwoFaMode = $currentTwoFaMode->value;
            }
            $twoFaPasskeyGloballyEnabled = in_array(\App\Enums\TwoFaMethod::PASSKEY->value, array_keys($twoFaEnabledMethods ?? []));
            $initialPasskeyEnabled = old('two_fa_passkey_enabled', $member->two_fa_passkey_enabled ?? true);
        @endphp
        
        <x-two-fa.individual-auth-selector
            name="two_fa_mode"
            :value="old('two_fa_mode', (string)$currentTwoFaMode)"
            :globalSetting="$forceTwoFa"
            :excludeUseProfileSetting="true"
            :twoFaPasskeyGloballyEnabled="$twoFaPasskeyGloballyEnabled"
            :twoFaPasskeyEnabled="$initialPasskeyEnabled"
            :twoFaPasskeyMode="(string)($twoFaPasskeyMode ?? '2')"
            :twoFaDefaultMethod="(string)($member->default_two_fa_method ?? $twoFaDefaultMethod)"
            :columns="3"
            :globalSettingsUrl="route('admin.members.settings.auth')"
        />
    </section>



@if(isset($member) && $member->exists)
    <!-- 2FA管理セクション -->
    <section class="mt-8">
        <h2>{{ __('admin/profile.two_fa_management') }}</h2>
        
        <div class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                <i class="fas fa-info-circle mr-2"></i>
                {{ __('admin/members/form.two_fa_management_admin_note') }}
            </p>
        </div>
        
        <x-two-fa.management
            :twoFaPasskeyEnabled="$twoFaPasskeyEnabled"
            :twoFaPasskeyDevices="$twoFaPasskeyDevices"
            :twoFaHasRecoveryCodes="$twoFaHasRecoveryCodes"
            :twoFaRecoveryCodesCount="$twoFaRecoveryCodesCount"
            :twoFaTrustedDevices="collect()"
            :twoFaShowTrustedDevices="false"
            :hideAddButtons="true"
            :hideGenerateButton="true"
            :adminContext="true"
            :routes="[
                'passkey_delete' => url('admin/members/passkey/' . $member->id . '/:id'),
                'passkey_delete_all' => url('admin/members/passkey/' . $member->id . '/all'),
                'recovery_codes_delete' => url('admin/members/recovery-codes/' . $member->id),
            ]"
            :csrfToken="csrf_token()"
        />
    </section>
@endif

@if($includeForm && $formAction)
    </form>
@endif

@if(isset($member) && $member->exists)
    <!-- 管理操作セクション -->
    <x-danger-zone
        :unlockRoute="route('admin.members.unlock-lockout', ['member' => $member->id])"
        :forceLogoutRoute="route('admin.members.force-logout', ['member' => $member->id])"
        :deleteRoute="!$isInitialAdmin ? route('admin.members.destroy', ['member' => $member->id]) : null"
        :canDelete="!$isInitialAdmin"
        entityType="member"
    />
@endif