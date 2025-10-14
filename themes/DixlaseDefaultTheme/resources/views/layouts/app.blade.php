<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('themes::partials.head')
    
    {{-- Font Awesome for SNS Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 flex flex-col min-h-screen">
    @include('themes::partials.header')

    <main class="flex-grow">
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
