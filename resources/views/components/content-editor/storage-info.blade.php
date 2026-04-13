{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-content-editor.storage-info />

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
])

<div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
        {{ __('components/content-editor.storage_section') }}
    </h3>

    <div>
        <x-form-label :text="__('components/content-editor.storage_type_label')" />
        <x-form-select
            name="storage_type"
            :options="collect($storageOptions)->mapWithKeys(fn ($opt, $key) => [$key => $opt['label']])->all()"
            :value="$storageType"
            xModel="storageType"
        />
        <x-form-error name="storage_type" />
    </div>

    <div class="mt-2 text-sm space-y-1" x-show="isFileStorage" x-cloak>
        <div>
            <span class="text-gray-500 dark:text-gray-400">{{ __('components/content-editor.storage_file_path') }}</span>
            <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="filePath"></span>
        </div>
        <div class="mt-3 rounded border border-amber-300 bg-amber-50 p-3 text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
            <p class="flex items-start gap-2">
                <i class="fas fa-triangle-exclamation mt-0.5"></i>
                <span>{{ __('components/content-editor.storage_file_warning') }}</span>
            </p>
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
