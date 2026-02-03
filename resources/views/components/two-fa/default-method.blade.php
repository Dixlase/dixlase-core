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
    'xModel' => null,
])

@php
    $defaultMethodOptions = [
        ['value' => '0', 'label' => __('common.email')],
        ['value' => '1', 'label' => __('components.two_fa.passkey')],
    ];
    $currentDefaultMethod = old('two_fa_default_method', $twoFaDefaultMethod);
@endphp

{{-- ラジオカードグループのみ（親スコープで無効化制御） --}}
<x-form-radio-card-group
    name="two_fa_default_method"
    :options="$defaultMethodOptions"
    :value="$currentDefaultMethod"
    :columns="$columns"
    :xModel="$xModel"
/>

<p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
    {{ __('components.two_fa.default_method_help') }}
</p>
<x-form-error
    :messages="$errors->get('two_fa_default_method')"
/>
