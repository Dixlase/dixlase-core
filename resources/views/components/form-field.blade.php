{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-form-field />

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

{{--
Draws one field from its FieldDefinition: the label, the input its type
draws, the help text and the errors.

  <x-form-field :field="$definition" :value="$value" />
  <x-form-field :field="$definition" :value="$value" name="settings[colour]" />

`name` defaults to fields[<key>]. `value` is the PHP value
(FieldTypeInterface::fromStorage()); after a failed validation the flashed
input wins, as with every other form component.
--}}
@props([
    'field',
    'value' => null,
    'name' => null,
    'id' => null,
])

@php
    $fieldType = app(\App\Services\Fields\FieldTypeRegistry::class)->find($field->type);
    $inputName = $name ?? 'fields['.$field->key.']';
    // fields[title] -> fields.title, the key old() and the error bag use
    $dotName = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $inputName), '.');
    $inputId = $id ?? 'field-'.str_replace('.', '-', $dotName);
    $helpId = $field->help ? $inputId.'-help' : null;
    $currentValue = old($dotName, $value);
@endphp

@if ($fieldType)
    <div {{ $attributes->merge(['class' => 'mb-4']) }}>
        <x-form-label :for="$inputId" :text="$field->label" :required="$field->required" />
        @include($fieldType->view(), ['field' => $field, 'name' => $inputName, 'id' => $inputId, 'value' => $currentValue, 'describedBy' => $helpId])

        @if ($field->help)
            <x-form-help-text :id="$helpId" :text="e($field->help)" />
        @endif

        <x-form-error :name="$dotName" />
    </div>
@endif
