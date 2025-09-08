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

@props([
    'require_password' => false,
])

<div class="mb-4">
    @include('components::form.label', [
        'for' => 'name',
        'text' => __('admin.settings.members.form.name'),
    ])
    @include('components::form.text', [
        'id' => 'name',
        'name' => 'name',
        'value' => old('name', $member->name ?? ''),
        'required' => true,
    ])
    @include('components::form.error', [
        'messages' => $errors->get('name')
    ])
</div>

<div class="mb-4">
    @include('components::form.label', [
        'for' => 'description',
        'text' => __('admin.settings.members.form.description'),
    ])
    <textarea name="description" id="description" rows="3" 
        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">{{ old('description', $member->description ?? '') }}</textarea>
    @include('components::form.error', [
        'messages' => $errors->get('description')
    ])
</div>

<div class="mb-4">
    @include('components::form.label', [
        'for' => 'email',
        'text' => __('admin.settings.members.form.email'),
    ])
    @include('components::form.text', [
        'type' => 'email',
        'id' => 'email',
        'name' => 'email',
        'value' => old('email', $member->email ?? ''),
        'required' => true,
    ])
    @include('components::form.error', [
        'messages' => $errors->get('email')
    ])
</div>

<div class="mb-6">
    @include('components::form.label', [
        'for' => 'password',
        'text' => $requirePassword ? __('admin.settings.members.form.password') : __('admin.settings.members.form.password_change_only'),
    ])
    @include('components::form.password-tools', [
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
</div>

<!-- 外観モード -->
@php
    $appearanceValue = old('appearance', (string) ($member->appearance->value ?? 0));
    $appearanceOptions = [
        '0' => 'admin.profile.appearance_auto',
        '1' => 'admin.profile.appearance_light',
        '2' => 'admin.profile.appearance_dark',
    ];
@endphp

<div class="mb-6" data-member-theme>
    <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">
        {{ __('admin.settings.members.form.appearance') }}
    </label>
    @include('components::form.radio-group', [
        'name' => 'appearance',
        'options' => $appearanceOptions,
        'value' => $appearanceValue
    ])
    @include('components::form.error', [
        'messages' => $errors->get('appearance')
    ])
</div>

<!-- アカウントステータス -->
<div class="mb-6">
    <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">
        {{ __('admin.settings.members.form.status') }}
        @if(isset($isInitialAdmin) && $isInitialAdmin)
            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">（初期管理者のため変更不可）</span>
        @endif
    </label>
    @if(isset($isInitialAdmin) && $isInitialAdmin)
        <!-- 初期管理者の場合は読み取り専用表示 -->
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ \App\Enums\MemberStatus::Active->label() }}
            </p>
        </div>
        <input type="hidden" name="status" value="{{ \App\Enums\MemberStatus::Active->value }}">
    @else
        @include('components::form.radio-group', [
            'name' => 'status',
            'options' => $statusOptions,
            'value' => $statusValue
        ])
    @endif
    @include('components::form.error', [
        'messages' => $errors->get('status')
    ])
</div>

<!-- 権限 -->
<div class="mb-6">
    <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">
        {{ __('admin.settings.members.form.role') }}
        @if(isset($isInitialAdmin) && $isInitialAdmin)
            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">（初期管理者のため変更不可）</span>
        @endif
    </label>
    @if(isset($isInitialAdmin) && $isInitialAdmin)
        <!-- 初期管理者の場合は読み取り専用表示 -->
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ \App\Enums\MemberRole::SUPER_ADMIN->label() }}
            </p>
        </div>
        <input type="hidden" name="role" value="{{ \App\Enums\MemberRole::SUPER_ADMIN->value }}">
    @else
        @include('components::form.radio-group', [
            'name' => 'role',
            'options' => $roleOptions,
            'value' => $roleValue
        ])
    @endif
    @include('components::form.error', [
        'messages' => $errors->get('role')
    ])
</div>

<!-- ログイン通知設定の初期値 -->




