<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>

        <!-- Scripts -->
    @if (app()->environment('local'))
        {{-- 開発環境ではリソースを直接読み込み --}}
        @vite([
            'resources/src/common/js/app.js',
            'resources/src/common/scss/app.scss'
        ])
    @else
        {{-- 本番環境ではmanifest.jsonを読み込み --}}
        @vite(['resources/src/common/js/app.js', 'resources/src/common/scss/app.scss'], 'build')
    @endif


</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="flex flex-col items-center w-full max-w-xl min-w-[400px]">

        <img src="{{ asset('assets/images/logo.svg') }}" alt="{{ env('APP_NAME') }}" class="w-32 h-auto mx-auto mb-4">

        <div class="bg-white shadow-lg rounded-lg p-8 max-w-xl w-full">
            <h1 class="text-2xl font-bold text-gray-800 mb-4 text-center">@yield('header')</h1>
            <p class="text-gray-600 mb-6 text-center">@yield('description')</p>

            @if(session('error'))
                <div class="bg-red-100 text-red-600 p-3 mb-4 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="bg-red-100 text-red-600 p-3 mb-4 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</body>
</html>