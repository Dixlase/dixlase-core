<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Dixlase') }} @yield('title')</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    {{-- Styles --}}
    @vite([
        'resources/src/front/scss/style.scss',
        'themes/DixlaseDefaultTheme/resources/assets/css/variables.css',
        'themes/DixlaseDefaultTheme/resources/assets/css/style.css'
    ])
    
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    @include('partials.header')

    <main class="min-h-screen">
        @yield('content')
    </main>

    @include('partials.footer')

    {{-- Scripts --}}
    @vite([
        'resources/src/front/js/scripts.js',
        'themes/DixlaseDefaultTheme/resources/assets/js/app.js'
    ])
    
    @stack('scripts')
</body>
</html>
