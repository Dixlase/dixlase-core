{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-content-editor.sidebar-section>

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
A collapsible section of a content editor's right sidebar.

The title row opens and closes it. While closed it can show a short
summary of what it holds ("Published", "1 category, 4 tags"), so the
state reads without opening it. A section holding a field with a
validation error opens, whatever its initial or remembered state, so an
error is never hidden. With a rememberKey, the open/closed state is kept
per viewer in the browser; without one, every load starts from `open`.

title        Section heading
icon         Font Awesome classes for the heading icon (optional)
open         Initial state when nothing is remembered
summary      Text shown next to the title while closed (optional)
errorFields  Field names (wildcards allowed, e.g. `tags.*`); an error on
             any of them opens the section
rememberKey  Key the open/closed state is remembered under (optional)
divider      Draw the rule above the section
--}}

@props([
    'title',
    'icon' => null,
    'open' => false,
    'summary' => null,
    'errorFields' => [],
    'rememberKey' => null,
    'divider' => true,
])

@php
    $errorBag = $errors ?? null;
    $hasError = $errorBag !== null && collect((array) $errorFields)
        ->contains(fn (string $field): bool => $errorBag->get($field) !== []);
    $startsOpen = $hasError || (bool) $open;
    $panelId = 'sidebar-section-'.\Illuminate\Support\Str::random(8);
    $summaryText = is_string($summary) ? trim($summary) : '';
@endphp

<div {{ $attributes->class([
        'border-t border-gray-200 dark:border-gray-700 pt-4' => $divider,
    ]) }}
     x-data="contentEditorSidebarSection"
     data-open="{{ $startsOpen ? '1' : '0' }}"
     data-force-open="{{ $hasError ? '1' : '0' }}"
     @if (filled($rememberKey)) data-remember-key="{{ $rememberKey }}" @endif
>
    <button type="button"
            @click="toggle"
            :aria-expanded="expanded()"
            aria-expanded="{{ $startsOpen ? 'true' : 'false' }}"
            aria-controls="{{ $panelId }}"
            class="w-full flex items-center justify-between gap-2 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
        <span class="flex min-w-0 items-center gap-1.5">
            @if ($icon)
                <i class="{{ $icon }} w-4 text-center text-gray-500 dark:text-gray-400" aria-hidden="true"></i>
            @endif
            <span class="truncate">{{ $title }}</span>
            @if ($hasError)
                <i class="fas fa-circle-exclamation text-red-600 dark:text-red-400" aria-hidden="true"></i>
            @endif
        </span>
        <span class="flex shrink-0 items-center gap-2">
            @if ($summaryText !== '')
                <span x-show="! open"
                      @if ($startsOpen) style="display: none" @endif
                      class="max-w-[11rem] truncate text-xs font-normal text-gray-500 dark:text-gray-400">{{ $summaryText }}</span>
            @endif
            <i class="fas fa-chevron-down text-xs text-gray-500 transition-transform dark:text-gray-400"
               :class="chevronClass()" aria-hidden="true"></i>
        </span>
    </button>
    <div id="{{ $panelId }}"
         x-show="open"
         x-collapse
         @unless ($startsOpen) style="display: none" @endunless
    >
        <div class="pt-3">
            {{ $slot }}
        </div>
    </div>
</div>
