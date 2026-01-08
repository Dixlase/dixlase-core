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
            />
            <p class="description-text">{{ __('admin/members/form.display_name_help') }}</p>
            <x-form.error
                :messages="$errors->get('display_name')"
            />
        </fieldset>

        <fieldset>
            <legend>{{ __('common.description') }}</legend>
            <textarea name="description" id="description" rows="3" 
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">{{ old('description', $member->description ?? '') }}</textarea>
            <x-form.error
                :messages="$errors->get('description')"
            />
        </fieldset>

        <fieldset>
            <legend>{{ __('common.email') }}</legend>
            <x-form.text
                type="email"
                id="email"
                name="email"
                :value="old('email', $member->email ?? '')"
                :required="true"
                autocomplete="email"
            />
            <x-form.error
                :messages="$errors->get('email')"
            />
        </fieldset>

        {{-- メールアドレス確認フィールド（新規作成時 or 編集時にメールアドレス変更） --}}
        <fieldset id="email-confirmation-field" style="display: none;">
            <legend>{{ __('admin.members.form.email_confirmation') }}</legend>
            <x-form.text
                type="email"
                id="email_confirmation"
                name="email_confirmation"
                :value="old('email_confirmation')"
                :required="false"
                autocomplete="off"
                onpaste="return false"
                oncopy="return false"
                oncut="return false"
            />
            <p class="help-text">{{ __('admin.members.form.email_confirmation_help') }}</p>
            <x-form.error
                :messages="$errors->get('email_confirmation')"
            />
        </fieldset>
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
                :requireSymbol="$passwordRequireSymbol"
                :showConfirmation="true"
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
            <legend>{{ __('admin.members.form.account_verification') }}</legend>
            
            @if(!$isMailServerTested)
                <x-message
                    type="info"
                    :message="__('admin.members.form.account_verification_disabled')"
                />
                <input type="hidden" name="email_verified" value="1">
            @else
                @if(!isset($member) || !$member->exists)
                    {{-- 新規作成時 --}}
                    @php
                        $emailVerifiedValue = old('email_verified', '0');
                        $emailVerificationOptions = [
                            '0' => 'admin.members.form.account_verified_send_email',
                            '1' => 'admin.members.form.account_verified',
                        ];
                    @endphp
                    <x-form.radio-group
                        name="email_verified"
                        :options="$emailVerificationOptions"
                        :value="$emailVerifiedValue"
                    />
                    <p class="description-text">{{ __('admin.members.form.account_verification_help_create') }}</p>
                @else
                    {{-- 編集時 --}}
                    @php
                        $emailVerifiedValue = old('email_verified', $member->hasVerifiedEmail() ? '1' : '0');
                        $emailVerificationOptionsEdit = [
                            '0' => 'admin.members.form.account_unverified',
                            '1' => 'admin.members.form.account_verified',
                        ];
                    @endphp
                    <x-form.radio-group
                        name="email_verified"
                        :options="$emailVerificationOptionsEdit"
                        :value="$emailVerifiedValue"
                    />
                    <p class="description-text">{{ __('admin.members.form.account_verification_help_edit') }}</p>
                    
                    {{-- 認証メール送信ボタン --}}
                    <div class="mt-4">
                        @if($isMailServerTested)
                            <x-form.button
                                type="button"
                                variant="secondary"
                                size="sm"
                                :label="__('admin.members.form.send_verification_email_button')"
                                icon="fas fa-envelope"
                                id="send-verification-email-btn"
                                onclick="sendVerificationEmail({{ $member->id }})"
                            />
                        @else
                            <x-form.button
                                type="button"
                                variant="secondary"
                                size="sm"
                                :label="__('admin.members.form.send_verification_email_button')"
                                icon="fas fa-envelope"
                                :disabled="true"
                            />
                            <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                {{ __('admin.members.form.mail_server_not_tested') }}
                            </p>
                        @endif
                    </div>
                @endif
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
                $appearanceOptions = [
                    '0' => 'common.auto',
                    '1' => 'common.light',
                    '2' => 'common.dark',
                ];
            @endphp
            <x-form.radio-group
                name="appearance"
                :options="$appearanceOptions"
                :value="$appearanceValue"
            />
            <x-form.error
                :messages="$errors->get('appearance')"
            />
        </fieldset>

        <!-- アカウントステータス -->
        <fieldset>
            <legend>
                {{ __('admin.members.create.account_status') }}
                @if($isInitialAdmin)
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">（初期管理者のため変更不可）</span>
                @endif
            </legend>
            
            @if($isInitialAdmin)
                <input type="hidden" name="status" value="1">
                <p class="description-text">{{ __('admin.members.form.initial_admin_status_fixed') }}</p>
            @else
                @php
                    $statusValue = old('status', (string) ($member->status->value ?? 1));
                    $statusOptions = [
                        '1' => 'components.status.active',
                        '0' => 'components.status.inactive',
                    ];
                @endphp
                <x-form.radio-group
                    name="status"
                    :options="$statusOptions"
                    :value="$statusValue"
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
                <p class="description-text">{{ __('admin.members.form.initial_admin_role_fixed') }}</p>
            @else
                @php
                    $roleValue = old('role', $member->role->value ?? $roleAdminValue);
                    $roleOptions = [];
                    foreach ($roles as $role) {
                        $roleOptions[$role->value] = $role->label();
                    }
                @endphp
                <x-form.radio-group
                    name="role"
                    :options="$roleOptions"
                    :value="$roleValue"
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
                :message="__('admin.members.form.mail_server_not_tested')"
            />
        @endif
        
        <!-- ログイン通知設定 -->
        @if($loginNotificationMode === $loginNotificationUseProfileSettingValue)
            <fieldset>
                <legend>{{ __('common.login_notification') }}</legend>
                @php
                    $loginNotificationValue = old('login_notification', (string) ($member->login_notification->value ?? 0));
                    $loginNotificationOptions = [
                        '0' => 'common.disabled',
                        '1' => 'common.enabled',
                    ];
                @endphp
                <x-form.radio-group
                    name="login_notification"
                    :options="$loginNotificationOptions"
                    :value="$loginNotificationValue"
                />
                <x-form.error
                    :messages="$errors->get('login_notification')"
                />
            </fieldset>
        @else
            <fieldset>
                <p class="description-text">{!! __('admin.members.form.login_notification_global_fixed', ['setting' => $loginNotificationModeLabel]) !!}</p>
            </fieldset>
        @endif
    </section>

    <!-- 二段階認証設定セクション -->
    <section>
        <h2>{{ __('common.two_fa_settings') }}</h2>
        @if(!$isMailServerTested)
            <x-message
                type="warning"
                :message="__('admin.members.form.mail_server_not_tested')"
            />
        @endif   
        
        @if($force2fa === $twoFaUseProfileSettingValue)
            <!-- 二段階認証有効/無効 -->
            <fieldset>
                <legend>{{ __('common.two_fa_authentication') }}</legend>
                @php
                    $twoFaModeValue = old('two_fa_mode', $member->two_fa_mode->value ?? $twoFaMode->value);
                @endphp
                <x-form.radio-group
                    name="two_fa_mode"
                    :options="$twoFaModeOptions"
                    :value="$twoFaModeValue"
                />
                <x-form.error
                    :messages="$errors->get('two_fa_mode')"
                />
            </fieldset>
            
            <!-- 二段階認証方法設定 -->
            @if(!empty($enabledTwoFaMethods))
                <fieldset>
                <legend>
                    {{ __('common.two_fa_method.label') }}
                    @if(count($enabledTwoFaMethods) > 1)
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                            ({{ count($enabledTwoFaMethods) }} {{ __('common.available_methods') }})
                        </span>
                    @endif
                </legend>

                @php
                    $currentTwoFaMethod = old('two_fa_method', $member?->two_fa_method ?? $defaultTwoFaMethod);
                    $currentMethodValid = array_key_exists($currentTwoFaMethod, $enabledTwoFaMethods);
                    $defaultMethod = $defaultTwoFaMethod ?? array_key_first($enabledTwoFaMethods);
                    $currentMethod = $currentMethodValid ? $currentTwoFaMethod : $defaultMethod;
                @endphp

                @if(count($enabledTwoFaMethods) > 1)
                    <x-form.radio-group
                        name="two_fa_method"
                        :options="$enabledTwoFaMethods"
                        :value="$currentMethod"
                    />
                @else
                    <input type="hidden" name="two_fa_method" value="{{ $currentMethod }}">
                    <p class="description-text">
                        {{ __('admin/profile.single_method_available') }}: 
                        <strong>{{ __($enabledTwoFaMethods[$currentMethod]) }}</strong>
                    </p>
                @endif
                
                <x-form.error
                    :messages="$errors->get('two_fa_method')"
                />
            </fieldset>
        @endif
        @else
            <fieldset>
                <p class="description-text">{!! __('admin.members.form.two_fa_global_fixed', ['setting' => $twoFaModeLabel]) !!}</p>
            </fieldset>
        @endif
    </section>



