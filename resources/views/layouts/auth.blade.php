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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ config('app.name') }}</title>

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
<body class="bg-gray-100 dark:bg-black flex items-center justify-center min-h-screen transition-colors duration-300">
    {{-- Demo-mode banner (account + front/admin URLs). Fixed to the top so it
         does not disturb the vertically centred auth card. --}}
    @if(config('dixlase.demo_mode'))
        <div class="fixed top-0 inset-x-0 z-50">
            <x-ui-admin-demo-banner />
        </div>
    @endif
    <div class="flex flex-col items-center w-full max-w-lg min-w-[400px]">

        <!-- ロゴ -->
        <img src="{{ asset('assets/images/logo.svg') }}" alt="{{ config('app.name') }}" class="w-32 h-auto mx-auto mb-4">

        <div class="bg-white dark:bg-gray-900 shadow-lg rounded-lg p-12 w-full mb-4 transition-colors duration-300">
            @hasSection('icon')
                @php
                    $iconSection = trim(View::yieldContent('icon'));
                    // アイコンが配列形式（JSON）かチェック
                    $icons = json_decode($iconSection, true);
                    if (!is_array($icons)) {
                        // 配列でない場合は単一アイコンとして扱う
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
    </div>
    
    @stack('scripts')

</body>
</html>
