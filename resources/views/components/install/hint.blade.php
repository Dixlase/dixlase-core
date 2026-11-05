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
    Setup wizard hint: one line that is always visible, and an optional
    explanation folded into a panel that opens and closes with an
    animation (the shared `accordion` data plus x-collapse), closed by
    default. What the user must not miss goes in `text`; the background
    goes in `details` (one string per paragraph). The directives use only
    method calls and plain properties, so they work under the strict
    Alpine build.
--}}
@props([
    'text' => '',
    'details' => [],
    'tone' => 'info', // info | warning
])

@php
    $toneClass = $tone === 'warning'
        ? 'text-amber-700 dark:text-amber-300'
        : 'text-gray-600 dark:text-gray-300';
@endphp

<div {{ $attributes->merge(['class' => 'mt-2 text-sm']) }}>
    <p class="{{ $toneClass }}">
        <i class="fas {{ $tone === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info' }} mr-1" aria-hidden="true"></i>{{ $text }}
    </p>
    @if(! empty($details))
        @php($panelId = 'install-hint-'.\Illuminate\Support\Str::random(8))
        <div x-data="accordion" class="mt-1">
            <button type="button"
                class="group inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-200 hover:underline"
                aria-controls="{{ $panelId }}"
                aria-expanded="false"
                :aria-expanded="open"
                @click="toggle()">
                <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-aria-expanded:rotate-180" aria-hidden="true"></i>
                {{ __('install/common.show_details') }}
            </button>
            <div id="{{ $panelId }}" x-show="open" x-collapse x-cloak>
                <div class="mt-2 space-y-2 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-3 text-gray-600 dark:text-gray-300">
                    @foreach($details as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
