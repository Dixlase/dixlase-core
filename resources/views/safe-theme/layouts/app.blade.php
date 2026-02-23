{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

Safe Theme Layout - テーマセーフモード用の最小限レイアウト
テーマアセットなし、コアTailwind CSSとAlpine.jsのみ使用。
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('themes::partials.head')
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 flex flex-col min-h-screen">
    {{-- 管理バー（管理者ログイン時のみ表示） --}}
    <div class="sticky top-0" style="z-index: 9999;">
        <x-ui-maintenance-banner />
        <x-ui-admin-bar />
    </div>

    @include('themes::partials.header')

    <main class="flex-grow container mx-auto px-4 py-8">
        @yield('content')
    </main>

    @include('themes::partials.footer')

    {{-- コアアセットのみ読み込み --}}
    {!! load_front_assets() !!}

    @stack('scripts')
</body>
</html>
