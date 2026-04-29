{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('themes::partials.head')
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 flex flex-col min-h-screen">
    {{-- 管理バー（管理者ログイン時のみ表示） --}}
    <div class="sticky top-0" style="z-index: 9999;">
        <x-ui-maintenance-banner />
        <x-ui-admin-bar />
    </div>

    @include('themes::partials.header')

    <main class="flex-grow container mx-auto px-4 py-8">
        @yield('content')
    </main>

    @include('themes::partials.footer')

    {{-- コアアセットのみ読み込み --}}
    {!! load_front_assets() !!}

    @stack('scripts')
</body>
</html>
