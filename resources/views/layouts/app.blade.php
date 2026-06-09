{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/src/common/css/tailwind.css', 'resources/src/common/js/app.js', 'resources/src/common/scss/style.scss'], 'assets/build')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            {{--@include('layouts.navigation')--}}
            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            {{-- AGPL §13: Make public the source code location for the running instance --}}
            <footer class="py-4 text-center text-xs text-gray-500">
                <span class="font-semibold">{{ config('app.software_name', 'Dixlase') }}</span>
                &middot;
                <a href="{{ config('dixlase.license_url', 'https://www.gnu.org/licenses/agpl-3.0.html') }}" target="_blank" rel="noopener" class="underline hover:text-gray-700">
                    {{ config('dixlase.license_label', 'AGPLv3') }}
                </a>
                &middot;
                <a href="{{ config('dixlase.source_url', 'https://github.com/Dixlase/dixlase-core') }}" target="_blank" rel="noopener" class="underline hover:text-gray-700">
                    {{ __('common.source_code') }}
                </a>
            </footer>
        </div>

    </body>
</html>
