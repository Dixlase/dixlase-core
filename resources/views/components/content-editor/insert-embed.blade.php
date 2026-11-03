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
"Insert embed" for <x-form-content-editor>: asks for a service and a URL,
then puts `@[service](URL)` on a line of its own at the cursor of the
editor's field (contentEditorInsertEmbed and contentEditorEmbedForm in
resources/src/admin/js/content-editor.js). Core renders the line through
ContentEmbedExtension (#731). Markdown only, so the button shows only while
the Markdown editor is selected.

@param string $target   id of the textarea to insert into
@param string $modalId  id of the modal that asks for the URL
--}}

@props([
    'target',
    'modalId',
])

<div x-data="contentEditorInsertEmbed"
     x-show="isEditorSelected('markdown')"
     data-modal-id="{{ $modalId }}"
     class="inline-flex">
    <x-form-button
        type="button"
        variant="secondary"
        size="xs"
        icon="fas fa-photo-film"
        :xClick="'open()'"
        :title="__('components/content-editor.insert_embed_help')"
    >
        {{ __('components/content-editor.insert_embed') }}
    </x-form-button>
</div>

@push('modals')
    <x-ui-modal
        :id="$modalId"
        :title="__('components/content-editor.embed.title')"
        message=""
        iconType="info"
        :hideActions="true"
    >
        <div x-data="contentEditorEmbedForm"
             data-target="{{ $target }}"
             data-modal-id="{{ $modalId }}"
             data-invalid="{{ __('components/content-editor.embed.url_invalid') }}"
             class="mt-4 space-y-4 text-left">
            <div>
                <x-form-label :for="$modalId.'_service'" :text="__('components/content-editor.embed.service')" />
                <x-form-select
                    :id="$modalId.'_service'"
                    :options="['youtube' => 'YouTube', 'x' => 'X', 'github' => 'GitHub']"
                    value="youtube"
                    xModel="service"
                />
            </div>
            <div>
                <x-form-label :for="$modalId.'_url'" :text="__('components/content-editor.embed.url')" />
                <x-form-text
                    :id="$modalId.'_url'"
                    :name="$modalId.'_url'"
                    type="url"
                    placeholder="https://"
                    xModel="url"
                    :ariaDescribedby="$modalId.'_url_help'"
                    @keydown.enter.prevent="insert()"
                />
                <p x-show="error" x-text="error" x-cloak class="mt-1 text-sm text-red-600 dark:text-red-400"></p>
                <p id="{{ $modalId }}_url_help" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('components/content-editor.embed.url_help') }}
                </p>
            </div>
            <div class="flex justify-end gap-2">
                <x-form-button type="button" variant="secondary" icon="fas fa-times" :xClick="'cancel()'">
                    {{ __('common.cancel') }}
                </x-form-button>
                <x-form-button type="button" variant="primary" icon="fas fa-check" :xClick="'insert()'">
                    {{ __('components/content-editor.embed.insert') }}
                </x-form-button>
            </div>
        </div>
    </x-ui-modal>
@endpush
