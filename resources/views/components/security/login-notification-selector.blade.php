{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-security.login-notification-selector />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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
    'name' => 'login_notification_mode',
    'value' => '0',
    'globalSetting' => null, // 全体設定の値（0=無効, 1=異なる端末時のみ, 2=常に有効, 3=プロフィール設定に従う）
    'excludeUseProfileSetting' => false, // プロフィール設定に従う選択肢を除外するか
    'columns' => 3,
])

@php
    use App\Enums\AuthenticationMode;
    
    // 全体設定が無効の場合はセクションを非表示
    $hideSection = ($globalSetting === AuthenticationMode::Disabled->value);
    
    // 設定可能かどうかの判定
    // - 全体設定画面（globalSetting === null）の場合は常に設定可能
    // - 個別設定画面（excludeUseProfileSetting === true）の場合、全体設定が「プロフィール設定に従う」の場合のみ設定可能
    // - プロフィール画面（excludeUseProfileSetting === false）の場合、全体設定が「プロフィール設定に従う」の場合のみ設定可能
    if ($globalSetting === null) {
        // 全体設定画面
        $showSettings = true;
    } elseif ($excludeUseProfileSetting) {
        // 個別設定画面（ユーザー作成・編集）
        $showSettings = ($globalSetting === AuthenticationMode::UseProfileSetting->value);
    } else {
        // プロフィール画面
        $showSettings = ($globalSetting === AuthenticationMode::UseProfileSetting->value);
    }
    
    // オプションを取得
    if ($excludeUseProfileSetting) {
        // プロフィール設定用（UseProfileSettingを除く）
        $options = [
            [
                'value' => (string) AuthenticationMode::Disabled->value,
                'label' => __('components/security/login-notification-selector.options.disabled'),
                'icon' => 'fas fa-bell-slash',
            ],
            [
                'value' => (string) AuthenticationMode::DifferentDevice->value,
                'label' => __('components/security/login-notification-selector.options.different_device'),
                'icon' => 'fas fa-exclamation-triangle',
            ],
            [
                'value' => (string) AuthenticationMode::Always->value,
                'label' => __('components/security/login-notification-selector.options.always'),
                'icon' => 'fas fa-bell',
            ],
        ];
    } else {
        // 全体設定用（全オプション）
        $options = [
            [
                'value' => (string) AuthenticationMode::Disabled->value,
                'label' => __('components/security/login-notification-selector.options.disabled'),
                'icon' => 'fas fa-bell-slash',
            ],
            [
                'value' => (string) AuthenticationMode::DifferentDevice->value,
                'label' => __('components/security/login-notification-selector.options.different_device'),
                'icon' => 'fas fa-exclamation-triangle',
            ],
            [
                'value' => (string) AuthenticationMode::Always->value,
                'label' => __('components/security/login-notification-selector.options.always'),
                'icon' => 'fas fa-bell',
            ],
            [
                'value' => (string) AuthenticationMode::UseProfileSetting->value,
                'label' => __('components/security/login-notification-selector.options.use_profile_setting'),
                'icon' => 'fas fa-user-cog',
            ],
        ];
    }
@endphp

@unless($hideSection)
    @if($showSettings)
        {{-- When configurable --}}
        <fieldset>
            <legend>{{ __('components/security/login-notification-selector.label') }}</legend>
            <x-form-radio-card-group
                :name="$name"
                :options="$options"
                :value="$value"
                :columns="$columns"
            />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('components/security/login-notification-selector.help') }}</p>
        </fieldset>
    @else
        {{-- When fixed by global settings: display the global settings value as selected and disable interaction --}}
        <fieldset>
            <legend>{{ __('components/security/login-notification-selector.label') }}</legend>
            <x-form-radio-card-group
                :name="$name"
                :options="$options"
                :value="(string)$globalSetting"
                :columns="$columns"
                :disabled="true"
            />
            <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                <i class="fas fa-lock mr-1"></i>
                {{ __('components/security/login-notification-selector.global_setting_locked') }}
            </p>
        </fieldset>
    @endif
@endunless
