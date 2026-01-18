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

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', env('APP_LOCALE', config('app.locale', 'en'))) }}"
    class="{{ $htmlClass ?? '' }}"
    x-data="appearanceMode('{{ $appearance }}')"
    x-init="init()"
    :class="{ 'dark': isDark, 'light': !isDark, 'theme-ready': themeReady }"
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

        {{-- 通知コンポーネント（他のスクリプトより先に読み込み） --}}
        <x-notification />

    </head>
    <body class="admin font-sans antialiased transition-colors-unified dark:bg-black dark:text-white"
          data-default-appearance="{{ $appearance }}"
          x-data="{ openSidebar: false, openUserMenu: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true', sidebarReady: false }"
          x-init="$nextTick(() => { sidebarReady = true }); $watch('sidebarCollapsed', value => localStorage.setItem('sidebarCollapsed', value))">
        <div class="min-h-screen">
            <!-- Admin Bar (Header) -->
            <x-admin-bar :isAdminLayout="true" />


            <div class="min-h-screen flex">
                <!-- Navigation Sidebar (Desktop only) -->
                <aside class="md:fixed md:h-full hidden sm:block w-64 flex-shrink-0 border-gray-300 @if($transitionEnabled ?? false) transition-all duration-[500ms] @else transition-transform duration-300 @endif"
                       :class="{
                           '-translate-x-64': sidebarCollapsed,
                           'translate-x-0': !sidebarCollapsed
                       }"
                       role="navigation" aria-label="Main navigation">
                    @include('admin.partials.sidebar', [
                        'transitionEnabled' => $transitionEnabled ?? null,
                        'route_name' => Route::currentRouteName()
                    ])
                </aside>

                <!-- Sidebar Toggle Button (Desktop) -->
                <button @click="sidebarCollapsed = !sidebarCollapsed"
                        class="hidden sm:flex fixed left-0 top-21 -translate-y-1/2 z-40 backdrop-blur-sm dark:bg-gray-900/75 bg-white/75 text-blue-400 dark:text-white px-1.5 py-4 rounded-r-lg shadow-md border border-l-0 border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                        :class="{
                            'translate-x-0': sidebarCollapsed,
                            'translate-x-64': !sidebarCollapsed
                        }"
                        :style="sidebarReady ? 'transition: transform 300ms ease-in-out' : ''"
                        aria-label="Toggle sidebar menu">
                    <i class="fas text-sm" :class="sidebarCollapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
                </button>

                <!-- Main Content Area -->
                <main class="mt-12 ml-0 md:pl-4 lg:pl-0 flex-1 bg-white text-gray-900 dark:bg-black dark:text-white @if($transitionEnabled ?? false) transition-all duration-[500ms] @endif"
                      :class="{
                          'md:ml-0': sidebarCollapsed,
                          'md:ml-64': !sidebarCollapsed
                      }"
                      x-init="
                          // 初回表示後にサイドバートグル用のトランジションを追加
                          $nextTick(() => { 
                              if (!(@if($transitionEnabled ?? false) true @else false @endif)) { 
                                  setTimeout(() => {
                                      $el.classList.add('transition-all', 'duration-300');
                                  }, 100);
                              }
                          })
                      "
                      role="main">

                    <!-- Page Header -->
                    <header class="mx-auto py-6 px-8 mb-2 bg-white text-gray-800 border-b border-gray-300 dark:border-gray-700 dark:bg-black dark:text-white @if($transitionEnabled ?? false) transition-colors duration-[500ms] @endif">
                        <h1 class="font-semibold text-xl leading-tight text-gray-800 dark:text-white">
                            {{ __($heading) }}
                        </h1>
                    </header>

                    <!-- Breadcrumbs -->
                    @if(!empty($breadcrumbs) && count($breadcrumbs) > 0)
                        <nav class="w-full px-6 lg:px-8">
                            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                @foreach($breadcrumbs as $index => $breadcrumb)
                                    @if($index > 0)
                                        <li><i class="fas fa-chevron-right text-xs"></i></li>
                                    @endif
                                    <li>
                                        @if(!empty($breadcrumb['route']))
                                            <a href="{{ route($breadcrumb['route']) }}" class="{{ $loop->last ? 'text-gray-900 dark:text-white font-medium' : 'hover:text-gray-700 dark:hover:text-white' }}">
                                                {{ $breadcrumb['label'] }}
                                            </a>
                                        @else
                                            <span class="{{ $loop->last ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-500 dark:text-gray-400' }}">{{ $breadcrumb['label'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    <!-- Page Description -->
                    @if(!empty($description))
                        <div class="w-full px-6 lg:px-8 mt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $description }}
                            </p>
                        </div>
                    @endif

                    <!-- Page Heading -->
                    @if (isset($heading))
                        <header class="w-full px-6 lg:px-8 mt-8 @if($transitionEnabled ?? false) transition-colors duration-[500ms] @endif">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ $heading }}
                            </h1>
                        </header>
                    @endif

                    <!-- Page Content -->
                    <article class="w-full px-6 lg:px-8 pb-8 mt-8">
                        <x-flash-message />
                        @yield('content')
                    </article>

                    @hasSection('save')
                        <div class="sticky bottom-0 z-30 backdrop-blur-sm bg-white/75 dark:bg-gray-900/75 border-t border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-3 @if($transitionEnabled ?? false) transition-colors duration-[500ms] @endif">
                            <div class="w-full mx-auto flex justify-center">
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

        {{-- Page-specific styles --}}
        @hasSection('styles')
            @yield('styles')
        @endif
        
        {{-- Component styles from @push --}}
        @stack('styles')

        {{-- Page-specific scripts --}}
        @hasSection('scripts')
            @yield('scripts')
        @endif

        @stack('scripts')

    </body>
</html>
