{{--
This file is part of MySoftware.

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
    class="fixed top-0 z-50 w-full flex items-center h-16 border-b {{ config('appearance.appearance_class.layout.header') }}">
    <!-- プライマリーナビゲーションメニュー -->
    <div class="w-full mx-4 sm:mx-0 lg:px-4 flex items-center h-16">

        <!-- 左：サイドメニュー（スマホ用のみ） -->
        <div class="w-1/3 flex items-center sm:hidden">
            <button @click="openSidebar = true"
                    class="inline-flex items-center justify-start p-2 rounded-md {{ config('appearance.appearance_class.layout.button_hamburger') }} focus:outline-none transition duration-150 ease-in-out">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </div>

        <!-- 中央：ロゴ（スマホでは中央配置, PCでは左寄せ） -->
        <div class="w-1/3 flex justify-center sm:flex-1 sm:justify-start">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center">
                @include('components::application-logo' ,[
                    'class' => config('appearance.appearance_class.layout.logo'),
                    'site_name' => $site_name
                ])
            </a>

            <!-- サイト名（PCのみ表示） -->
            <a href="{{ route('admin.dashboard') }}" class="hidden sm:flex items-center ml-4 text-gray-800 dark:text-white font-semibold">
                {{ $site_name ?? env('APP_NAME') }}
            </a>
        </div>

        <!-- 右：プレビューボタン & ユーザーメニュー -->
        <div class="w-1/3 flex items-center justify-end gap-2">
            <!-- プレビュー（スマホはアイコンのみ） -->
            <a href="{{ url('/') }}" target="_blank"
            class="inline-flex items-center justify-center px-3 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 {{ $transition }}">
                <i class="fa-solid fa-eye text-lg sm:me-2"></i>
                <span class="hidden sm:inline">{{ __('common.preview') }}</span>
            </a>

            <!-- ユーザーメニュー（スマホ用） -->
            <button @click="openUserMenu = true"
                    class="inline-flex items-center justify-center rounded-md sm:hidden {{ config('appearance.appearance_class.layout.button_hamburger') }} focus:outline-none transition duration-150 ease-in-out">
                <i class="fa-solid fa-user-circle text-3xl text-gray-600 dark:text-gray-300"></i>
            </button>

            <!-- ユーザーメニュー（PC用） -->
            <div class="hidden sm:flex sm:items-center relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md focus:outline-none transition ease-in-out duration-150 {{ config('admin.layout.button_admin_user') }}">
                    <i class="fa-solid fa-user-circle text-3xl text-gray-600 dark:text-gray-300"></i>
                    <div class="ml-2">{{ Auth::user()->name }}</div>
                    <i class="fa-solid fa-chevron-down ms-2 text-xs"></i>
                </button>

                <!-- ユーザードロップダウンメニュー -->
                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition
                    class="absolute right-[-1rem] top-full mt-2 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg py-2 z-50">
                    <div class="px-4 flex items-center space-x-3">
                        <i class="fa-solid fa-user-circle text-3xl text-gray-600 dark:text-gray-300"></i>
                        <div>
                            <div class="font-medium text-base text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</div>
                            <div class="font-medium text-sm text-gray-600 dark:text-gray-400">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                        {{ __('admin.profile.heading') }}
                    </a>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                            {{ __('common.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- スライドメニューのバックグラウンド -->
    <div x-show="openSidebar || openUserMenu" @click="openSidebar = !true; openUserMenu = false" class="fixed inset-0 bg-transparent z-10 w-full h-full"></div>

    <!-- 左側スライドインメニュー -->
    <div x-cloak class="fixed inset-y-0 w-64 shadow-lg transform transition-transform duration-300 ease-in-out x-minus-full translate-x-minus-full z-20 text-gray-700 bg-gray-200 dark:text-gray-300 dark:bg-gray-900"
    :class="{ 'translate-x-minus-full': !openSidebar, 'translate-x-0': openSidebar }">

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
        @include('admin::partials.sidebar')
    </div>


    <!-- 右側スライドインメニュー -->
    <div x-cloak class="sm:hidden fixed w-64 h-full shadow-lg transform transition-transform duration-300 ease-in-out z-20 translate-x-100vw text-gray-300 dark:text-gray-700 bg-gray-900 dark:bg-gray-200' }}"
    :class="{ 'translate-x-100vw': !openUserMenu, 'translate-x-100vw-16': openUserMenu,'sm:block': openUserMenu, 'sm:hidden': ! openUserMenu }">

        <!-- 閉じるボタン -->
        <button @click="openUserMenu = false" class="absolute top-4 right-4 p-2 text-gray-700 dark:text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- ユーザーメニュー(スマホ用) -->
        <div class="pt-4 pb-2 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div class="px-4 flex items-center space-x-3">
                <i class="fa-solid fa-user-circle text-3xl text-gray-600 dark:text-gray-300"></i>
                <div>
                    <div class="font-medium text-base text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-600 dark:text-gray-400">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <a href="{{ route('admin.settings.members.profile') }}"
                class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    <i class="fa-solid fa-user me-2"></i> {{ __('admin.settings.members.profile.heading') }}
                </a>

                <!-- ログアウト -->
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <i class="fa-solid fa-sign-out-alt me-2"></i> {{ __('common.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>


</header>
