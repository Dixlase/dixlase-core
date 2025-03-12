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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $appearance === 2 ? 'dark' : ($appearance === 1 ? 'light' : 'auto') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- アセットを読み込み -->
        {!! load_active_assets() !!}

    </head>
    <body class="font-sans antialiased {{ config('admin.appearance_class.layout.body') }}">
        <div class="min-h-screen">
            <!-- Header -->
            @include('admin.partials.header', [
                'site_name' => $site_name,
            ])


            <div class="min-h-screen flex pt-16">
                 <!-- Side Bar -->
                <aside class="hidden sm:block w-64 flex-shrink-0 {{ config('admin.appearance_class.layout.aside') }}">
                    @include('admin.partials.sidebar')
                </aside>

                <!-- Main -->
                <main class="flex-1 pb-10 {{ config('admin.appearance_class.layout.main') }}">

                    <!-- Page Heading -->
                    <div class="{{ config('admin.appearance_class.layout.title') }} mx-auto py-6 px-4 sm:px-6 lg:px-8 mb-10">
                        <h2 class="font-semibold text-xl leading-tight {{ config('admin.appearance_class.layout.heading') }}">
                            <!-- ここにページタイトルを表示 -->
                            {{__($heading)}}
                        </h2>
                    </div>

                    <div class="px-4 sm:px-6 lg:px-8">
                        <!-- Page Content -->
                        @yield('content')
                    </div>

                </main>
            </div>
        </div>

        <script>

            // テーマの設定
            const current_appearance_class = '{{ $appearance }}';
            if (current_appearance_class === '0') { // 0: auto
                const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', isDarkMode);
                document.documentElement.classList.toggle('light', !isDarkMode);
            } else if (current_appearance_class === '2') { // 2: dark
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else { // 1: light
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            }

        </script>
    </body>
</html>
