{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-brand-attribution />

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
    ===========================================================================
    PROTECTED REGION — Dixlase brand attribution
    ===========================================================================

    This component renders the "Powered by Dixlase" attribution shown on
    admin authentication screens (login / password-reset / 2FA / …).
    It is **not** customizable by the operator: it must remain visible
    on every Dixlase-branded surface as long as the platform runs
    unmodified.

    Downstream forks / `custom/` overrides:
      - If you override `resources/views/layouts/auth.blade.php` in a
        `custom/` directory, please KEEP the `<x-brand-attribution />`
        call at the bottom of your override so the platform stays
        recognisable. Removing it in a customer-facing deployment is a
        licence violation under both AGPL v3 and the commercial licence
        (see LICENSE-EXCEPTIONS).

      - This component's internal markup MAY change between releases
        (typography, wordmark image swap, layout). Do not copy the
        markup below into your override — invoke the component and let
        us evolve it centrally.

    Future / v1.x:
      - The text-only wordmark below is intentionally simple for Beta 1.
        A later release will swap in the graphic wordmark via
        `<x-brand-logo />` inline; the callsite here does not change.

    ===========================================================================
--}}

<div class="mt-8 flex items-center justify-center gap-3 text-xs text-gray-500 dark:text-gray-500">
    {{-- Small brand mark to the left of the attribution lines. Inline
         SVG via <x-brand-logo /> so it inherits the parent's
         `currentColor` and stays legible on both light and dark
         grounds. Sized similar to the favicon (~20px) so it reads
         as an attribution glyph, not a hero mark. --}}
    <x-brand-logo class="h-5 w-5 shrink-0" :aria-label="''" />

    <div class="text-left leading-relaxed">
        <div>
            <a href="https://dixlase.org"
               target="_blank"
               rel="noopener noreferrer"
               class="hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                Powered by Dixlase
            </a>
        </div>
        <div>
            &copy; {{ date('Y') }} Dixlase is developed and maintained by exc-D inc.
        </div>
    </div>
</div>
