{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-admin.mode-readonly-banner />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

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

@props([
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700']) }}>
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0 mt-0.5">
            <i class="fas fa-lock text-gray-400 dark:text-gray-500"></i>
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $message ?? __('components/admin/mode-readonly-banner.message') }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('components/admin/mode-readonly-banner.hint') }}
            </p>
        </div>
        <div class="flex-shrink-0">
            <a href="{{ route('admin.settings.base.mode') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-md hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                <i class="fas fa-cogs"></i>
                {{ __('components/admin/mode-readonly-banner.switch_mode') }}
            </a>
        </div>
    </div>
</div>
