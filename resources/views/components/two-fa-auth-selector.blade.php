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
    'name' => 'two_fa_mode',
    'value' => '0',
    'globalSetting' => null, // 全体設定の値（0=無効, 1=異なる端末時のみ, 2=常に有効, 3=プロフィール設定に従う）
    'excludeUseProfileSetting' => false, // プロフィール設定に従う選択肢を除外するか
    'passkeyGloballyEnabled' => false, // パスキーが全体で有効か
    'passkeyEnabled' => true, // パスキーが個別に有効か
    'defaultTwoFaMethod' => '0', // デフォルトの二段階認証方法（0=メール, 1=パスキー）
    'columns' => 3,
    'globalSettingsUrl' => null, // 全体設定へのリンクURL（nullの場合は注意書きを非表示）
])

@php
    use App\Enums\AuthenticationMode;
    use App\Enums\TwoFaMethod;
    
    // 全体設定が「プロフィール設定を反映」の場合、またはglobalSettingがnull（全体設定画面）の場合は設定を表示
    $showSettings = ($globalSetting === AuthenticationMode::UseProfileSetting->value) || ($globalSetting === null);
    
    // 全体設定で固定されている場合（excludeUseProfileSettingがtrueでも、globalSettingが0,1,2の場合は固定）
    $isFixedByGlobal = !$showSettings && $globalSetting !== null;
    
    // オプションを取得
    if ($excludeUseProfileSetting) {
        // プロフィール設定用（UseProfileSettingを除く）
        $options = [
            [
                'value' => (string) AuthenticationMode::Disabled->value,
                'label' => __('components.two_fa.options.disabled'),
                'icon' => 'fas fa-shield-alt',
            ],
            [
                'value' => (string) AuthenticationMode::DifferentDevice->value,
                'label' => __('components.two_fa.options.different_device'),
                'icon' => 'fas fa-shield-virus',
            ],
            [
                'value' => (string) AuthenticationMode::Always->value,
                'label' => __('components.two_fa.options.always'),
                'icon' => 'fas fa-shield-check',
            ],
        ];
    } else {
        // 全体設定用（全オプション）
        $options = [
            [
                'value' => (string) AuthenticationMode::Disabled->value,
                'label' => __('components.two_fa.options.disabled'),
                'icon' => 'fas fa-shield-alt',
            ],
            [
                'value' => (string) AuthenticationMode::DifferentDevice->value,
                'label' => __('components.two_fa.options.different_device'),
                'icon' => 'fas fa-shield-virus',
            ],
            [
                'value' => (string) AuthenticationMode::Always->value,
                'label' => __('components.two_fa.options.always'),
                'icon' => 'fas fa-shield-check',
            ],
            [
                'value' => (string) AuthenticationMode::UseProfileSetting->value,
                'label' => __('components.two_fa.options.use_profile_setting'),
                'icon' => 'fas fa-user-cog',
            ],
        ];
    }
    
    $initialPasskeyEnabled = old('two_fa_passkey_enabled', $passkeyEnabled);
    $initialTwoFactorMode = old($name, $value);
@endphp

<div x-data="{ 
    passkeyEnabled: {{ $passkeyGloballyEnabled && $initialPasskeyEnabled ? 'true' : 'false' }},
    twoFaMode: '{{ $initialTwoFactorMode }}',
    get twoFaEnabled() {
        return this.twoFaMode !== '0';
    },
    init() {
        this.$watch('passkeyEnabled', value @php echo '=>'; @endphp {
            if (!value) {
                const emailRadio = document.querySelector('input[name=\'default_two_fa_method\'][value=\'0\']');
                if (emailRadio) {
                    emailRadio.checked = true;
                    emailRadio.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
    }
}">
    
    @if($showSettings || $excludeUseProfileSetting)
        {{-- 設定可能な場合 --}}
        <fieldset>
            <legend>{{ __('components.two_fa.mode_label') }}</legend>
            <x-form.radio-card-group
                :name="$name"
                :options="$options"
                :value="$value"
                :columns="$columns"
                xModel="twoFaMode"
            />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{!! __('components.two_fa.help') !!}</p>
        </fieldset>
    @elseif($isFixedByGlobal)
        {{-- 全体設定で固定されている場合 --}}
        <fieldset>
            <legend>{{ __('components.two_fa.mode_label') }}</legend>
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
                    {{ __('common.two_fa_global_setting_fixed') }}
                </p>
            </div>
        </fieldset>
    @endif

    {{-- 二段階認証方法設定 --}}
    <fieldset>
        <legend>{{ __('components.two_fa.method_label') }}</legend>

        <div class="space-y-6">
            <div class="space-y-3">
                <div class="flex items-center space-x-3">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 mr-2"></i>
                        <span class="text-sm font-medium">{{ __('components.two_fa.email_always_enabled') }}</span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('components.two_fa.email_always_enabled') }}</span>
                </div>
                
                @if($passkeyGloballyEnabled || $globalSetting === null)
                    {{-- 全体設定でパスキーが有効な場合、または全体設定画面の場合：個別に設定可能 --}}
                    <div class="flex items-center space-x-3" :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled }">
                        <x-form.toggle
                            name="passkey_two_fa_enabled"
                            :label="__('components.two_fa.passkey')"
                            :checked="$initialPasskeyEnabled"
                            xModel="passkeyEnabled"
                        />
                    </div>
                @else
                    {{-- 全体設定でパスキーが無効な場合：表示のみ --}}
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center">
                            <i class="fas fa-times-circle text-gray-400 dark:text-gray-600 mr-2"></i>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('components.two_fa.passkey') }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('components.two_fa.passkey_disabled_globally') }}
                        </span>
                    </div>
                @endif
            </div>

            @if($globalSettingsUrl)
                <div class="space-y-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('components.two_fa.method_note') }}
                        <a href="{{ $globalSettingsUrl }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                            {{ __('components.two_fa.change_in_global_settings') }}
                        </a>
                    </p>
                </div>
            @endif
        </div>

        @error('passkey_two_fa_enabled')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </fieldset>

    {{-- デフォルトの認証方法（パスキーが全体設定で有効、またはユーザーが個別に有効にしている場合に表示） --}}
    @if($passkeyGloballyEnabled || $initialPasskeyEnabled)
        <fieldset>
            <legend>{{ __('components.two_fa.default_method') }}</legend>
            @php
                $defaultMethodOptions = [
                    ['value' => '0', 'label' => __('common.email')],
                    ['value' => '1', 'label' => __('components.two_fa.passkey')],
                ];
                $currentDefaultMethod = old('default_two_fa_method', $defaultTwoFaMethod);
            @endphp
            
            <div x-show="!passkeyEnabled" class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('components.two_fa.passkey_disabled_default_email_only') }}
                </p>
            </div>
            
            <div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled || !passkeyEnabled }">
                <x-form.radio-card-group
                    name="default_two_fa_method"
                    :options="$defaultMethodOptions"
                    :value="$currentDefaultMethod"
                    :columns="2"
                />
            </div>
            
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('components.two_fa.default_method_help') }}
            </p>
            <x-form.error
                :messages="$errors->get('default_two_fa_method')"
            />
        </fieldset>
    @endif
    
</div>
