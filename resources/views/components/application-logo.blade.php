{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-application-logo />

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

@props([
    'site_name' => '',
    'class' => '',
    'size' => 'h-6 w-6', // デフォルトサイズ
])

{{--
    Renders the SITE'S CURRENT LOGO (distinct from <x-brand-logo />,
    which always renders the Dixlase mark). Beta 1 defaults to the
    Dixlase mark inline via <x-brand-logo /> — a future release adds
    a branch here: if the operator uploaded a custom logo via the
    admin panel, use <img src="{custom path}" /> instead; otherwise
    fall through to the Dixlase mark below. The callsites do not need
    to change when that switch lands — the component boundary absorbs
    the difference.

    Inline SVG (via <x-brand-logo />) so `text-*` classes on the
    surrounding wrapper drive `currentColor` in both light and dark
    themes — the old `<img>` implementation could not inherit CSS
    color, so the logo was invisible on dark grounds and had to
    ship as a two-colour placeholder to compensate.
--}}
<div class="block {{ $size }} fill-current {{ $class }}">
    <x-brand-logo
        class="h-full w-full"
        :aria-label="$site_name"
    />
</div>
