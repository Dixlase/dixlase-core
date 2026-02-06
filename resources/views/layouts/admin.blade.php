<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', env('APP_LOCALE', config('app.locale', 'en'))) }}">
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
    <x-ui-livewire-notification />

</head>
<body class="admin font-sans antialiased transition-colors-unified dark:bg-black dark:text-white">
    <div class="min-h-screen">
        <div class="min-h-screen flex">
            <!-- Main Content Area -->
            <main class="mt-12 ml-0 md:pl-4 lg:pl-0 flex-1 bg-white text-gray-900 dark:bg-black dark:text-white">
                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>
