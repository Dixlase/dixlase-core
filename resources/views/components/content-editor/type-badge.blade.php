{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

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
    'icon' => '',
    'color' => '',
    'label' => '',
    'description' => '',
    'helpText' => __('components/content-editor.editor_type_locked_help'),
])

<div>
    <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
        <i class="{{ $icon }} text-lg" style="color: {{ $color }}"></i>
        <div>
            <div class="font-medium text-gray-900 dark:text-white">{{ $label }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $description }}</div>
        </div>
    </div>
    <x-form-help-text :text="$helpText" />
</div>
