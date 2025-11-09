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
    'twoFactorMode' => null,
    'enabledTwoFactorMethods' => [],
    'defaultTwoFactorMethod' => null,
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
            <legend>{{ __('common.name') }}</legend>
            <x-form.text
                id="name"
                name="name"
                :value="old('name', $member->name ?? '')"
                :required="true"
            />
            <x-form.error
                :messages="$errors->get('name')"
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
            <legend>{{ __('admin.settings.members.form.email_confirmation') }}</legend>
            @include('components::form.text', [
                'type' => 'email',
                'id' => 'email_confirmation',
                'name' => 'email_confirmation',
                'value' => old('email_confirmation'),
                'required' => false,
                'autocomplete' => 'off',
                'onpaste' => 'return false',
                'oncopy' => 'return false',
                'oncut' => 'return false',
            ])
            <p class="help-text">{{ __('admin.settings.members.form.email_confirmation_help') }}</p>
            @include('components::form.error', [
                'messages' => $errors->get('email_confirmation')
            ])
        </fieldset>
    </section>

    <!-- パスワード設定セクション -->
    <section>
        <h2>{{ __('common.password_settings') }}</h2>
        
        <fieldset>
            <legend>{{ $requirePassword ? __('common.password') : __('admin.profile.password_change_only') }}</legend>
            @include('components.password-tools', [
                'id' => 'password',
                'name' => 'password',
                'required' => $requirePassword,
                'minLength' => $passwordMinLength,
                'requireUppercase' => $passwordRequireUppercase,
                'requireSymbol' => $passwordRequireSymbol,
                'showConfirmation' => true
            ])
            @include('components::form.error', [
                'messages' => $errors->get('password')
            ])
        </fieldset>
    </section>

    <!-- アカウント設定セクション -->
    <section>
        <h2>{{ __('common.account_settings') }}</h2>
        
        <!-- アカウント認証設定 -->
        <fieldset>
            <legend>{{ __('admin.settings.members.form.account_verification') }}</legend>
            
            @if(!$isMailServerTested)
                @include('components.message', [
                    'type' => 'info',
                    'message' => __('admin.settings.members.form.account_verification_disabled')
                ])
                <input type="hidden" name="email_verified" value="1">
            @else
                @if(!isset($member) || !$member->exists)
                    {{-- 新規作成時 --}}
                    @php
                        $emailVerifiedValue = old('email_verified', '0');
                        $emailVerificationOptions = [
                            '0' => 'admin.settings.members.form.account_verified_send_email',
                            '1' => 'admin.settings.members.form.account_verified',
                        ];
                    @endphp
                    @include('components::form.radio-group', [
                        'name' => 'email_verified',
                        'options' => $emailVerificationOptions,
                        'value' => $emailVerifiedValue
                    ])
                    <p class="description-text">{{ __('admin.settings.members.form.account_verification_help_create') }}</p>
                @else
                    {{-- 編集時 --}}
                    @php
                        $emailVerifiedValue = old('email_verified', $member->hasVerifiedEmail() ? '1' : '0');
                        $emailVerificationOptionsEdit = [
                            '0' => 'admin.settings.members.form.account_unverified',
                            '1' => 'admin.settings.members.form.account_verified',
                        ];
                    @endphp
                    @include('components::form.radio-group', [
                        'name' => 'email_verified',
                        'options' => $emailVerificationOptionsEdit,
                        'value' => $emailVerifiedValue
                    ])
                    <p class="description-text">{{ __('admin.settings.members.form.account_verification_help_edit') }}</p>
                    
                    {{-- 認証メール送信ボタン --}}
                    <div class="mt-4">
                        @if($isMailServerTested)
                            @include('components::form.button', [
                                'type' => 'button',
                                'variant' => 'secondary',
                                'size' => 'sm',
                                'label' => __('admin.settings.members.form.send_verification_email_button'),
                                'icon' => 'fas fa-envelope',
                                'id' => 'send-verification-email-btn',
                                'onclick' => 'sendVerificationEmail(' . $member->id . ')'
                            ])
                        @else
                            @include('components::form.button', [
                                'type' => 'button',
                                'variant' => 'secondary',
                                'size' => 'sm',
                                'label' => __('admin.settings.members.form.send_verification_email_button'),
                                'icon' => 'fas fa-envelope',
                                'disabled' => true
                            ])
                            <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                {{ __('admin.settings.members.form.mail_server_not_tested') }}
                            </p>
                        @endif
                    </div>
                @endif
            @endif
            
            @include('components::form.error', [
                'messages' => $errors->get('email_verified')
            ])
        </fieldset>
        
        <!-- 言語設定 -->
        <fieldset>
            <legend>{{ __('common.locale') }}</legend>
            @include('components.form.select', [
                'name' => 'locale',
                'options' => $localeOptions,
                'value' => old('locale', $member->locale?->value ?? null),
                'nullable' => true,
                'nullLabel' => __('admin.profile.use_system_default')
            ])
            @include('components::form.error', [
                'messages' => $errors->get('locale')
            ])
            <p class="description-text">{{ __('admin.profile.language_help') }}</p>
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
            @include('components::form.radio-group', [
                'name' => 'appearance',
                'options' => $appearanceOptions,
                'value' => $appearanceValue
            ])
            @include('components::form.error', [
                'messages' => $errors->get('appearance')
            ])
        </fieldset>

        <!-- アカウントステータス -->
        <fieldset>
            <legend>
                {{ __('admin.settings.members.create.account_status') }}
                @if($isInitialAdmin)
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">（初期管理者のため変更不可）</span>
                @endif
            </legend>
            
            @if($isInitialAdmin)
                <input type="hidden" name="status" value="1">
                <p class="description-text">{{ __('admin.settings.members.form.initial_admin_status_fixed') }}</p>
            @else
                @php
                    $statusValue = old('status', (string) ($member->status->value ?? 1));
                    $statusOptions = [
                        '1' => 'components.status.active',
                        '0' => 'components.status.inactive',
                    ];
                @endphp
                @include('components::form.radio-group', [
                    'name' => 'status',
                    'options' => $statusOptions,
                    'value' => $statusValue
                ])
            @endif
            @include('components::form.error', [
                'messages' => $errors->get('status')
            ])
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
                <p class="description-text">{{ __('admin.settings.members.form.initial_admin_role_fixed') }}</p>
            @else
                @php
                    $roleValue = old('role', $member->role->value ?? $roleAdminValue);
                    $roleOptions = [];
                    foreach ($roles as $role) {
                        $roleOptions[$role->value] = $role->label();
                    }
                @endphp
                @include('components::form.radio-group', [
                    'name' => 'role',
                    'options' => $roleOptions,
                    'value' => $roleValue
                ])
            @endif
            @include('components::form.error', [
                'messages' => $errors->get('role')
            ])
        </fieldset>
    </section>

    <!-- 通知設定セクション -->
    <section>
        <h2>{{ __('common.notification_settings') }}</h2>
        @if(!$isMailServerTested)
            @include('components.message', [
                'type' => 'warning',
                'message' => __('admin.settings.members.form.mail_server_not_tested')
            ])
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
                @include('components::form.radio-group', [
                    'name' => 'login_notification',
                    'options' => $loginNotificationOptions,
                    'value' => $loginNotificationValue
                ])
                @include('components::form.error', [
                    'messages' => $errors->get('login_notification')
                ])
            </fieldset>
        @else
            <fieldset>
                <p class="description-text">{!! __('admin.settings.members.form.login_notification_global_fixed', ['setting' => $loginNotificationModeLabel]) !!}</p>
            </fieldset>
        @endif
    </section>

    <!-- 二段階認証設定セクション -->
    <section>
        <h2>{{ __('common.two_factor_settings') }}</h2>
        @if(!$isMailServerTested)
            @include('components.message', [
                'type' => 'warning',
                'message' => __('admin.settings.members.form.mail_server_not_tested')
            ])
        @endif   
        
        @if($force2fa === $twoFactorUseProfileSettingValue)
            <!-- 二段階認証有効/無効 -->
            <fieldset>
                <legend>{{ __('common.two_factor_authentication') }}</legend>
                @php
                    $twoFactorModeValue = old('two_factor_mode', $member->two_factor_mode->value ?? $twoFactorMode->value);
                @endphp
                @include('components::form.radio-group', [
                    'name' => 'two_factor_mode',
                    'options' => $twoFactorModeOptions,
                    'value' => $twoFactorModeValue
                ])
                @include('components::form.error', [
                    'messages' => $errors->get('two_factor_mode')
                ])
            </fieldset>
            
            <!-- 二段階認証方法設定 -->
            @if(!empty($enabledTwoFactorMethods))
                <fieldset>
                <legend>
                    {{ __('common.two_factor_method.label') }}
                    @if(count($enabledTwoFactorMethods) > 1)
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                            ({{ count($enabledTwoFactorMethods) }} {{ __('common.available_methods') }})
                        </span>
                    @endif
                </legend>

                @php
                    $currentTwoFactorMethod = old('two_factor_method', $member?->two_factor_method ?? $defaultTwoFactorMethod);
                    $currentMethodValid = array_key_exists($currentTwoFactorMethod, $enabledTwoFactorMethods);
                    $defaultMethod = $defaultTwoFactorMethod ?? array_key_first($enabledTwoFactorMethods);
                    $currentMethod = $currentMethodValid ? $currentTwoFactorMethod : $defaultMethod;
                @endphp

                @if(count($enabledTwoFactorMethods) > 1)
                    @include('components::form.radio-group', [
                        'name' => 'two_factor_method',
                        'options' => $enabledTwoFactorMethods,
                        'value' => $currentMethod
                    ])
                @else
                    <input type="hidden" name="two_factor_method" value="{{ $currentMethod }}">
                    <p class="description-text">
                        {{ __('admin.profile.single_method_available') }}: 
                        <strong>{{ __($enabledTwoFactorMethods[$currentMethod]) }}</strong>
                    </p>
                @endif
                
                @include('components::form.error', [
                    'messages' => $errors->get('two_factor_method')
                ])
            </fieldset>
        @endif
        @else
            <fieldset>
                <p class="description-text">{!! __('admin.settings.members.form.two_factor_global_fixed', ['setting' => $twoFactorModeLabel]) !!}</p>
            </fieldset>
        @endif
    </section>



