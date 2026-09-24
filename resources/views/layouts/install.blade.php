{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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
<html lang="{{ $currentLocale ?? 'en' }}" x-data="installTheme()" :class="{ 'dark': isDark, 'light': !isDark }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @pageTitle

    {{-- Favicon links. Kept in sync with auth / admin layouts so the
         browser tab icon is consistent across install wizard, login,
         and admin surfaces. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon-16.png') }}">
    
    {{-- Prevent FOUC: dark mode + Alpine.js x-cloak (executed synchronously) --}}
    <style>[x-cloak]{display:none!important;}</style>
    <script @cspNonce>
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <!-- Main scripts -->
    @vite(['resources/src/common/css/tailwind.css', 'resources/src/install/js/app.js', 'resources/src/common/js/app.js', 'resources/src/common/scss/style.scss'], 'assets/build')


</head>
<body class="bg-gray-100 dark:bg-black flex items-center justify-center min-h-screen px-4">
    <div class="flex flex-col items-center w-full max-w-3xl my-10">

        {{-- Site logo. Same wrapper pattern as layouts/auth.blade.php
             — inline SVG inherits the wrapper's text color via
             `currentColor` so the mark reads on both light and dark
             backgrounds without shipping two files. --}}
        <div class="text-gray-900 dark:text-white mb-4">
            <x-application-logo
                size="w-32 h-auto"
                class="mx-auto"
                :site_name="env('APP_NAME')"
            />
        </div>

        <!-- Main Installation Container -->
        <main class="bg-white dark:bg-gray-900 shadow-lg rounded-lg p-8 w-full" role="main">

            <!-- Installation Header -->
            <header class="mb-6">
                <!-- Step Progress and Language Selector -->
                <div class="flex justify-between items-center w-full mb-4">
                    <!-- Step Progress Indicator -->
                    @if(isset($current_step) && isset($total_steps))
                        <nav aria-label="{{ __('install/common.installation_progress') }}" class="text-gray-600 dark:text-gray-300">
                            {{ __('install/common.step_of_total', ['current' => $current_step, 'total' => $total_steps]) }}
                        </nav>
                    @else
                        <div aria-hidden="true"></div>
                    @endif

                    <!-- Language Selector -->
                    @if(isset($availableLocales) && isset($currentLocale))
                        <div aria-label="{{ __('install/common.language_selection') }}">
                            <form id="language-form" action="{{ route('install.language', ['locale' => '__locale__']) }}" method="POST">
                                @csrf
                                @php
                                    $languageOptions = [];
                                    foreach($availableLocales as $locale) {
                                        $languageOptions[$locale] = 'install/common.languages.' . $locale;
                                    }
                                @endphp
                                <x-form-select
                                    id="language-selector"
                                    name="locale"
                                    :options="$languageOptions"
                                    :value="$currentLocale"
                                    :useDefaultClass="false"
                                    class="appearance-none bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-4 pr-8 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400"
                                    style="-webkit-appearance: none; -moz-appearance: none; text-indent: 1px; text-overflow: ''; min-width: 150px;"
                                />
                            </form>
                        </div>
                    @endif
                </div>
                
                <!-- Page Title and Description -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 text-center">@yield('header')</h1>
                    <p class="text-gray-600 dark:text-gray-300 text-center">
                        @yield('description')
                    </p>
                </div>
            </header>

            {{-- Flash messages (status / success / error / $errors) —
                 single source of truth for session flash + validation errors.
                 The install wizard's theme-download AJAX path also sets a
                 session flash and then reloads the page so the result lands here. --}}
            <x-ui-flash-message />

            <!-- Page Content -->
            <article>
                @yield('content')
            </article>
        </main>
    </div>

    @if(isset($availableLocales) && isset($currentLocale))
    <div id="install-layout-config" 
         data-available-locales="{{ json_encode($availableLocales) }}"
         data-current-locale="{{ $currentLocale }}"
         data-fallback-locale="{{ config('app.fallback_locale') }}"
         data-language-change-url="{{ url('/install/language') }}"
         data-csrf-token="{{ csrf_token() }}"
         data-session-locale="{{ session('install_locale') }}"
         data-msg-switch-failed="{{ __('Language switch failed.') }}"
         data-msg-error-occurred="{{ __('An error occurred. Please reload the page.') }}"
         style="display: none;">
    </div>
    @endif
</body>
</html>