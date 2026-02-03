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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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
                'label' => __('components.login_notification.options.disabled'),
                'icon' => 'fas fa-bell-slash',
            ],
            [
                'value' => (string) AuthenticationMode::DifferentDevice->value,
                'label' => __('components.login_notification.options.different_device'),
                'icon' => 'fas fa-exclamation-triangle',
            ],
            [
                'value' => (string) AuthenticationMode::Always->value,
                'label' => __('components.login_notification.options.always'),
                'icon' => 'fas fa-bell',
            ],
        ];
    } else {
        // 全体設定用（全オプション）
        $options = [
            [
                'value' => (string) AuthenticationMode::Disabled->value,
                'label' => __('components.login_notification.options.disabled'),
                'icon' => 'fas fa-bell-slash',
            ],
            [
                'value' => (string) AuthenticationMode::DifferentDevice->value,
                'label' => __('components.login_notification.options.different_device'),
                'icon' => 'fas fa-exclamation-triangle',
            ],
            [
                'value' => (string) AuthenticationMode::Always->value,
                'label' => __('components.login_notification.options.always'),
                'icon' => 'fas fa-bell',
            ],
            [
                'value' => (string) AuthenticationMode::UseProfileSetting->value,
                'label' => __('components.login_notification.options.use_profile_setting'),
                'icon' => 'fas fa-user-cog',
            ],
        ];
    }
@endphp

@unless($hideSection)
    @if($showSettings)
        {{-- 設定可能な場合 --}}
        <fieldset>
            <legend>{{ __('components.login_notification.label') }}</legend>
            <x-form-radio-card-group
                :name="$name"
                :options="$options"
                :value="$value"
                :columns="$columns"
            />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('components.login_notification.help') }}</p>
        </fieldset>
    @else
        {{-- 全体設定で固定されている場合：全体設定の値を選択状態で表示し操作不可にする --}}
        <fieldset>
            <legend>{{ __('components.login_notification.label') }}</legend>
            <x-form-radio-card-group
                :name="$name"
                :options="$options"
                :value="(string)$globalSetting"
                :columns="$columns"
                :disabled="true"
            />
            <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                <i class="fas fa-lock mr-1"></i>
                {{ __('components.login_notification.global_setting_locked') }}
            </p>
        </fieldset>
    @endif
@endunless