<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const emailConfirmationField = document.getElementById('email-confirmation-field');
    const emailConfirmationInput = document.getElementById('email_confirmation');
    
    @if(!isset($member) || !$member->exists)
        // 新規作成時は常に表示
        emailConfirmationField.style.display = 'block';
        emailConfirmationInput.required = true;
    @else
        // 編集時は元のメールアドレスを保存
        const originalEmail = '{{ $member->email ?? '' }}';
        
        // バリデーションエラーがある場合、または old値がある場合は初期表示
        @if($errors->has('email_confirmation') || old('email_confirmation'))
            emailConfirmationField.style.display = 'block';
            emailConfirmationInput.required = true;
        @endif
        
        // メールアドレスの変更を監視
        emailInput.addEventListener('input', function() {
            if (this.value !== originalEmail && this.value !== '') {
                // メールアドレスが変更された場合は確認フィールドを表示
                emailConfirmationField.style.display = 'block';
                emailConfirmationInput.required = true;
            } else {
                // 元に戻した場合は確認フィールドを非表示
                emailConfirmationField.style.display = 'none';
                emailConfirmationInput.required = false;
                emailConfirmationInput.value = '';
            }
        });
    @endif
});
</script>

@if(isset($member) && $member->exists)
    <!-- 2FA管理セクション -->
    <section class="mt-8">
        <h2>{{ __('admin/profile.2fa_management') }}</h2>

        <!-- Passkeyデバイス -->
        @if($passkeyEnabled)
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Passkeyデバイス</h3>
                @if(!$passkeyDevices->isEmpty())
                    <x-form.button
                        type="button"
                        variant="danger"
                        size="sm"
                        label="全て削除"
                        icon="fas fa-trash-alt"
                        onclick="openModal('deleteAllPasskeysModal')"
                    />
                @endif
            </div>
            
            @if($passkeyDevices->isEmpty())
                <p class="text-gray-600 dark:text-gray-400 mb-4">Passkeyデバイスが登録されていません</p>
            @else
                <div class="space-y-4 mb-4">
                    @foreach($passkeyDevices as $device)
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-key text-green-600 dark:text-green-400 mr-2"></i>
                                    <h4 class="font-semibold">{{ $device->name }}</h4>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p><strong>登録日時:</strong> {{ $device->created_at->format('Y-m-d H:i') }}</p>
                                    @if($device->last_used_at)
                                        <p><strong>最終使用:</strong> {{ $device->last_used_at->format('Y-m-d H:i') }}</p>
                                    @endif
                                </div>
                            </div>
                            <x-form.button
                                type="button"
                                variant="danger"
                                size="sm"
                                label="削除"
                                onclick="openDeletePasskeyModal('{{ $device->id }}', '{{ $device->name }}')"
                                class="ml-4"
                            />
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Passkeyの説明 -->
            <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>{{ __('admin/profile.passkey_info_title') }}
                </h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                    <li>{{ __('admin/profile.passkey_info_1') }}</li>
                    <li>{{ __('admin/profile.passkey_info_2') }}</li>
                    <li>{{ __('admin/profile.passkey_info_3') }}</li>
                    <li class="text-red-600 dark:text-red-400 font-semibold">{{ __('admin/profile.passkey_info_4') }}</li>
                </ul>
            </div>
        </div>
        @endif

        <!-- 回復コード -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ __('two_fa.recovery_codes.title') }}</h3>
            </div>

            @if($hasRecoveryCodes)
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <i class="fas fa-info-circle mr-2"></i>
                            {{ __('two_fa.recovery_codes.remaining', ['count' => $recoveryCodesCount]) }}
                        </p>
                        <x-form.button
                            type="button"
                            variant="danger"
                            size="sm"
                            :label="__('admin.members.form.delete_recovery_codes')"
                            icon="fas fa-trash"
                            onclick="openModal('deleteRecoveryCodesModal')"
                        />
                    </div>
                </div>
            @else
                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('two_fa.recovery_codes.not_generated') }}</p>
            @endif

            <!-- 回復コードの説明 -->
            <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>{{ __('admin/profile.recovery_codes_info_title') }}
                </h4>
                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc list-inside">
                    <li>{{ __('admin/profile.recovery_codes_info_1') }}</li>
                    <li>{{ __('admin/profile.recovery_codes_info_2') }}</li>
                    <li>{{ __('admin/profile.recovery_codes_info_3') }}</li>
                    <li class="text-red-600 dark:text-red-400 font-semibold">{{ __('admin.members.form.recovery_codes_admin_note') }}</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- 管理操作セクション -->
    <section>
        <h2>{{ __('common.management_operations') }}</h2>

        <fieldset>
            <legend>{{ __('admin.members.form.unlock_lockout') }}</legend>
            <p class="mb-4">{{ __('admin.members.form.unlock_lockout_description') }}</p>
            <x-form.button
                variant="info"
                icon="fas fa-unlock"
                :label="__('admin.members.form.unlock_lockout_button')"
                onclick="openModal('unlockLockoutModal')"
            />
        </fieldset>
        
        <fieldset>
            <legend>{{ __('admin.members.form.force_logout') }}</legend>
            <p class="mb-4">{{ __('admin.members.form.force_logout_description') }}</p>
            <x-form.button
                variant="warning"
                icon="fas fa-sign-out-alt"
                :label="__('admin.members.form.force_logout_button')"
                onclick="openModal('forceLogoutModal')"
            />
        </fieldset>

        @if(!$isInitialAdmin)
            <fieldset>
                <legend>{{ __('admin.members.form.delete_member') }}</legend>
                <p class="mb-4">{{ __('admin.members.form.delete_member_description') }}</p>
                <x-form.button
                    variant="danger"
                    icon="fas fa-trash"
                    :label="__('admin.members.form.delete_member_button')"
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
    
    <x-modal
        id="forceLogoutModal"
        :title="__('admin.members.modals.force_logout.title')"
        :message="__('admin.members.modals.force_logout.message', ['name' => $member->display_name ?? $member->account_name])"
        :confirm_label="__('admin.members.modals.force_logout.confirm')"
        :cancel_label="__('common.cancel')"
        :form="$forceLogoutFormId"
        icon_type="warning"
        confirm_color="yellow"
    />

    <x-modal
        id="unlockLockoutModal"
        :title="__('admin.members.modals.unlock_lockout.title')"
        :message="__('admin.members.modals.unlock_lockout.message', ['name' => $member->display_name ?? $member->account_name])"
        :confirm_label="__('admin.members.modals.unlock_lockout.confirm')"
        :cancel_label="__('common.cancel')"
        :form="$unlockLockoutFormId"
        icon_type="info"
        confirm_color="blue"
    />

    @if(!$isInitialAdmin)
        <x-modal
            id="deleteMemberModal"
            :title="__('admin.members.modals.delete.title')"
            :message="__('admin.members.modals.delete.message', ['name' => $member->display_name ?? $member->account_name]) . "\n\n" . __('admin.members.modals.delete.warning')"
            :confirm_label="__('common.delete')"
            :cancel_label="__('common.cancel')"
            :form="$deleteMemberFormId"
            icon_type="danger"
            confirm_color="red"
        />
    @endif
@endif

@if($includeForm && $formAction)
    </form>
@endif