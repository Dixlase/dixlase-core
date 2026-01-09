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
    'twoFaPasskeyEnabled' => false,
    'twoFaDefaultMethod' => '0',
    'columns' => 2,
])

@php
    $defaultMethodOptions = [
        ['value' => '0', 'label' => __('common.email')],
        ['value' => '1', 'label' => __('components.two_fa.passkey')],
    ];
    $currentDefaultMethod = old('default_two_fa_method', $twoFaDefaultMethod);
@endphp

<div x-data="{ passkeyEnabled: {{ $twoFaPasskeyEnabled ? 'true' : 'false' }} }">
    <fieldset>
        <legend>{{ __('components.two_fa.default_method') }}</legend>
        
        <div x-show="!passkeyEnabled" class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <i class="fas fa-info-circle mr-1"></i>
                {{ __('components.two_fa.passkey_disabled_default_email_only') }}
            </p>
        </div>
        
        <div :class="{ 'opacity-50 pointer-events-none': !passkeyEnabled }">
            <x-form.radio-card-group
                name="default_two_fa_method"
                :options="$defaultMethodOptions"
                :value="$currentDefaultMethod"
                :columns="$columns"
            />
        </div>
        
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('components.two_fa.default_method_help') }}
        </p>
        <x-form.error
            :messages="$errors->get('default_two_fa_method')"
        />
    </fieldset>
</div>
