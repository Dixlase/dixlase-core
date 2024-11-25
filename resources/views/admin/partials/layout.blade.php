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

@props([
    'title' => '',
    'theme' => 'light',

    'theme_class' => [
        'body' => 'bg-white text-gray-900 dark:bg-gray-950 dark:text-white',
        'header' => 'bg-gray-200 dark:bg-gray-800 border-gray-300 dark:border-gray-700 border-b',
        'logo' => 'text-gray-900 dark:text-white',
        'aside' => 'bg-gray-100 dark:bg-gray-800 text-gray-900 border-r border-gray-300  dark:text-white dark:border-r dark:border-gray-700',
        'main' => 'bg-white text-gray-900 dark:bg-gray-900 dark:text-white',
        'title' => 'bg-gray-100 text-gray-800 border-b border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white',
        'heading' => 'text-gray-800 dark:text-white',
        'nav_link' => 'text-gray-700 hover:text-black dark:text-gray-300 dark:hover:text-white',
        'button_admin_user' => 'text-gray-500 bg-white hover:text-gray-700 dark:text-gray-300 dark:bg-gray-800 dark:hover:text-white',
        'button_hamburger' => 'text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700',
        'responsive_navigation_menu' => 'text-gray-700 hover:text-black dark:text-gray-300 dark:hover:text-white',
        'option_1' => 'border-gray-200 dark:border-gray-700',
        'option_2' => 'text-gray-800 dark:text-white',
        'option_3' => 'text-gray-500 dark:text-gray-400',
        'table' => [
            'header' => 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-white',
            'row' => 'bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white',
            'row_hover' => 'hover:bg-gray-100 dark:hover:bg-gray-600',
            'row_selected' => 'bg-gray-200 dark:bg-gray-600',
            'row_selected_hover' => 'hover:bg-gray-200 dark:hover:bg-gray-600',
            'cell' => 'border-b border-gray-200 dark:border-gray-700',
            'cell_selected' => 'border-b border-gray-200 dark:border-gray-700',
            'cell_selected_hover' => 'border-b border-gray-200 dark:border-gray-700',
        ]
    ],

])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $theme === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @if (app()->environment('local'))
            {{-- 開発環境ではリソースを直接読み込み --}}
            @vite(['resources/js/app.js', 'resources/scss/app.scss'])
        @else
            {{-- 本番環境ではmanifest.jsonを読み込み --}}
            @vite(['resources/js/app.js', 'resources/scss/app.scss'], 'build')
        @endif
    </head>
    <body class="font-sans antialiased {{ config('admin.theme_class.layout.body') }}">
        <div class="min-h-screen">
            <!-- Header -->
            @include('admin.partials.header', [
                'siteName' => $siteName,
                'theme' => $theme,
            ])


            <div class="min-h-screen flex">
                 <!-- Side Bar -->
                <aside class="hidden sm:block w-64 flex-shrink-0 {{ config('admin.theme_class.layout.aside') }}">
                    @include('admin.partials.sidebar')
                </aside>

                <!-- Main -->
                <main class="flex-1 {{ config('admin.theme_class.main') }}">

                    <!-- Page Heading -->
                    <div class="{{ config('admin.theme_class.title') }} mx-auto py-6 px-4 sm:px-6 lg:px-8 mb-10">
                        <h2 class="font-semibold text-xl leading-tight {{ config('admin.theme_class.layout.heading') }}">
                            <!-- ここにページタイトルを表示 -->
                            {{ __($title) ?? '' }}
                        </h2>
                    </div>

                    <div class="px-4 sm:px-6 lg:px-8">
                        <!-- Page Content -->
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
        <script>

    </body>
</html>
