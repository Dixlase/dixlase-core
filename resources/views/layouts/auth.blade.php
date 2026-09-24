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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @pageTitle

    {{-- Favicon links. Ordered SVG-first so modern browsers pick the
         vector; PNG fallbacks for browsers that ignore SVG icons. The
         paths are stable across releases; only the image files at these
         paths may change (e.g. when the operator swaps their favicon
         in a future release that adds that feature). --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon-16.png') }}">

    {{-- Prevent FOUC: Dark mode + Alpine.js x-cloak (executed synchronously) --}}
    <style>[x-cloak]{display:none!important;}</style>
    <script @cspNonce>
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <!-- Scripts -->
    {!! load_auth_assets() !!}
</head>
<body class="bg-gray-100 dark:bg-black flex flex-col items-center min-h-screen transition-colors duration-300">
    {{-- Demo-mode banner (account + front/admin URLs). Rendered in-flow (not
         fixed) so it takes layout height and pushes the auth card down instead of
         overlapping the logo on short mobile viewports; the card below still
         centres in the remaining space via `my-auto`. --}}
    @if(config('dixlase.demo_mode'))
        <div class="w-full">
            <x-ui-admin-demo-banner />
        </div>
    @endif
    <div class="flex flex-col items-center w-full max-w-lg min-w-[400px] my-auto">

        {{-- Site logo. Wrapped so the login card gets the current
             theme's ink color via `text-gray-900 dark:text-white`,
             which the inline SVG inside <x-application-logo /> reads
             through `currentColor`. --}}
        <div class="text-gray-900 dark:text-white mb-4">
            <x-application-logo
                size="w-32 h-auto"
                class="mx-auto"
                :site_name="config('app.name')"
            />
        </div>

        <div class="bg-white dark:bg-gray-900 shadow-lg rounded-lg p-12 w-full mb-4 transition-colors duration-300">
            @hasSection('icon')
                @php
                    $iconSection = trim(View::yieldContent('icon'));
                    // Check whether the icon section holds an array (JSON)
                    $icons = json_decode($iconSection, true);
                    if (!is_array($icons)) {
                        // Not an array, so treat it as a single icon
                        $icons = [$iconSection];
                    }
                @endphp
                <div class="mx-auto flex items-center justify-center gap-2 mb-6">
                    @foreach($icons as $icon)
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="{{ $icon }} text-2xl text-blue-600 dark:text-blue-400"></i>
                        </div>
                    @endforeach
                </div>
            @endif
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 text-center">@yield('header')</h1>
            <div class="text-gray-600 dark:text-gray-300 mb-6 text-center">@yield('description')</div>
            <x-ui-flash-message />
            @yield('content')
        </div>

        @hasSection('back_link')
            <div class="mt-4 text-center">
                @yield('back_link')
            </div>
        @endif

        {{-- PROTECTED REGION: Dixlase brand attribution. See
             components/brand-attribution.blade.php for the license /
             override policy. Do not remove without reading that file
             first. --}}
        <x-brand-attribution />
    </div>

    @stack('scripts')

</body>
</html>
