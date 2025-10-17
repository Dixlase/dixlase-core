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
            @include('components::form.hidden', [
                'name' => 'id',
                'value' => $member->id,
            ])
        @endif
@endif

    <!-- 基本情報セクション -->
    <section>
        <h2>{{ __('common.basic_info') }}</h2>
        
        <fieldset>
            <legend>{{ __('common.name') }}</legend>
            @include('components::form.text', [
                'id' => 'name',
                'name' => 'name',
                'value' => old('name', $member->name ?? ''),
                'required' => true,
            ])
            @include('components::form.error', [
                'messages' => $errors->get('name')
            ])
        </fieldset>

        <fieldset>
            <legend>{{ __('common.description') }}</legend>
            <textarea name="description" id="description" rows="3" 
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">{{ old('description', $member->description ?? '') }}</textarea>
            @include('components::form.error', [
                'messages' => $errors->get('description')
            ])
        </fieldset>

        <fieldset>
            <legend>{{ __('common.email') }}</legend>
            @include('components::form.text', [
                'type' => 'email',
                'id' => 'email',
                'name' => 'email',
                'value' => old('email', $member->email ?? ''),
                'required' => true,
                'autocomplete' => 'email',
            ])
            @include('components::form.error', [
                'messages' => $errors->get('email')
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
                {{ __('common.status') }}
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
                <input type="hidden" name="role" value="{{ \App\Enums\MemberRole::SUPER_ADMIN->value }}">
                <p class="description-text">{{ __('admin.settings.members.form.initial_admin_role_fixed') }}</p>
            @else
                @php
                    $roleValue = old('role', $member->role->value ?? \App\Enums\MemberRole::ADMIN->value);
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
            
            <p>{{ __('common.two_factor_global_setting_fixed') }}</p>
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
    </section>

    @if(isset($member) && $member->exists)
        <!-- 管理操作セクション -->
        <section>
            <h2>{{ __('common.management_operations') }}</h2>
            
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

<!-- 編集時のみ：モーダルと隠しフォーム（フォーム外に配置） -->
@if(isset($member) && $member->id)
    <!-- 隠しフォーム -->
    <form id="forceLogoutForm-{{ $member->id }}" method="POST" action="{{ route('admin.settings.members.force-logout', $member->id) }}" style="display: none;">
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
        $deleteMemberFormId = 'deleteMemberForm-' . $member->id;
    @endphp
    
    @include('components.modal', [
        'id' => 'forceLogoutModal',
        'title' => __('admin.settings.members.modals.force_logout.title'),
        'message' => __('admin.settings.members.modals.force_logout.message', ['name' => $member->name]),
        'confirm_label' => __('admin.settings.members.modals.force_logout.confirm'),
        'cancel_label' => __('admin.settings.members.modals.cancel'),
        'form' => $forceLogoutFormId,
        'icon_type' => 'warning',
        'confirm_color' => 'yellow'
        ])

    @if(!$isInitialAdmin)
        @include('components.modal', [
            'id' => 'deleteMemberModal',
            'title' => __('admin.settings.members.modals.delete.title'),
            'message' => __('admin.settings.members.modals.delete.message', ['name' => $member->name]) . "\n\n" . __('admin.settings.members.modals.delete.warning'),
            'confirm_label' => __('common.delete'),
            'cancel_label' => __('admin.settings.members.modals.cancel'),
            'form' => $deleteMemberFormId,
            'icon_type' => 'danger',
            'confirm_color' => 'red'
        ])
    @endif
@endif

@if($includeForm && $formAction)
    </form>
@endif

