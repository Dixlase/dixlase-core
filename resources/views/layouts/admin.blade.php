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
@endphp

@props([
    'title' => ''
])

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
        @if (app()->environment('local'))
            {{-- 開発環境ではリソースを直接読み込み --}}
            @vite(['resources/js/app.js', 'resources/scss/app.scss'])
        @else
            {{-- 本番環境ではmanifest.jsonを読み込み --}}
            @vite(['resources/js/app.js', 'resources/scss/app.scss'], 'build')
        @endif
    </head>
    <body class="font-sans antialiased {{ config('admin.theme_class.' . $theme . '.body') }}">
        <div class="min-h-screen">
            <!-- Header -->
            @include('admin.partials.header')
            <!-- Side Bar -->
            <div class="min-h-screen flex">
                <aside class="{{ config('admin.theme_class.' . $theme . '.aside') }} w-64 flex-shrink-0">
                    @include('admin.partials.sidebar')
                </aside>
                <main class="{{ config('admin.theme_class.' . $theme . '.main') }} flex-1">
                    <!-- Page Heading -->
                    <div class="{{ config('admin.theme_class.' . $theme . '.title') }} mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <h2 class="font-semibold text-xl {{ config('admin.theme_class.' . $theme . '.headding') }} leading-tight">
                            <!-- ここにページタイトルを表示 -->
                            {{ $title ?? '' }}
                        </h2>
                    </div>
                    <div class="px-4 sm:px-6 lg:px-8">
                        <!-- Page Content -->
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
