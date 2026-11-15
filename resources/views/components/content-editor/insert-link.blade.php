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
"Insert link" for <x-form-content-editor> (#1032): asks for a URL, the
text and whether to open a new window, then writes a Markdown or HTML link
over the field's selection or at its cursor (contentEditorInsertLink and
contentEditorLinkForm in resources/src/admin/js/content-editor.js). A
selected image is linked as it is. Cmd/Ctrl+K in the field opens it too.
Shown while the Markdown or HTML editor is selected.

@param string $target   id of the textarea to insert into
@param string $modalId  id of the modal that asks for the link
--}}

@props([
    'target',
    'modalId',
])

<div x-data="contentEditorInsertLink"
     x-show="acceptsLinks"
     data-target="{{ $target }}"
     data-modal-id="{{ $modalId }}"
     class="inline-flex">
    <x-form-button
        type="button"
        variant="secondary"
        size="xs"
        icon="fas fa-link"
        :xClick="'open()'"
        :title="__('components/content-editor.insert_link_help')"
    >
        {{ __('components/content-editor.insert_link') }}
    </x-form-button>
</div>

@push('modals')
    <x-ui-modal
        :id="$modalId"
        :title="__('components/content-editor.link.title')"
        message=""
        iconType="info"
        :hideActions="true"
    >
        <div x-data="contentEditorLinkForm"
             data-modal-id="{{ $modalId }}"
             data-invalid="{{ __('components/content-editor.link.url_invalid') }}"
             class="mt-4 space-y-4 text-left">
            <div>
                <x-form-label :for="$modalId.'_url'" :text="__('components/content-editor.link.url')" />
                <x-form-text
                    :id="$modalId.'_url'"
                    :name="$modalId.'_url'"
                    type="text"
                    inputmode="url"
                    placeholder="https://"
                    xModel="url"
                    :ariaDescribedby="$modalId.'_url_help'"
                    @keydown.enter.prevent="insert()"
                />
                <p x-show="error" x-text="error" x-cloak class="mt-1 text-sm text-red-600 dark:text-red-400"></p>
                <p id="{{ $modalId }}_url_help" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('components/content-editor.link.url_help') }}
                </p>
            </div>
            <div x-show="showText()">
                <x-form-label :for="$modalId.'_text'" :text="__('components/content-editor.link.text')" />
                <x-form-text
                    :id="$modalId.'_text'"
                    :name="$modalId.'_text'"
                    xModel="text"
                    :ariaDescribedby="$modalId.'_text_help'"
                    @keydown.enter.prevent="insert()"
                />
                <p id="{{ $modalId }}_text_help" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('components/content-editor.link.text_help') }}
                </p>
            </div>
            <p x-show="imageSelected" x-cloak class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('components/content-editor.link.image_selected') }}
            </p>
            <x-form-toggle
                :id="$modalId.'_new_window'"
                :name="$modalId.'_new_window'"
                :label="__('components/content-editor.link.new_window')"
                xModel="newWindow"
            />
            <div class="flex justify-end gap-2">
                <x-form-button type="button" variant="secondary" icon="fas fa-times" :xClick="'cancel()'">
                    {{ __('common.cancel') }}
                </x-form-button>
                <x-form-button type="button" variant="primary" icon="fas fa-check" :xClick="'insert()'">
                    {{ __('components/content-editor.link.insert') }}
                </x-form-button>
            </div>
        </div>
    </x-ui-modal>
@endpush
