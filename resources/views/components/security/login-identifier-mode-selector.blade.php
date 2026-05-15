{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-security.login-identifier-mode-selector />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact info@dixlase.org).

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
    'name' => 'login_identifier_mode',
    'value' => '1',
    'columns' => 3,
    'disabled' => false,
])

@php
    $options = \App\Enums\LoginIdentifierMode::radioCardOptions();
@endphp

<fieldset>
    <legend>{{ __('components/security/login-identifier-mode-selector.label') }}</legend>
    <x-form-radio-card-group
        :name="$name"
        :options="$options"
        :value="$value"
        :columns="$columns"
        :disabled="$disabled"
    />
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('components/security/login-identifier-mode-selector.help') }}</p>
</fieldset>
