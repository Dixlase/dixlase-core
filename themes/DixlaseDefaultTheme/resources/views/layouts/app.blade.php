<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('themes::partials.head')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    @include('themes::partials.header')

    <main class="min-h-screen">
        @yield('content')
    </main>

    @include('themes::partials.footer')

    {{-- Scripts --}}
    @if(!(app()->environment('local') && file_exists(public_path('hot'))))
        {{-- Viteが起動していない場合のみJSを読み込む（起動時は上で読み込み済み） --}}
        @vite([
            'resources/src/front/js/scripts.js',
            'themes/DixlaseDefaultTheme/resources/assets/js/app.js'
        ])
    @endif
    
    @stack('scripts')
</body>
</html>
