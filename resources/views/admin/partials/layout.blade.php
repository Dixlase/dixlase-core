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
<html
    lang="{{ str_replace('_', '-', env('APP_LOCALE', config('app.locale', 'en'))) }}"
    class="{{ $htmlClass ?? '' }} {{ empty($transitionEnabled) ? 'disable-transition' : '' }}"
    x-data="appearanceTheme('{{ $appearance }}')"
    @if(empty($transitionEnabled))
        x-init="applyTheme(true)"
    @else
        x-init="init()"
    @endif
    :class="{ 'dark': isDark, 'light': !isDark }"
>
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
    <body  class="admin font-sans antialiased transition-colors duration-300 {{ config('admin.appearance_class.layout.body') }}">
        <div class="min-h-screen">
            <!-- Header -->
            @include('admin.partials.header', [
                'site_name' => $site_name,
            ])


            <div class="min-h-screen flex pt-16">
                 <!-- Side Bar -->
                <aside class="md:fixed overflow-auto md:h-full hidden sm:block w-64 flex-shrink-0 {{ config('admin.appearance_class.layout.aside') }}">
                    @include('admin.partials.sidebar')
                </aside>

                <!-- Main -->
                <main class="ml-0 md:ml-64 md:pl-4 lg:pl-0 flex-1 {{ config('admin.appearance_class.layout.main') }}">

                    <!-- Page Heading -->
                    <div class="mx-auto py-6 px-4 sm:px-6 lg:px-8 mb-10 {{ config('admin.appearance_class.layout.title') }}">
                        <h1 class="font-semibold text-xl leading-tight {{ config('admin.appearance_class.layout.heading') }}">
                            <!-- ここにページタイトルを表示 -->
                            {{ __($heading) }}
                        </h1>
                    </div>

                    <div class="w-full p-6 sm:p-0 lg:px-8 pb-8">
                        @include('components::flash-message')
                        <!-- Page Content -->
                        @yield('content')
                    </div>

                    @hasSection('save')
                        <div class="sticky bottom-0 z-30 backdrop-blur-sm bg-white/50 bg-white dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 pl-6 sm:px-6 lg:px-8 py-3">
                            <div class="w-full mx-auto">
                                @yield('save')
                            </div>
                        </div>
                    @endif

                </main>
            </div>
            <!-- Footer -->
            @include('admin.partials.footer')
        </div>

        <!-- Modals Section -->
        @hasSection('modals')
            @yield('modals')
        @endif
        @stack('modals')

        <script>
            // Alpine.js関数を先に定義
            function appearanceTheme(defaultValue) {
                return {
                    theme: defaultValue, // データベースの値を優先
                    isDark: false,

                    applyTheme(skipApply = false) {
                        this.isDark = this.theme === '2' || (this.theme === '0' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                        // プロフィール画面でのみlocalStorageに保存（リアルタイム変更のため）
                        if (document.querySelector('[data-profile-theme]')) {
                            localStorage.setItem('appearance', this.theme);
                        }
                        if (!skipApply) {
                            document.documentElement.classList.toggle('dark', this.isDark);
                            document.documentElement.classList.toggle('light', !this.isDark);
                        }
                    },

                    init() {
                        // プロフィール画面のみlocalStorageの値を使用（リアルタイム変更のため）
                        if (document.querySelector('[data-profile-theme]')) {
                            const storedTheme = localStorage.getItem('appearance');
                            if (storedTheme !== null) {
                                this.theme = storedTheme;
                            }
                        }
                        // その他の画面（メンバー管理画面含む）はDBの値を優先
                        
                        this.applyTheme(false);
                        document.documentElement.classList.remove('disable-transition');

                        document.querySelectorAll('input[name="appearance"]').forEach((el) => {
                            // プロフィール画面とメンバー管理画面の外観設定は除外
                            if (el.closest('[data-profile-theme]') || el.closest('[data-member-theme]')) {
                                return;
                            }
                            el.addEventListener('change', (e) => {
                                this.theme = e.target.value;
                                this.applyTheme(false);
                            });
                        });
                    }
                }
            }



            // テーマストア（後方互換性のため）
            window.themeStore = {
                theme: localStorage.getItem('appearance') ?? '{{ $appearance }}',
                isDark: false,
                applyTheme() {
                    this.isDark = this.theme === '2' || (this.theme === '0' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    localStorage.setItem('appearance', this.theme);
                    document.documentElement.classList.toggle('dark', this.isDark);
                    document.documentElement.classList.toggle('light', !this.isDark);
                }
            };

        </script>

        {{-- Page-specific scripts --}}
        @hasSection('scripts')
            @yield('scripts')
        @endif

        @stack('scripts')

    </body>
</html>
