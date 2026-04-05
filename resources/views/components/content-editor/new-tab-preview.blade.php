{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-content-editor.new-tab-preview />

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
    'url' => '',
    'label' => null,
    'help' => null,
    'icon' => 'fas fa-external-link-alt',
])

@if($url)
<div>
    <x-form-button
        variant="tertiary"
        :icon="$icon"
        :label="$label ?? __('components/content-editor.new_tab_preview')"
        class="w-full"
        x-click="window.Dixlase.newTabPreview('{{ $url }}', $el)"
    />
    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        <i class="fas fa-info-circle mr-1"></i>
        {{ $help ?? __('components/content-editor.new_tab_preview_help') }}
    </p>
</div>
@endif