@php
    // ログイン通知設定の初期値
    $currentLoginNotificationMode = old('login_notification_mode', (string)($member->login_notification_mode->value ?? '1'));
    
    // ログイン通知設定のオプション
    $loginNotificationOptions = [
        '1' => 'admin.profile.login_notification_mode_options.disabled',
        '3' => 'admin.profile.login_notification_mode_options.only_new_device',
        '2' => 'admin.profile.login_notification_mode_options.always',
    ];
    
    // 二段階認証設定の初期値 - 全体設定に基づいてデフォルト値を決定
    $memberTwoFactorMode = $member->two_factor_mode ?? null;
    $defaultTwoFactorModeValue = '1'; // デフォルトは無効
    
    // 全体設定が「プロフィール設定を反映」の場合、メンバーの設定値またはデフォルト値を使用
    if ($force2fa === \App\Enums\TwoFactorMode::UseProfileSetting->value) {
        $currentTwoFactorMode = old('two_factor_mode', $memberTwoFactorMode ? (string)$memberTwoFactorMode->value : $defaultTwoFactorModeValue);
    } else {
        // 全体設定で固定されている場合は、その値を使用
        $currentTwoFactorMode = (string)$force2fa;
    }

    // 二段階認証モードのオプション
    $twoFactorModeOptions = [
        '1' => 'admin.profile.two_factor_mode_options.disabled',
        '3' => 'admin.profile.two_factor_mode_options.only_new_device',
        '2' => 'admin.profile.two_factor_mode_options.always',
    ];
    
    $currentTwoFactorMethods = old('two_factor_methods', $member?->two_factor_methods ? explode(',', $member?->two_factor_methods) : []);
    $enabledTwoFactorMethods = $enabledTwoFactorMethods ?? [];
    $defaultTwoFactorMethod = $defaultTwoFactorMethod ?? '';
@endphp


<!-- ログイン通知設定 -->
<div class="mb-6">
    <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">
        {{ __('admin.profile.login_notification_mode') }}
    </label>
    @include('components::form.radio-group', [
        'name' => 'login_notification_mode',
        'options' => $loginNotificationOptions,
        'value' => $currentLoginNotificationMode
    ])
    <p class="mt-1 text-xs">
        {{ __('admin.profile.login_notification_help') }}
    </p>
    @include('components::form.error', [
        'messages' => $errors->get('login_notification_mode')
    ])
</div>

<!-- 二段階認証設定 -->
@if($force2fa === \App\Enums\TwoFactorMode::UseProfileSetting->value)
    <!-- プロフィール設定を反映の場合：ユーザーが選択可能 -->
    <div class="mb-6" x-data="{ twoFactorMode: '{{ $currentTwoFactorMode }}' }">
        <label class="block font-medium text-sm mb-2">
            {{ __('admin.profile.two_factor_mode') }}
        </label>
        @include('components::form.radio-group', [
            'name' => 'two_factor_mode',
            'options' => $twoFactorModeOptions,
            'value' => $currentTwoFactorMode
        ])
        
        <!-- 二段階認証方法設定 -->
        @if(!empty($enabledTwoFactorMethods))
            <div class="mt-4" x-show="twoFactorMode !== '1'">
                <label class="block font-medium text-sm mb-2">
                    {{ __('admin.profile.two_factor_method') }}
                </label>

                @php
                    // 現在の認証方法を取得
                    $currentTwoFactorMethod = old('two_factor_method', $member?->two_factor_method ?? $defaultTwoFactorMethod);
                    // 現在の認証方法が有効な方法に含まれているか確認
                    $currentMethodValid = array_key_exists($currentTwoFactorMethod, $enabledTwoFactorMethods);
                    // デフォルトの認証方法を取得
                    $defaultMethod = $defaultTwoFactorMethod ?? array_key_first($enabledTwoFactorMethods);
                    // 現在の認証方法を決定（無効な場合はデフォルトを使用）
                    $currentMethod = $currentMethodValid ? $currentTwoFactorMethod : $defaultMethod;
                @endphp

                @if(count($enabledTwoFactorMethods) > 1)
                    @include('components::form.radio-group', [
                        'name' => 'two_factor_method',
                        'options' => $enabledTwoFactorMethods,
                        'value' => $currentMethod
                    ])
                @else
                    <!-- 認証方法が1つだけの場合 -->
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ array_values($enabledTwoFactorMethods)[0] }}
                            <span class="ml-1 text-xs text-blue-500">({{ __('admin.profile.default_method') }})</span>
                        </p>
                        <input type="hidden" name="two_factor_method" value="{{ array_keys($enabledTwoFactorMethods)[0] }}">
                    </div>
                @endif
                
                <p class="mt-1 text-xs">
                    {{ __('admin.profile.two_factor_method_help') }}
                </p>
            </div>
        @endif
        
        @include('components::form.error', [
            'messages' => $errors->get('two_factor_mode')
        ])
        @include('components::form.error', [
            'messages' => $errors->get('two_factor_method')
        ])
    </div>
