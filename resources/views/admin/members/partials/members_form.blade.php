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
    'enabledTwoFaMethods' => [],
    'defaultTwoFaMethod' => null,
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
                :required="true"
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
            <x-email_input
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
            <x-password_tools
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
        <fieldset>
            <legend>{{ __('admin/members/form.account_verification') }}</legend>
            

            
            @if(!isset($member) || !$member->exists)
                {{-- 新規作成時 --}}
                @php
                    $emailVerifiedValue = old('email_verified', $isMailServerTested ? '0' : '1');
                    $emailVerificationOptions = [
                        ['value' => '0', 'label' => 'admin/members/form.account_verified_send_email'],
                        ['value' => '1', 'label' => 'admin/members/form.account_verified'],
                    ];
                @endphp
                <x-form.radio_card_group
                    name="email_verified"
                    :options="$emailVerificationOptions"
                    :value="$emailVerifiedValue"
                    :columns="2"
                    :disabled="!$isMailServerTested"
                />
                <p class="description-text">{{ __('admin/members/form.account_verification_help_create') }}</p>
            @else
                {{-- 編集時 --}}
                @php
                    $emailVerifiedValue = old('email_verified', $member->hasVerifiedEmail() ? '1' : '0');
                    $emailVerificationOptionsEdit = [
                        ['value' => '0', 'label' => 'admin/members/form.account_unverified'],
                        ['value' => '1', 'label' => 'admin/members/form.account_verified'],
                    ];
                @endphp
                <x-form.radio_card_group
                    name="email_verified"
                    :options="$emailVerificationOptionsEdit"
                    :value="$emailVerifiedValue"
                    :columns="2"
                    :disabled="!$isMailServerTested"
                />
                <p class="description-text">{{ __('admin/members/form.account_verification_help_edit') }}</p>
                
                {{-- 認証メール送信ボタン（編集時のみ） --}}
                <div class="my-4">
                    @if($isMailServerTested)
                        <x-form.button
                            type="button"
                            variant="secondary"
                            size="sm"
                            :label="__('admin/members/form.send_verification_email_button')"
                            icon="fas fa-envelope"
                            id="send-verification-email-btn"
                            onclick="sendVerificationEmail({{ $member->id }})"
                        />
                    @else
                        <x-form.button
                            type="button"
                            variant="secondary"
                            size="sm"
                            :label="__('admin/members/form.send_verification_email_button')"
                            icon="fas fa-envelope"
                            id="send-verification-email-btn"
                            :disabled="true"
                        />
                    @endif
                </div>
                @if(!$isMailServerTested)
                    <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        {{ __('admin/members/form.mail_server_not_tested') }}
                    </p>
                @endif
            @endif

            @if(!$isMailServerTested)
                <x-message
                    type="info"
                    :message="__('admin/members/form.account_verification_disabled')"
                />
            @endif
            
            <x-form.error
                :messages="$errors->get('email_verified')"
            />
        </fieldset>
        
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
            <x-appearance_mode_selector
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
        <fieldset>
            <legend>
                {{ __('admin/members/create.account_status') }}
                @if($isInitialAdmin)
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">（初期管理者のため変更不可）</span>
                @endif
            </legend>
            
            @if($isInitialAdmin)
                <input type="hidden" name="status" value="1">
                <p class="description-text">{{ __('admin/members/form.initial_admin_status_fixed') }}</p>
            @else
                @php
                    $statusValue = old('status', (string) ($member->status->value ?? 1));
                    $statusOptions = [
                        ['value' => '1', 'label' => 'components.status.active', 'icon' => 'fas fa-check-circle', 'color' => 'green'],
                        ['value' => '0', 'label' => 'components.status.inactive', 'icon' => 'fas fa-times-circle', 'color' => 'gray'],
                    ];
                @endphp
                <x-form.radio_card_group
                    name="status"
                    :options="$statusOptions"
                    :value="$statusValue"
                    :columns="2"
                />
            @endif
            <x-form.error
                :messages="$errors->get('status')"
            />
        </fieldset>

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
                <x-form.radio_card_group
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
        
        <x-login_notification_selector
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
            $passkeyGloballyEnabled = in_array(\App\Enums\TwoFaMethod::PASSKEY->value, array_keys($enabledTwoFaMethods ?? []));
            $initialPasskeyEnabled = old('two_fa_passkey_enabled', $member->two_fa_passkey_enabled ?? true);
        @endphp
        
        <x-two_fa_auth_selector
            name="two_fa_mode"
            :value="old('two_fa_mode', (string)$currentTwoFaMode)"
            :globalSetting="$force2fa"
            :excludeUseProfileSetting="true"
            :passkeyGloballyEnabled="$passkeyGloballyEnabled"
            :passkeyEnabled="$initialPasskeyEnabled"
            :defaultTwoFaMethod="(string)($member->default_two_fa_method ?? $defaultTwoFaMethod)"
            :columns="3"
            :globalSettingsUrl="route('admin.members.settings.auth')"
        />
    </section>



