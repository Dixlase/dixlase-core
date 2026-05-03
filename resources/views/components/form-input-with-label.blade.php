{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-input-with-label />

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
    'id' => null,
    'name' => null,
    'label' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'autofocus' => false,
    'autocomplete' => null,
    'placeholder' => null,
    'xModel' => null,
    'xBind' => null,
    'class' => '',
    'containerClass' => 'mb-4',
    'labelClass' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2',
    'inputClass' => 'block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white',
    'errorClass' => 'border-red-500',
    'showError' => null,
    'errorMessage' => null,
])

<div class="{{ $containerClass }}">
    @if($label)
        <label for="{{ $id ?? $name }}" class="{{ $labelClass }}">
            {{ $label }}
        </label>
    @endif
    <input
        type="{{ $type }}"
        id="{{ $id ?? $name }}"
        name="{{ $name }}"
        @if($required) required @endif
        @if($autofocus) autofocus @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @if($xModel) x-model="{{ $xModel }}" @endif
        @if($xBind) {{ $xBind }} @endif
        @if(!$xModel) value="{{ old($name, $value) }}" @endif
        class="{{ $inputClass }} {{ $class }}"
        @if($showError) :class="{ '{{ $errorClass }}': {{ $showError }} }" @endif
    >
    @if($errorMessage)
        <p x-show="{{ $showError }}" x-text="{{ $errorMessage }}" class="mt-1 text-sm text-red-600 dark:text-red-400"></p>
    @endif
</div>
