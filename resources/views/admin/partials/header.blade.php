{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

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

<header
    x-cloak
    x-data="{ openSidebar: false, openUserMenu: false }"
    class="fixed top-0 z-50 w-full flex items-center h-16 border-b bg-gray-100 dark:bg-gray-900 border-gray-300 dark:border-gray-700"
    role="banner">
    <!-- Primary Navigation Bar -->
    <nav class="w-full mx-4 sm:mx-0 lg:px-4 flex items-center h-16" role="navigation" aria-label="Primary navigation">

        <!-- Mobile Menu Toggle -->
        <div class="w-1/3 flex items-center sm:hidden">
            <button @click="openSidebar = true"
                    class="inline-flex items-center justify-start p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out"
                    aria-label="Open sidebar menu">
                <i class="fa-solid fa-bars text-xl" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Brand/Logo Section -->
        <div class="w-1/3 flex justify-center sm:flex-1 sm:justify-start">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center" aria-label="Go to dashboard">
                @include('components::application-logo' ,[
                    'class' => 'text-gray-900 dark:text-white',
                    'site_name' => $site_name
                ])
            </a>

            <!-- Site Name (Desktop only) -->
            <a href="{{ route('admin.dashboard') }}" class="hidden sm:flex items-center ml-4 text-gray-800 dark:text-white font-semibold" aria-label="Go to dashboard">
                {{ $site_name ?? env('APP_NAME') }}
            </a>
        </div>

        <!-- Actions & User Menu -->
        <div class="w-1/3 flex items-center justify-end gap-2">
            <!-- Preview Site Link -->
            <a href="{{ url('/') }}" target="_blank"
            class="inline-flex items-center justify-center px-3 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 {{ empty($transitionEnabled) ? '' : 'transition-colors duration-500' }}"
            aria-label="Preview site in new tab">
                <i class="fa-solid fa-eye text-lg sm:me-2" aria-hidden="true"></i>
                <span class="hidden sm:inline">{{ __('common.preview') }}</span>
            </a>

            <!-- Mobile User Menu Toggle -->
            <button @click="openUserMenu = true"
                    class="inline-flex items-center justify-center rounded-md sm:hidden text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out"
                    aria-label="Open user menu">
                <i class="fa-solid fa-user-circle text-3xl text-gray-600 dark:text-gray-300" aria-hidden="true"></i>
            </button>

            <!-- Desktop User Menu -->
            <div class="hidden sm:flex sm:items-center relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md focus:outline-none transition ease-in-out duration-150 text-gray-500 bg-white hover:text-gray-700 dark:text-gray-300 dark:bg-gray-800 dark:hover:text-white"
                    aria-label="User menu"
                    aria-expanded="false">
                    <i class="fa-solid fa-user-circle text-xl text-gray-600 dark:text-gray-300" aria-hidden="true"></i>
                    <div class="ml-2">{{ Auth::user()->name }}</div>
                    <i class="fa-solid fa-chevron-down ms-2 text-xs" aria-hidden="true"></i>
                </button>

                <!-- User Dropdown Menu -->
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition
                    class="absolute right-[-1rem] top-full mt-2 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg py-2 z-50"
                    role="menu"
                    aria-orientation="vertical">
                    <!-- User Info -->
                    <div class="px-4 flex items-center space-x-3" role="none">
                        <i class="fa-solid fa-user-circle text-3xl text-gray-600 dark:text-gray-300" aria-hidden="true"></i>
                        <div>
                            <div class="font-medium text-base text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</div>
                            <div class="font-medium text-sm text-gray-600 dark:text-gray-400">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <!-- Menu Items -->
                    <a href="{{ route('admin.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 transition" role="menuitem">
                        {{ __('admin.profile.heading') }}
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}" role="none">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 transition" role="menuitem">
                            {{ __('common.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div x-show="openSidebar || openUserMenu" @click="openSidebar = false; openUserMenu = false" class="fixed inset-0 bg-black bg-opacity-50 z-10 w-full h-full" aria-hidden="true"></div>

    <!-- 左側スライドインメニュー -->
    <div x-cloak class="fixed inset-y-0 w-64 shadow-lg transform transition-transform duration-300 ease-in-out x-minus-full translate-x-minus-full z-20 text-gray-700 bg-gray-200 dark:text-gray-300 dark:bg-gray-900 flex flex-col"
    :class="{ 'translate-x-minus-full': !openSidebar, 'translate-x-0': openSidebar }">

        <!-- 固定ヘッダー部分 -->
        <div class="flex-shrink-0">
            <button @click="openSidebar = !openSidebar" class="p-4 text-gray-700 dark:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <!-- スマホレイアウト用のサイト名 -->
            <div class="px-4 py-2 border-b border-gray-300 dark:border-gray-700">
                <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold">
                    {{ $site_name ?? env('APP_NAME') }}
                </a>
            </div>
        </div>

        <!-- スクロール可能なメニュー部分 -->
        <div class="flex-1 overflow-y-auto">
            @include('admin::partials.sidebar')
        </div>
    </div>


    <!-- 右側スライドインメニュー -->
    <div x-cloak class="sm:hidden fixed right-0 top-0 w-64 h-full shadow-lg transform transition-transform duration-300 ease-in-out z-20 bg-white dark:bg-gray-800"
    :class="{ 'translate-x-full': !openUserMenu, 'translate-x-0': openUserMenu }">

        <!-- 閉じるボタン -->
        <button @click="openUserMenu = false" class="absolute top-4 right-4 p-2 text-gray-700 dark:text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- ユーザーメニュー(スマホ用) -->
        <div class="pt-16 px-4">
            <!-- ユーザー情報 -->
            <div class="flex items-center space-x-3 mb-6">
                <i class="fa-solid fa-user-circle text-3xl text-gray-600 dark:text-gray-300"></i>
                <div>
                    <div class="font-medium text-base text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-600 dark:text-gray-400">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <!-- メニュー項目 -->
            <div class="space-y-2">
                <a href="{{ route('admin.profile') }}"
                class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition">
                    <i class="fa-solid fa-user me-3"></i> {{ __('admin.profile.heading') }}
                </a>

                <!-- ログアウト -->
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition text-left">
                        <i class="fa-solid fa-sign-out-alt me-3"></i> {{ __('common.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>


</header>
