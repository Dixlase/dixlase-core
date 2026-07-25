{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-brand-logo />

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
    The Dixlase brand mark — the "D+S" ligature monogram. **Immutable
    brand asset**: distinct from <x-application-logo />, which shows the
    site's currently-configured logo (user-overridable in a future
    release). This component always renders the Dixlase mark, and is
    intended for surfaces where the platform identity must be preserved
    regardless of the operator's branding — the auth-screen attribution
    footer, the future admin-bar Dixlase slot, etc.

    Inline SVG (not <img>) so `fill="currentColor"` inherits the parent
    CSS color, which lets the same component drop into both light and
    dark grounds without a second file.

    Props:
    - $class: extra classes for the outer <svg> (sizing lives here, e.g.
             `h-8 w-8` or `w-32 h-auto`).
    - $ariaLabel: accessible name. Defaults to "Dixlase" — override only
                  when the surrounding text already names the brand and
                  the mark should be treated as decorative (pass "" to
                  render as aria-hidden).
--}}

@props([
    'class' => 'h-8 w-auto',
    'ariaLabel' => 'Dixlase',
])

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 1024 1024"
    class="{{ $class }}"
    @if($ariaLabel === '')
        aria-hidden="true"
    @else
        role="img"
        aria-label="{{ $ariaLabel }}"
    @endif
>
    <path fill="currentColor" d="M511.25,111.86c-121.04,0-210.47,56.76-210.47,152.85,0,65.31,70.22,113.06,114.18,155.62,44.58,43.2,97.18,113.95,97.18,244.52,0,93.65-98.8,239.71-283.53,239.71V111.86h-116.62v800.26h116.62c207.27,0,400.15-80.45,400.15-247.3,0-119.33-134.87-182.19-208.62-250.1-51.25-47.19-74.78-108.01-74.78-150.01,0-81.77,80.65-145.23,165.85-145.23,117.41,0,284.16,131.11,284.16,392.86,0,206.91-121.21,389.39-283.27,399.82,225.02,0,399.88-183.74,399.88-399.82,0-233.44-190.6-400.45-400.78-400.45l.03-.03Z"/>
</svg>
