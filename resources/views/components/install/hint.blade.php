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
    explanation folded into a native <details> element, closed by default.
    What the user must not miss goes in `text`; the background goes in
    `details` (one string per paragraph). <details> needs no JavaScript,
    so it is CSP-safe and works under the strict Alpine build.
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
        <details class="mt-1">
            <summary class="cursor-pointer text-indigo-600 dark:text-indigo-400 hover:underline">{{ __('install/common.show_details') }}</summary>
            <div class="mt-2 space-y-2 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 p-3 text-gray-600 dark:text-gray-300">
                @foreach($details as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        </details>
    @endif
</div>
