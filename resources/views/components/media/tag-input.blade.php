{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

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
Tag input for the media library (#766): pick existing tags first, create a
new one only after them. Behaviour lives in mediaTagInput
(resources/src/components/media/js/tag-input.js).

Each chosen tag becomes a hidden input named $name, carrying the tag's
name, or its id when valueField is "id" (an id exists only for tags that
already exist, so allowCreate must be false then). $form puts the hidden
inputs in a form the component sits outside of, as in a modal. $inline
puts the option list in the flow instead of floating it, for a modal,
whose scrolling body would clip a floating list.
--}}

@props([
    'id',
    'name' => 'tags[]',
    'selected' => [],
    'allowCreate' => true,
    'valueField' => 'name',
    'form' => null,
    'inline' => false,
    'label' => null,
    'placeholder' => null,
])

<div id="{{ $id }}"
     x-data="mediaTagInput"
     data-tags-url="{{ route('admin.media.tags.list') }}"
     data-selected="{{ json_encode(array_values($selected)) }}"
     data-allow-create="{{ $allowCreate ? '1' : '0' }}"
     data-value-field="{{ $valueField === 'id' ? 'id' : 'name' }}"
     data-name="{{ $name }}"
     @if($form) data-form="{{ $form }}" @endif
     data-create-label="{{ __('admin/media/tags.input.create', ['name' => ':name']) }}"
     data-remove-label="{{ __('admin/media/tags.input.remove', ['name' => ':name']) }}"
     class="relative">
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
               maxlength="{{ \App\Models\MediaTag::MAX_NAME_LENGTH }}"
               autocomplete="off"
               placeholder="{{ $placeholder ?? __('admin/media/tags.input.placeholder') }}"
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
        {{ __('admin/media/tags.input.no_match') }}
    </p>
</div>
