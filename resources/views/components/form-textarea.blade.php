{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-textarea />

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
    'rows' => 10,
    'placeholder' => '',
    'required' => false,
    'readonly' => false,
    'class' => '',
    'label' => null,          // Pre-translated label text (nullable). Set to render a form-label tied to this textarea. Prior to this prop, callers that passed :label were silently dropping it.
    'xBindReadonly' => null,  // Alpine.jsのx-bind:readonly
    'xBindClass' => null,     // Alpine.jsのx-bind:class
    'xModel' => null,         // Alpine.jsのx-model
])

@if($label)
    <x-form-label :for="$id ?? $name" :text="$label" :required="$required" />
@endif
<textarea
    name="{{ $name }}"
    @if($id) id="{{ $id }}" @endif
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    class="input-common input-full {{ $class }}"
    @if($required) required @endif
    @if($readonly) readonly @endif
    {{ $xBindReadonly ? "x-bind:readonly=$xBindReadonly" : '' }}
    {{ $xBindClass ? "x-bind:class=$xBindClass" : '' }}
    {{ $xModel ? "x-model=$xModel" : '' }}
>@if(!$xModel){{ $name ? (is_string($oldVal = old($name, $value)) ? $oldVal : $value) : $value }}@endif</textarea>
