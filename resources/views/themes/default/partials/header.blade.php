{{--
This file is part of Your Software Name.

Copyright (C) 2024 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

<header x-data="{ openSidebar: false, openUserMenu: false}" class="fixed top-0 z-50 w-full flex border-b {{ config('admin.appearance_class.layout.header') }}">
    <!-- プライマリーナビゲーションメニュー -->
    <div class="w-full mx-4 sm:mx-0 sm:px-6 lg:px-8 flex items-center h-16 justify-between sm:justify-start">
        <!-- ロゴ -->
        <div class="flex items-center order-2 sm:order-1">
            <a href="{{ route('admin.dashboard') }}">
                @include('components::application-logo' ,[
                    'class' => config('admin.appearance_class.layout.logo'),
                    'site_name' => $site_name
                ])
            </a>
        </div>

        <!-- サイト名 (PCレイアウトのみ表示) -->
        <div class="hidden space-x-8 sm:-my-px sm:ms-6 sm:flex items-center sm:order-2">
            <a href="{{ route('welcome') }}">
                {{ $site_name ?? env('APP_NAME') }}
            </a>
        </div>

</header>
