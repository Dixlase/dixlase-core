{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-content-editor.preview-tabs />

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
    'editLabel' => __('components/content-editor.preview_tab_edit'),
    'previewLabel' => __('components/content-editor.preview_tab_preview'),
    'editIcon' => 'fas fa-edit',
    'previewIcon' => 'fas fa-eye',
])

<div class="border-b border-gray-200 dark:border-gray-700 mb-6">
    <nav class="-mb-px flex gap-x-6" aria-label="Tabs">
        <button type="button" @click="showEditor()"
            :class="!previewMode
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600 hover:text-gray-700 dark:hover:text-gray-300'"
            class="flex items-center gap-x-2 border-b-2 px-1 py-3 text-sm font-medium whitespace-nowrap transition-colors">
            <i class="{{ $editIcon }}"></i>{{ $editLabel }}
        </button>
        <button type="button" @click="loadPreview()"
            :class="previewMode
                ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600 hover:text-gray-700 dark:hover:text-gray-300'"
            class="flex items-center gap-x-2 border-b-2 px-1 py-3 text-sm font-medium whitespace-nowrap transition-colors">
            <i class="{{ $previewIcon }}"></i>{{ $previewLabel }}
        </button>
    </nav>
</div>
