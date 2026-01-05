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
    
    // 全体設定が「プロフィール設定を反映」の場合は設定を表示
    $showSettings = ($globalSetting === AuthenticationMode::UseProfileSetting->value);
    
    // オプションを取得
    if ($excludeUseProfileSetting) {
        // プロフィール設定用（UseProfileSettingを除く）
        $options = [];
        foreach (AuthenticationMode::forProfile() as $case) {
            $options[] = [
                'value' => (string) $case->value,
                'label' => $case->notificationLabel(),
                'icon' => match($case) {
                    AuthenticationMode::Disabled => 'fas fa-bell-slash',
                    AuthenticationMode::DifferentDevice => 'fas fa-exclamation-triangle',
                    AuthenticationMode::Always => 'fas fa-bell',
                    default => 'fas fa-bell',
                },
            ];
        }
    } else {
        // 全体設定用（全オプション）
        $options = [];
        foreach (AuthenticationMode::cases() as $case) {
            $options[] = [
                'value' => (string) $case->value,
                'label' => $case->notificationLabel(),
                'icon' => match($case) {
                    AuthenticationMode::Disabled => 'fas fa-bell-slash',
                    AuthenticationMode::DifferentDevice => 'fas fa-exclamation-triangle',
                    AuthenticationMode::Always => 'fas fa-bell',
                    AuthenticationMode::UseProfileSetting => 'fas fa-user-cog',
                },
            ];
        }
    }
@endphp

@unless($hideSection)
    @if($showSettings || $excludeUseProfileSetting)
        {{-- 設定可能な場合 --}}
        <fieldset>
            <legend>{{ __('auth.login_notification_mode.label') }}</legend>
            <x-form.radio-card-group
                :name="$name"
                :options="$options"
                :value="$value"
                :columns="$columns"
            />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('auth.login_notification_mode.help') }}</p>
        </fieldset>
    @else
        {{-- 全体設定で固定されている場合 --}}
        <fieldset>
            <legend>{{ __('auth.login_notification_mode.label') }}</legend>
            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">
                        @if($globalSetting === AuthenticationMode::DifferentDevice->value)
                            {{ AuthenticationMode::DifferentDevice->notificationLabel() }}
                        @elseif($globalSetting === AuthenticationMode::Always->value)
                            {{ AuthenticationMode::Always->notificationLabel() }}
                        @endif
                    </span>
                </p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('admin/profile.login_notification_global_setting_help') }}
                </p>
            </div>
        </fieldset>
    @endif
@endunless
