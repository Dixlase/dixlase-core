{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-ui-front-banner-stack />

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
Front-side sticky banner stack mirroring the admin layout's
#admin-banner-stack: groups the maintenance banner, the demo-mode
banner, plugin-pushed front banners, and the admin bar into one sticky
container that sits above the theme header, so system-level notices
never get covered by theme navigation.

Plugins inject notices via:

    @push('front-banners')
        <div>...your banner...</div>
    @endpush

Themes mount this once at the top of <body>, before the theme's own
header partial:

    <x-ui-front-banner-stack />
    @include('themes::partials.header')
    ...

Sticky (not fixed) is intentional: the stack occupies its own height,
so the theme header naturally flows below it without needing the
ResizeObserver / --admin-banner-offset dance the admin layout uses
to compensate for its fixed admin bar.
--}}

<div id="front-banner-stack" class="sticky top-0 z-[9999] flex flex-col">
    <x-ui-maintenance-banner />
    <x-ui-admin-demo-banner />
    @stack('front-banners')
    <x-ui-admin-bar />
</div>