@elseif($force2fa === \App\Enums\TwoFactorMode::OnlyNewDevice->value || $force2fa === \App\Enums\TwoFactorMode::Always->value)
    <!-- 全体設定で固定されている場合：表示のみ -->
    <div class="mb-6">
        <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">
            {{ __('admin.profile.two_factor_mode') }}
        </label>
        
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
            <p class="text-sm text-gray-700 dark:text-gray-300">
                <span class="font-medium">
                    @if($force2fa === \App\Enums\TwoFactorMode::OnlyNewDevice->value)
                        {{ __('admin.profile.two_factor_mode_options.only_new_device') }}
                    @elseif($force2fa === \App\Enums\TwoFactorMode::Always->value)
                        {{ __('admin.profile.two_factor_mode_options.always') }}
                    @endif
                </span>
            </p>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ __('admin.profile.two_factor_global_setting_fixed') }}
            </p>
        </div>
        
        <!-- 二段階認証方法設定 -->
        @if(!empty($enabledTwoFactorMethods))
            <div class="mt-4">
                <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('admin.profile.two_factor_method') }}
                    @if(count($enabledTwoFactorMethods) > 1)
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                            ({{ count($enabledTwoFactorMethods) }} {{ __('admin.profile.available_methods') }})
                        </span>
                    @endif
                </label>

                @php
                    // 現在の認証方法を取得
                    $currentTwoFactorMethod = old('two_factor_method', $member?->two_factor_method ?? $defaultTwoFactorMethod);
                    // 現在の認証方法が有効な方法に含まれているか確認
                    $currentMethodValid = array_key_exists($currentTwoFactorMethod, $enabledTwoFactorMethods);
                    // デフォルトの認証方法を取得
                    $defaultMethod = $defaultTwoFactorMethod ?? array_key_first($enabledTwoFactorMethods);
                    // 現在の認証方法を決定（無効な場合はデフォルトを使用）
                    $currentMethod = $currentMethodValid ? $currentTwoFactorMethod : $defaultMethod;
                @endphp

                @if(count($enabledTwoFactorMethods) > 1)
                    <!-- 複数の認証方法がある場合：選択可能 -->
                    @include('components::form.radio-group', [
                        'name' => 'two_factor_method',
                        'options' => $enabledTwoFactorMethods,
                        'value' => $currentMethod
                    ])
                @else
                    <!-- 認証方法が1つだけの場合：表示のみ -->
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                        <p class="text-sm">
                            {{ array_values($enabledTwoFactorMethods)[0] }}
                            <span class="ml-1 text-xs text-blue-500">({{ __('admin.profile.default_method') }})</span>
                        </p>
                        <p class="mt-1 text-xs">
                            {{ __('admin.profile.two_factor_method_global_setting_fixed') }}
                        </p>
                        <input type="hidden" name="two_factor_method" value="{{ array_keys($enabledTwoFactorMethods)[0] }}">
                    </div>
                @endif
                
                <p class="mt-1 text-xs">
                    {{ __('admin.profile.two_factor_method_help') }}
                </p>
            </div>
        @endif
    </div>
@endif

<!-- 管理操作ボタン（編集時のみ表示） -->
@if(isset($member) && $member->exists)
    <div class="mb-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
            管理操作
        </h3>
        
        @if(isset($isInitialAdmin) && $isInitialAdmin)
            <!-- 初期管理者の場合は削除不可の説明 -->
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            初期管理者アカウント
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>このアカウントは初期管理者のため、削除や強制ログアウトはできません。システムの安全性を保つため、これらの操作は制限されています。</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="flex flex-col sm:flex-row gap-2">
                <!-- 強制ログアウトボタン -->
                @include('components::form.button', [
                    'type' => 'button',
                    'label' => '強制ログアウト',
                    'class' => 'bg-orange-600 hover:bg-orange-700 text-white dark:bg-orange-500 dark:hover:bg-orange-600',
                    'onclick' => "openModal('forceLogoutModal')"
                ])

                <!-- 削除ボタン -->
                @include('components::form.button', [
                    'type' => 'button',
                    'label' => 'メンバーを削除',
                    'class' => 'bg-red-700 hover:bg-red-800 text-white dark:bg-red-600 dark:hover:bg-red-700',
                    'onclick' => "openModal('deleteModal')"
                ])
            </div>
        @endif
    </div>

    <!-- モーダル（編集時のみ） -->
    <!-- 強制ログアウトモーダル -->
    @include('components::form.modal', [
        'id' => 'forceLogoutModal',
        'title' => '強制ログアウトの確認',
        'message' => 'このメンバーを強制的にログアウトさせますか？<br><br>対象メンバー: ' . $member->name . '<br><br>この操作により、対象メンバーの全てのセッションが無効化され、再度ログインが必要になります。',
        'confirm_label' => '強制ログアウト',
        'cancel_label' => 'キャンセル',
        'form' => 'force-logout-form',
    ])

    <!-- 削除モーダル -->
    @include('components::form.modal', [
        'id' => 'deleteModal',
        'title' => '削除の確認',
        'message' => 'このユーザーを削除しますか？',
        'confirm_label' => '削除',
        'cancel_label' => 'キャンセル',
        'form' => 'delete-form',
    ])
@endif