@if(isset($member) && $member->exists)
    <!-- 2FA管理セクション -->
    <section class="mt-8">
        <h2>{{ __('admin/profile.2fa_management') }}</h2>
        
        <div class="mb-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                <i class="fas fa-info-circle mr-2"></i>
                {{ __('admin/members/form.2fa_management_admin_note') }}
            </p>
        </div>
        
        <x-two_fa_management
            :passkeyEnabled="$passkeyEnabled"
            :passkeyDevices="$passkeyDevices"
            :hasRecoveryCodes="$hasRecoveryCodes"
            :recoveryCodesCount="$recoveryCodesCount"
            :trustedDevices="collect()"
            :showTrustedDevices="false"
            :hideAddButtons="true"
            :hideGenerateButton="true"
            :adminContext="true"
            :routes="[
                'passkey_delete' => url('admin/members/passkey/' . $member->id . '/:id'),
                'passkey_delete_all' => url('admin/members/passkey/' . $member->id . '/revoke-all'),
                'recovery_codes_delete' => url('admin/members/recovery-codes/' . $member->id . '/revoke'),
            ]"
            :csrfToken="csrf_token()"
        />
    </section>

    <!-- 管理操作セクション -->
    <section>
        <h2>{{ __('common.management_operations') }}</h2>

        <fieldset>
            <legend>{{ __('admin/members/form.unlock_lockout') }}</legend>
            <p class="mb-4">{{ __('admin/members/form.unlock_lockout_description') }}</p>
            <x-form.button
                variant="info"
                icon="fas fa-unlock"
                :label="__('admin/members/form.unlock_lockout_button')"
                onclick="openModal('unlockLockoutModal')"
            />
        </fieldset>
        
        <fieldset>
            <legend>{{ __('admin/members/form.force_logout') }}</legend>
            <p class="mb-4">{{ __('admin/members/form.force_logout_description') }}</p>
            <x-form.button
                variant="warning"
                icon="fas fa-sign-out-alt"
                :label="__('admin/members/form.force_logout_button')"
                onclick="openModal('forceLogoutModal')"
            />
        </fieldset>

        @if(!$isInitialAdmin)
            <fieldset>
                <legend>{{ __('admin/members/form.delete_member') }}</legend>
                <p class="mb-4">{{ __('admin/members/form.delete_member_description') }}</p>
                <x-form.button
                    variant="danger"
                    icon="fas fa-trash"
                    :label="__('admin/members/form.delete_member_button')"
                    onclick="openModal('deleteMemberModal')"
                />
            </fieldset>
        @endif
    </section>
@endif

@if(isset($member) && $member->exists)
    <!-- 隠しフォーム -->
    <form id="forceLogoutForm-{{ $member->id }}" method="POST" action="{{ route('admin.members.force-logout', $member->id) }}" style="display: none;">
        @csrf
    </form>

    <form id="unlockLockoutForm-{{ $member->id }}" method="POST" action="{{ route('admin.members.unlock-2fa', $member->id) }}" style="display: none;">
        @csrf
    </form>

    @if(!$isInitialAdmin)
        <form id="deleteMemberForm-{{ $member->id }}" method="POST" action="{{ route('admin.members.destroy', $member->id) }}" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endif

    <!-- モーダル -->
    @php
        $forceLogoutFormId = 'forceLogoutForm-' . $member->id;
        $unlockLockoutFormId = 'unlockLockoutForm-' . $member->id;
        $deleteMemberFormId = 'deleteMemberForm-' . $member->id;
    @endphp
    
    @php
        $forceLogoutModalTitle = __('admin/members/edit.modals.force_logout.title');
        $forceLogoutModalMessage = __('admin/members/edit.modals.force_logout.message', ['name' => $member->display_name ?? $member->account_name]);
        $forceLogoutConfirmLabel = __('admin/members/edit.modals.force_logout.confirm');
        $forceLogoutCancelLabel = __('common.cancel');
    @endphp
    <x-modal
        id="forceLogoutModal"
        :title="$forceLogoutModalTitle"
        :message="$forceLogoutModalMessage"
        :confirm_label="$forceLogoutConfirmLabel"
        :cancel_label="$forceLogoutCancelLabel"
        :form="$forceLogoutFormId"
        icon_type="warning"
        confirm_color="yellow"
    />

    @php
        $unlockModalTitle = __('admin/members/edit.modals.unlock_lockout.title');
        $unlockModalMessage = __('admin/members/edit.modals.unlock_lockout.message', ['name' => $member->display_name ?? $member->account_name]);
        $unlockConfirmLabel = __('admin/members/edit.modals.unlock_lockout.confirm');
        $unlockCancelLabel = __('common.cancel');
    @endphp
    <x-modal
        id="unlockLockoutModal"
        :title="$unlockModalTitle"
        :message="$unlockModalMessage"
        :confirm_label="$unlockConfirmLabel"
        :cancel_label="$unlockCancelLabel"
        :form="$unlockLockoutFormId"
        icon_type="info"
        confirm_color="blue"
    />

    @if(!$isInitialAdmin)
        @php
            $deleteModalTitle = __('admin/members/edit.modals.delete.title');
            $deleteModalMessage = __('admin/members/edit.modals.delete.message', ['name' => $member->display_name ?? $member->account_name]) . "\n\n" . __('admin/members/edit.modals.delete.warning');
            $deleteConfirmLabel = __('common.delete');
            $deleteCancelLabel = __('common.cancel');
        @endphp
        <x-modal
            id="deleteMemberModal"
            :title="$deleteModalTitle"
            :message="$deleteModalMessage"
            :confirm_label="$deleteConfirmLabel"
            :cancel_label="$deleteCancelLabel"
            :form="$deleteMemberFormId"
            icon_type="danger"
            confirm_color="red"
        />
    @endif
@endif

@if($includeForm && $formAction)
    </form>
@endif