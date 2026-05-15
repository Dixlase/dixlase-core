{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-content-editor.storage-info />

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

@props([
    'storageOptions' => [],
    'storageType' => '',
    'showJsCss' => false,
    // true を指定すると保存形式を変更不可の表示専用バッジとして表示する（編集画面向け）
    'locked' => false,
])

<div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
        {{ __('components/content-editor.storage_section') }}
    </h3>

    <div>
        <x-form-label :text="__('components/content-editor.storage_type_label')" />
        @if ($locked)
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-sm font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                    <i class="fas fa-lock mr-1.5 text-gray-500 dark:text-gray-400"></i>
                    {{ $storageOptions[$storageType]['label'] ?? $storageType }}
                </span>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('components/content-editor.storage_type_locked_help') }}</p>
        @else
            <x-form-select
                name="storage_type"
                :options="collect($storageOptions)->mapWithKeys(fn ($opt, $key) => [$key => $opt['label']])->all()"
                :value="$storageType"
                xModel="storageType"
            />
            <x-form-error name="storage_type" />
        @endif
    </div>

    <div class="mt-2 text-sm space-y-1" x-show="isFileStorage" x-cloak>
        <div>
            <span class="text-gray-500 dark:text-gray-400">{{ __('components/content-editor.storage_file_path') }}</span>
            <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="filePath"></span>
        </div>
        <div class="mt-3 flex items-start gap-2 rounded border border-yellow-300 bg-yellow-50 p-3 text-sm text-yellow-900 dark:border-yellow-700 dark:bg-yellow-900 dark:text-yellow-100">
            <i class="fas fa-triangle-exclamation mt-0.5 shrink-0"></i>
            <span>{{ __('components/content-editor.storage_file_warning') }}</span>
        </div>
        @if ($showJsCss)
            <div x-show="isHtmlEditor && jsFilePath" x-cloak>
                <span class="text-gray-500 dark:text-gray-400">JS:</span>
                <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="jsFilePath"></span>
            </div>
            <div x-show="isHtmlEditor && cssFilePath" x-cloak>
                <span class="text-gray-500 dark:text-gray-400">CSS:</span>
                <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="cssFilePath"></span>
            </div>
        @endif
    </div>
</div>