<script>
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
        <h2>{{ __('admin.profile.2fa_management') }}</h2>

        <!-- Passkeyデバイス -->
        @if($passkeyEnabled)
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Passkeyデバイス</h3>
                @if(!$passkeyDevices->isEmpty())
                    <button 
                        type="button"
                        onclick="openModal('deleteAllPasskeysModal')"
                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                        <i class="fas fa-trash-alt mr-1"></i>全て削除
                    </button>
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
                            <button 
                                type="button"
                                onclick="openDeletePasskeyModal('{{ $device->id }}', '{{ $device->name }}')"
                                class="ml-4 px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                                削除
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Passkeyの説明 -->
            <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>{{ __('admin.profile.passkey_info_title') }}
                </h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                    <li>{{ __('admin.profile.passkey_info_1') }}</li>
                    <li>{{ __('admin.profile.passkey_info_2') }}</li>
                    <li>{{ __('admin.profile.passkey_info_3') }}</li>
                    <li class="text-red-600 dark:text-red-400 font-semibold">{{ __('admin.profile.passkey_info_4') }}</li>
                </ul>
            </div>
        </div>
        @endif

        <!-- 回復コード -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">{{ __('two-factor.recovery_codes.title') }}</h3>
            </div>

            @if($hasRecoveryCodes)
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <i class="fas fa-info-circle mr-2"></i>
                            {{ __('two-factor.recovery_codes.remaining', ['count' => $recoveryCodesCount]) }}
                        </p>
                        <button type="button" 
                                onclick="openModal('deleteRecoveryCodesModal')"
                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i class="fas fa-trash mr-1"></i>
                            {{ __('admin.settings.members.form.delete_recovery_codes') }}
                        </button>
                    </div>
                </div>
            @else
                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('two-factor.recovery_codes.not_generated') }}</p>
            @endif

            <!-- 回復コードの説明 -->
            <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>{{ __('admin.profile.recovery_codes_info_title') }}
                </h4>
                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc list-inside">
                    <li>{{ __('admin.profile.recovery_codes_info_1') }}</li>
                    <li>{{ __('admin.profile.recovery_codes_info_2') }}</li>
                    <li>{{ __('admin.profile.recovery_codes_info_3') }}</li>
                    <li class="text-red-600 dark:text-red-400 font-semibold">{{ __('admin.settings.members.form.recovery_codes_admin_note') }}</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- 管理操作セクション -->
    <section>
        <h2>{{ __('common.management_operations') }}</h2>

        <fieldset>
            <legend>{{ __('admin.settings.members.form.unlock_lockout') }}</legend>
            <p class="mb-4">{{ __('admin.settings.members.form.unlock_lockout_description') }}</p>
            @include('components::form.button', [
                'variant' => 'info',
                'icon' => 'fas fa-unlock',
                'label' => __('admin.settings.members.form.unlock_lockout_button'),
                'onclick' => "openModal('unlockLockoutModal')",
            ])
        </fieldset>
        
        <fieldset>
            <legend>{{ __('admin.settings.members.form.force_logout') }}</legend>
            <p class="mb-4">{{ __('admin.settings.members.form.force_logout_description') }}</p>
            @include('components::form.button', [
                'variant' => 'warning',
                'icon' => 'fas fa-sign-out-alt',
                'label' => __('admin.settings.members.form.force_logout_button'),
                'onclick' => "openModal('forceLogoutModal')",
            ])
        </fieldset>

        @if(!$isInitialAdmin)
            <fieldset>
                <legend>{{ __('admin.settings.members.form.delete_member') }}</legend>
                <p class="mb-4">{{ __('admin.settings.members.form.delete_member_description') }}</p>
                @include('components::form.button', [
                    'variant' => 'danger',
                    'icon' => 'fas fa-trash',
                    'label' => __('admin.settings.members.form.delete_member_button'),
                    'onclick' => "openModal('deleteMemberModal')"
                ])
            </fieldset>
        @endif
    </section>
