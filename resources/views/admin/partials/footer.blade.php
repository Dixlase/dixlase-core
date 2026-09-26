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

<footer id="page-footer"
        class="lg:ml-64 border-t py-4 text-center text-xs bg-white text-gray-500 border-gray-200 dark:bg-black dark:text-gray-400 dark:border-gray-700 transition-[margin] duration-300 ease-in-out"
        :class="{ 'lg:mr-80': rightSidebarActive && !rightSidebarCollapsed }">
    {{-- Layout switches at the same `lg` breakpoint the admin sidebar uses.
         Below `lg` the footer stacks into three clean rows — product/version,
         copyright, and the license/source link pair — so the middot `·`
         separators between them are hidden (they belong to the one-line form,
         not to visual stacking). `AGPLv3 · ソース` stays a single inline group
         even on mobile so the separator between them is meaningful. From `lg`
         upward everything sits on one line with `·` separators visible. --}}
    <div class="flex flex-col items-center gap-y-1 lg:flex-row lg:flex-wrap lg:justify-center lg:gap-x-2">
        <span class="whitespace-nowrap">
            <span class="font-semibold">{{ config('app.software_name', 'Dixlase') }}</span>
            v{{ $coreVersion ?? \App\Support\CoreVersion::current() }}
        </span>
        <span aria-hidden="true" class="hidden lg:inline">&middot;</span>
        <span class="whitespace-nowrap">&copy; {{ date('Y') }} exc-D inc. and Dixlase contributors</span>
        <span aria-hidden="true" class="hidden lg:inline">&middot;</span>
        <span class="inline-flex items-center gap-x-2 whitespace-nowrap">
            <a href="{{ config('dixlase.license_url', 'https://www.gnu.org/licenses/agpl-3.0.html') }}" target="_blank" rel="noopener" class="underline hover:text-gray-700 dark:hover:text-gray-300">
                {{ config('dixlase.license_label', 'AGPLv3') }}
            </a>
            <span aria-hidden="true">&middot;</span>
            <a href="{{ config('dixlase.source_url', 'https://github.com/Dixlase/dixlase-core') }}" target="_blank" rel="noopener" class="underline hover:text-gray-700 dark:hover:text-gray-300" title="{{ __('common.source_code_title') }}">
                {{ __('common.source_code') }}
            </a>
        </span>
    </div>
</footer>