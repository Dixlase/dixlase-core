{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-content-editor.scroll-buttons />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact info@dixlase.org).

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

<div x-show="!isHorizontal && previewVisible" x-cloak
     x-data="{ _btnRight: 16 }"
     x-init="
        const updatePos = () => {
            const main = document.getElementById('admin-main-content');
            if (main) {
                _btnRight = Math.max(16, window.innerWidth - main.getBoundingClientRect().right + 16);
            }
        };
        updatePos();
        new ResizeObserver(updatePos).observe(document.getElementById('admin-main-content'));
     "
     class="fixed z-40 flex flex-col gap-2"
     :style="'right: ' + _btnRight + 'px; bottom: 4.5rem;'">
    <button type="button" @click="scrollToEditor()"
            class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition-colors"
            title="{{ __('components/content-editor.scroll_to_editor') }}">
        <i class="fas fa-edit text-sm"></i>
    </button>
    <button type="button" @click="scrollToPreview()"
            class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition-colors"
            title="{{ __('components/content-editor.scroll_to_preview') }}">
        <i class="fas fa-eye text-sm"></i>
    </button>
</div>
