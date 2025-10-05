<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ config('app.name', 'Dixlase') }} @yield('title')</title>

{{-- Fonts --}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

{{-- Styles --}}
@if(app()->environment('local') && file_exists(public_path('hot')))
    {{-- Vite開発サーバーが起動している場合 --}}
    @vite([
        'resources/src/front/scss/style.scss',
        'themes/DixlaseDefaultTheme/resources/src/front/scss/style.scss',
        'themes/DixlaseDefaultTheme/resources/assets/js/app.js'
    ])
@else
    {{-- 本番環境またはViteが起動していない場合 --}}
    @vite([
        'resources/src/front/scss/style.scss',
        'themes/DixlaseDefaultTheme/resources/assets/css/variables.css',
        'themes/DixlaseDefaultTheme/resources/assets/css/style.css'
    ])
@endif

@stack('styles')
