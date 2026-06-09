{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-text />

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
    'id' => null,
    'name' => null,
    'value' => '',
    'type' => 'text', // text, number, email, password, etc.
    'disabled' => false,
    'class' => '',
    'step' => null,
    'min' => null,
    'max' => null,
    'placeholder' => null,
    'required' => false,
    'pattern' => null,
    'minlength' => null,
    'maxlength' => null,
    'ariaDescribedby' => null,
    'ariaLabel' => null,
    'autocomplete' => null,
    'xModel' => null,
    'showPasswordToggle' => false,
])

@if ($showPasswordToggle)
<div x-data="{ showPassword: false }" class="relative">
    <input :type="showPassword ? 'text' : 'password'"
@else
<input type="{{ $type }}"
@endif
    id="{{ $id ?? $name }}"
    name="{{ $name }}"
    @if ($disabled) disabled @endif
    @if ($required) required @endif
    @if ($step) step="{{ $step }}" @endif
    @if ($min !== null) min="{{ $min }}" @endif
    @if ($max !== null) max="{{ $max }}" @endif
    @if ($placeholder) placeholder="{{ $placeholder }}" @endif
    @if ($pattern) pattern="{{ $pattern }}" @endif
    @if ($minlength) minlength="{{ $minlength }}" @endif
    @if ($maxlength) maxlength="{{ $maxlength }}" @endif
    @if ($ariaDescribedby) aria-describedby="{{ $ariaDescribedby }}" @endif
    @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
    @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
    @if ($xModel) x-model="{{ $xModel }}" @endif
    {{ $attributes->except(['class'])->merge([]) }}
    class="input-common input-full my-2 {{ $showPasswordToggle ? 'pr-10' : '' }} {{ $class }}"
    value="{{ old($name, $value) }}"
    >
@if ($showPasswordToggle)
    <button type="button"
        x-on:click="showPassword = !showPassword"
        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
        :aria-label="showPassword ? '{{ __('components/form.hide_password') }}' : '{{ __('components/form.show_password') }}'">
        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
    </button>
</div>
@endif
