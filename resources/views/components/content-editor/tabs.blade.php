{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-content-editor.tabs />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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

<nav class="border-b border-gray-200 dark:border-gray-600">
    <div class="flex -mb-px space-x-4">
        <button type="button" @click="activeTab = 'content'"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                :class="activeTab === 'content'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'">
            <i class="fas fa-code mr-1.5"></i>{{ __('components/content-editor.tab_content') }}
        </button>
        <button type="button" @click="activeTab = 'css'"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                :class="activeTab === 'css'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'">
            <i class="fab fa-css3-alt mr-1.5"></i>{{ __('components/content-editor.tab_css') }}
        </button>
        <button type="button" @click="activeTab = 'js'"
                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                :class="activeTab === 'js'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'">
            <i class="fab fa-js mr-1.5"></i>{{ __('components/content-editor.tab_js') }}
        </button>
    </div>
</nav>
