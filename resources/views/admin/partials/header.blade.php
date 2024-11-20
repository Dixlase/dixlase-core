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

@php
// テーマの設定を取得
$theme = config('admin.theme');
$isDark = $theme === 'dark';

/**
 * サブメニューが存在するかをチェックする関数
 *
 * @param array $item メニュー項目
 * @return bool サブメニューが存在する場合は true
 */
function hasSubmenu(array $item): bool
{
    return isset($item['children']) && is_array($item['children']);
}
@endphp

<header x-data="{ openSidebar: false, openUserMenu: false}" class="{{ config('admin.theme_class.' . $theme . '.header') }} w-full flex">
    <!-- プライマリーナビゲーションメニュー -->
    <div class="w-full mx-4 sm:mx-0 sm:px-6 lg:px-8 flex items-center h-16 justify-between sm:justify-start">
        <!-- ロゴ -->
        <div class="flex items-center order-2 sm:order-1">
            <a href="{{ route('admin.dashboard') }}">
                <x-application-logo class="block h-9 w-auto fill-current {{ config('admin.theme_class.' . $theme . '.logo') }}" />
            </a>
        </div>

        <!-- サイト名 (PCレイアウトのみ表示) -->
        <div class="hidden space-x-8 sm:-my-px sm:ms-6 sm:flex items-center sm:order-2">
            <a href="{{ route('admin.dashboard') }}">
                {{ env('APP_NAME') }}
            </a>
        </div>
        <!-- サイドメニューのハンバーガー (スマホ用) -->
        <button @click="openSidebar = true, openMenu= true" class="sm:hidden inline-flex items-center justify-center p-2 rounded-md order-1 {{ config('admin.theme_class.' . $theme . '.button_hamburger') }} focus:outline-none transition duration-150 ease-in-out">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path :class="{'hidden': openSidebar, 'inline-flex': ! openSidebar }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! openSidebar, 'inline-flex': openSidebar }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- ユーザーメニューのハンバーガー (スマホ用) -->
        <button @click="openUserMenu = true, openMenu= true" class="inline-flex items-center justify-center rounded-md order-2 sm:hidden {{ config('admin.theme_class.' . $theme . '.button_hamburger') }} focus:outline-none transition duration-150 ease-in-out">
            <svg class="h-8 w-8" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18c0-2.21 1.79-4 4-4h4c2.21 0 4 1.79 4 4v1H6v-1z" />
            </svg>
        </button>
    </div>

    <!-- ユーザードロップダウンメニュー -->
    <div class="hidden w-36 sm:flex sm:items-center sm:ms-6 ">
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md {{ config('admin.theme_class.' . $theme . '.button_admin_user') }} focus:outline-none transition ease-in-out duration-150">
                    <div>{{ Auth::user()->name }}</div>

                    <div class="ms-1">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4">
                    <div class="font-medium text-base {{ config('admin.theme_class.' . $theme . '.option_2') }}">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm {{ config('admin.theme_class.' . $theme . '.option_3') }}">{{ Auth::user()->email }}</div>
                </div>

                <x-dropdown-link :href="route('mypage.profile.edit')">
                    {{ __('Profile') }}
                </x-dropdown-link>

                <!-- ログアウト -->
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf

                    <x-dropdown-link :href="route('admin.logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>

    <!-- スライドメニューのバックグラウンド -->
    <div x-show="openSidebar || openUserMenu" @click="openSidebar = !true; openUserMenu = false" class="fixed inset-0 bg-transparent z-10 w-full h-full"></div>

    <!-- 左側スライドインメニュー -->
    <div class="fixed inset-y-0 w-64 shadow-lg transform transition-transform duration-300 ease-in-out translate-x-minus-full z-20 {{ $isDark ? 'text-gray-300 bg-gray-900' : 'text-gray-700 bg-gray-200' }}"
    :class="{ 'translate-x-minus-full': !openSidebar, 'translate-x-0': openSidebar }">
        <button @click="openSidebar = !openSidebar" class="p-4 {{ $isDark ? "text-gray-300" : "text-gray-700" }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <!-- スマホレイアウト用のサイト名 -->
        <div class="px-4 py-2 border-b {{ $isDark ? "border-gray-700" : "border-gray-300" }}">
            <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold">
                {{ env('APP_NAME') }}
            </a>
        </div>
        @include('admin.partials.sidebar')
    </div>


    <!-- 右側スライドインメニュー -->
    <div class="sm:hidden fixed w-64 h-full shadow-lg transform transition-transform duration-300 ease-in-out z-20 translate-x-100vw {{ $isDark ? 'text-gray-300 bg-gray-900' : 'text-gray-700 bg-gray-200' }}"
    :class="{ 'translate-x-100vw': !openUserMenu, 'translate-x-100vw-16': openUserMenu,'sm:block': openUserMenu, 'sm:hidden': ! openUserMenu }">

        <!-- 閉じるボタン -->
        <button @click="openUserMenu = false" class="absolute top-4 right-4 p-2 {{ $isDark ? "text-gray-300" : "text-gray-700" }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- ユーザーメニュー(スマホ用) -->
        <div class="pt-4 pb-1 border-t {{ config('admin.theme_class.' . $theme . '.option_1') }}">
            <div class="px-4">
                <div class="font-medium text-base {{ config('admin.theme_class.' . $theme . '.option_2') }}">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm {{ config('admin.theme_class.' . $theme . '.option_3') }}">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('mypage.profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- ログアウト -->
                <form method="POST" action="{{ route('mypage.logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('mypage.logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</header>
