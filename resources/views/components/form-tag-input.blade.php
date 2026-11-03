{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-form-tag-input>

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
A tag input with suggestions (#841), for any screen that tags something:
typing narrows the existing tags, shown with their counts, and "create «…»"
comes one step below them, so an existing tag is the first thing offered.
Behaviour lives in tagInput (resources/src/components/js/tag-input.js).

$tagsUrl answers a GET with { "tags": [{ "id", "name", "count" }] }; it is
fetched the first time the field gets focus.

Each chosen tag becomes a hidden input named $name, carrying the tag's
name, or its id when valueField is "id" (an id exists only for tags that
already exist, so allowCreate must be false then). $form puts the hidden
inputs in a form the component sits outside of, as in a modal. $inline
puts the option list in the flow instead of floating it, for a modal,
whose scrolling body would clip a floating list.

$commitTyped suits a free-text tag field: Space, and leaving the field,
add what was typed as it is (an existing tag of that name, else a new
one), instead of only the highlighted option. $stripHash drops a leading
"#", so "#news" and "news" are the same tag.

Dispatch "tag-input-reset" on the root element to empty it.
--}}

@props([
    'id',
    'tagsUrl',
    'name' => 'tags[]',
    'selected' => [],
    'allowCreate' => true,
    'valueField' => 'name',
    'form' => null,
    'inline' => false,
    'label' => null,
    'placeholder' => null,
    'maxlength' => null,
    'commitTyped' => false,
    'stripHash' => false,
    'createLabel' => null,
    'removeLabel' => null,
    'noMatch' => null,
])

<div id="{{ $id }}"
     x-data="tagInput"
     data-tags-url="{{ $tagsUrl }}"
     data-selected="{{ json_encode(array_values($selected)) }}"
     data-allow-create="{{ $allowCreate ? '1' : '0' }}"
     data-value-field="{{ $valueField === 'id' ? 'id' : 'name' }}"
     data-name="{{ $name }}"
     data-commit-typed="{{ $commitTyped ? '1' : '0' }}"
     data-strip-hash="{{ $stripHash ? '1' : '0' }}"
     @if($form) data-form="{{ $form }}" @endif
     data-create-label="{{ $createLabel ?? __('components/form.tag_input.create', ['name' => ':name']) }}"
     data-remove-label="{{ $removeLabel ?? __('components/form.tag_input.remove', ['name' => ':name']) }}"
     {{ $attributes->merge(['class' => 'relative']) }}>
    @if($label)
        <label for="{{ $id }}-field" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $label }}</label>
    @endif

    <div class="flex flex-wrap items-center gap-1.5 w-full px-2 py-1.5 border border-gray-300 rounded-lg bg-white dark:border-gray-600 dark:bg-gray-700 focus-within:ring-2 focus-within:ring-blue-500">
        <template x-for="item in selected" :key="item.key">
            <span class="inline-flex items-center gap-1 max-w-full rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-100 pl-2.5 pr-1 py-0.5 text-sm">
                <span class="truncate" x-text="item.name"></span>
                <button type="button"
                        @click="remove(item)"
                        :aria-label="removeLabel(item)"
                        :title="removeLabel(item)"
                        class="inline-flex items-center justify-center w-5 h-5 rounded-full hover:bg-blue-200 dark:hover:bg-blue-800">
                    <i class="fas fa-times text-xs" aria-hidden="true"></i>
                </button>
                <input type="hidden" :name="inputName()" :value="submittedValue(item)" :form="formId()">
            </span>
        </template>

        <input type="text"
               id="{{ $id }}-field"
               x-ref="field"
               x-model="query"
               @focus="focused()"
               @blur="blurred()"
               @input="typed()"
               @keydown="keydown($event)"
               role="combobox"
               aria-autocomplete="list"
               aria-controls="{{ $id }}-listbox"
               :aria-expanded="showList() ? 'true' : 'false'"
               :aria-activedescendant="activeDescendant()"
               @if($maxlength) maxlength="{{ $maxlength }}" @endif
               autocomplete="off"
               placeholder="{{ $placeholder ?? __('components/form.tag_input.placeholder') }}"
               class="flex-1 min-w-[8rem] border-0 bg-transparent p-1 text-sm text-gray-900 dark:text-white focus:ring-0 focus:outline-none">
    </div>

    <ul id="{{ $id }}-listbox"
        role="listbox"
        x-show="showList()"
        x-cloak
        class="{{ $inline ? 'relative' : 'absolute z-20' }} mt-1 w-full max-h-60 overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-600 dark:bg-gray-800">
        <template x-for="(option, index) in options()" :key="option.key">
            <li :id="optionId(index)"
                role="option"
                :aria-selected="isActive(index) ? 'true' : 'false'"
                @mousedown.prevent="choose(option)"
                :class="optionClass(index)"
                class="flex items-center justify-between gap-3 px-3 py-1.5 text-sm cursor-pointer">
                <span class="truncate">
                    <i x-show="option.create" class="fas fa-plus mr-1.5 text-xs" aria-hidden="true"></i>
                    <span x-text="optionLabel(option)"></span>
                </span>
                <span x-show="!option.create" class="shrink-0 text-xs text-gray-500 dark:text-gray-400" x-text="optionCount(option)"></span>
            </li>
        </template>
    </ul>

    <p x-show="showEmpty()" x-cloak class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ $noMatch ?? __('components/form.tag_input.no_match') }}
    </p>
</div>
