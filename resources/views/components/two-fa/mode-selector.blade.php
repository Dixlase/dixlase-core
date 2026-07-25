{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-two-fa.mode-selector />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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
    'name' => 'two_fa_mode',
    'value' => '0',
    'globalSetting' => null,
    'excludeUseProfileSetting' => false,
    'columns' => 4,
    'xModel' => null,
    'isProfile' => false,
    'isTwoFaEditable' => true,
])

@php
    use App\Enums\AuthenticationMode;
    
    // プロフィール画面の場合は、isTwoFaEditableで判定
    if ($isProfile) {
        $showSettings = $isTwoFaEditable;
        $isFixedByGlobal = !$isTwoFaEditable && $globalSetting !== null;
    } else {
        // 全体設定画面の場合は、従来通り
        $showSettings = ($globalSetting === AuthenticationMode::UseProfileSetting->value) || ($globalSetting === null);
        $isFixedByGlobal = !$showSettings && $globalSetting !== null;
    }
    
    // デバッグ情報
    $debugInfo = [
        'isProfile' => $isProfile,
        'isTwoFaEditable' => $isTwoFaEditable,
        'globalSetting' => $globalSetting,
        'showSettings' => $showSettings,
        'isFixedByGlobal' => $isFixedByGlobal,
    ];
    
    if ($excludeUseProfileSetting) {
        $options = [
            [
                'value' => (string) AuthenticationMode::Disabled->value,
                'label' => __('components/security/two-fa-general-settings.options.disabled'),
                'icon' => 'fas fa-shield-alt',
            ],
            [
                'value' => (string) AuthenticationMode::DifferentDevice->value,
                'label' => __('components/security/two-fa-general-settings.options.different_device'),
                'icon' => 'fas fa-shield-virus',
            ],
            [
                'value' => (string) AuthenticationMode::Always->value,
                'label' => __('components/security/two-fa-general-settings.options.always'),
                'icon' => 'fas fa-shield-check',
            ],
        ];
    } else {
        $options = [
            [
                'value' => (string) AuthenticationMode::Disabled->value,
                'label' => __('components/security/two-fa-general-settings.options.disabled'),
                'icon' => 'fas fa-shield-alt',
            ],
            [
                'value' => (string) AuthenticationMode::DifferentDevice->value,
                'label' => __('components/security/two-fa-general-settings.options.different_device'),
                'icon' => 'fas fa-shield-virus',
            ],
            [
                'value' => (string) AuthenticationMode::Always->value,
                'label' => __('components/security/two-fa-general-settings.options.always'),
                'icon' => 'fas fa-shield-check',
            ],
            [
                'value' => (string) AuthenticationMode::UseProfileSetting->value,
                'label' => __('components/security/two-fa-general-settings.options.use_profile_setting'),
                'icon' => 'fas fa-user-cog',
            ],
        ];
    }
@endphp

@if($isProfile)
    {{-- For profile screen --}}
    @if($showSettings)
        <fieldset>
            <legend>{{ __('components/security/two-fa-general-settings.mode_label') }}</legend>
            <x-form-radio-card-group
                :name="$name"
                :options="$options"
                :value="$value"
                :columns="$columns"
                :xModel="$xModel"
            />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{!! __('components/security/two-fa-general-settings.help') !!}</p>
        </fieldset>
    @elseif($isFixedByGlobal)
        <fieldset>
            <legend>{{ __('components/security/two-fa-general-settings.mode_label') }}</legend>
            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
                <p class="text-sm">
                    @php
                        $globalMode = AuthenticationMode::tryFrom($globalSetting);
                        if ($globalMode) {
                            echo str_replace(':account_type', __('common.account_types.member'), $globalMode->twoFactorLabel());
                        }
                    @endphp
                </p>
                <p class="text-xs mt-1">
                    {{ __('components/security/two-fa-general-settings.global_setting_fixed') }}
                </p>
            </div>
        </fieldset>
    @endif
@elseif($showSettings || $excludeUseProfileSetting)
    {{-- For general settings screen --}}
    <fieldset>
        <legend>{{ __('components/security/two-fa-general-settings.mode_label') }}</legend>
        <x-form-radio-card-group
            :name="$name"
            :options="$options"
            :value="$value"
            :columns="$columns"
            :xModel="$xModel"
        />
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{!! __('components/security/two-fa-general-settings.help') !!}</p>
    </fieldset>
@elseif($isFixedByGlobal)
    <fieldset>
        <legend>{{ __('components/security/two-fa-general-settings.mode_label') }}</legend>
        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-md border">
            <p class="text-sm">
                @php
                    $globalMode = AuthenticationMode::tryFrom($globalSetting);
                    if ($globalMode) {
                        echo str_replace(':account_type', __('common.account_types.member'), $globalMode->twoFaLabel());
                    }
                @endphp
            </p>
            <p class="text-xs mt-1">
                {{ __('components/security/two-fa-general-settings.global_setting_fixed') }}
            </p>
        </div>
    </fieldset>
@endif
