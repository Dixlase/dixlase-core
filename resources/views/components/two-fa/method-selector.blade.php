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
    'name' => 'two_fa_passkey_mode',
    'value' => '2',
    'columns' => 3,
    'globalSettingsUrl' => null,
])

@php
    use App\Enums\PasskeyMode;
    $passkeyModeOptions = PasskeyMode::getGlobalOptions();
    $currentPasskeyMode = old($name, $value);
@endphp

{{-- メール認証は常に有効 --}}
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
</fieldset>

{{-- パスキー設定 --}}
<fieldset>
    <legend>{{ __('common.passkey_mode.label') }}</legend>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        {{ __('common.passkey_mode.help.global') }}
    </p>
    
    <x-form.radio-card-group
        :name="$name"
        :options="$passkeyModeOptions"
        :value="$currentPasskeyMode"
        :columns="$columns"
    />
    
    @error($name)
        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
    @enderror
</fieldset>