@endif

@if($includeForm && $formAction)
    </form>
@endif

@if(isset($member) && $member->exists)
    <!-- 隠しフォーム -->
    <form id="forceLogoutForm-{{ $member->id }}" method="POST" action="{{ route('admin.settings.members.force-logout', $member->id) }}" style="display: none;">
        @csrf
    </form>

    <form id="unlockLockoutForm-{{ $member->id }}" method="POST" action="{{ route('admin.settings.members.unlock-2fa', $member->id) }}" style="display: none;">
        @csrf
    </form>

    @if(!$isInitialAdmin)
        <form id="deleteMemberForm-{{ $member->id }}" method="POST" action="{{ route('admin.settings.members.destroy', $member->id) }}" style="display: none;">
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
    
    @include('components.modal', [
        'id' => 'forceLogoutModal',
        'title' => __('admin.settings.members.modals.force_logout.title'),
        'message' => __('admin.settings.members.modals.force_logout.message', ['name' => $member->name]),
        'confirm_label' => __('admin.settings.members.modals.force_logout.confirm'),
        'cancel_label' => __('common.cancel'),
        'form' => $forceLogoutFormId,
        'icon_type' => 'warning',
        'confirm_color' => 'yellow'
        ])

    @include('components.modal', [
        'id' => 'unlockLockoutModal',
        'title' => __('admin.settings.members.modals.unlock_lockout.title'),
        'message' => __('admin.settings.members.modals.unlock_lockout.message', ['name' => $member->name]),
        'confirm_label' => __('admin.settings.members.modals.unlock_lockout.confirm'),
        'cancel_label' => __('common.cancel'),
        'form' => $unlockLockoutFormId,
        'icon_type' => 'info',
        'confirm_color' => 'blue'
        ])

    @if(!$isInitialAdmin)
        @include('components.modal', [
            'id' => 'deleteMemberModal',
            'title' => __('admin.settings.members.modals.delete.title'),
            'message' => __('admin.settings.members.modals.delete.message', ['name' => $member->name]) . "\n\n" . __('admin.settings.members.modals.delete.warning'),
            'confirm_label' => __('common.delete'),
            'cancel_label' => __('common.cancel'),
            'form' => $deleteMemberFormId,
            'icon_type' => 'danger',
            'confirm_color' => 'red'
        ])
    @endif
@endif

@if($includeForm && $formAction)
    </form>
@endif