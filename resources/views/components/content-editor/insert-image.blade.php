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
"Insert image" for <x-form-content-editor>: opens the media selector and
puts a tag for the chosen item at the cursor of the editor's field
(contentEditorInsertImage in resources/src/admin/js/content-editor.js).
Markdown gets ![alt](path); HTML and Blade get <img>. A file that is not
an image becomes a link. Paths are root-relative, so content survives a
change of domain.

@param string $target      id of the textarea to insert into
@param string $selectorId  id of the media selector modal
--}}

@props([
    'target',
    'selectorId',
])

<div x-data="contentEditorInsertImage"
     data-target="{{ $target }}"
     data-selector-id="{{ $selectorId }}"
     class="inline-flex">
    <x-form-button
        type="button"
        variant="secondary"
        size="xs"
        icon="fas fa-image"
        :xClick="'open()'"
        :title="__('components/content-editor.insert_image_help')"
    >
        {{ __('components/content-editor.insert_image') }}
    </x-form-button>
</div>

@push('modals')
    <x-media.selector
        :id="$selectorId"
        :inputId="$selectorId.'_input'"
        :previewId="$selectorId.'_preview'"
    />
@endpush
