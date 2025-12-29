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
    'oninvalid' => null,
    'oninput' => null,
    'onpaste' => null,
    'oncopy' => null,
    'oncut' => null,
    'oncontextmenu' => null,
    'ariaDescribedby' => null,
    'ariaLabel' => null,
    'autocomplete' => null,
    'xModel' => null,
])

<input type="{{ $type }}"
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
    @if ($oninvalid) oninvalid="{{ $oninvalid }}" @endif
    @if ($oninput) oninput="{{ $oninput }}" @endif
    @if ($onpaste) onpaste="{{ $onpaste }}" @endif
    @if ($oncopy) oncopy="{{ $oncopy }}" @endif
    @if ($oncut) oncut="{{ $oncut }}" @endif
    @if ($oncontextmenu) oncontextmenu="{{ $oncontextmenu }}" @endif
    @if ($ariaDescribedby) aria-describedby="{{ $ariaDescribedby }}" @endif
    @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
    @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
    @if ($xModel) x-model="{{ $xModel }}" @endif
    class="input-common my-2 {{ $class }}"
    value="{{ old($name, $value) }}"
    >
