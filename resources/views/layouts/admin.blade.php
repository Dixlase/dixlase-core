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
        <!-- CSP Safe Mode Banner -->
        <x-security.csp-safe-mode-banner />
        
        <!-- Admin Bar (Header) -->
        <x-ui-admin-bar :isAdminLayout="true" />
        
        <div class="min-h-screen flex">
            <!-- Navigation Sidebar (Desktop only) -->
            <aside class="md:fixed md:h-full hidden sm:block w-64 flex-shrink-0 border-gray-300 transition-transform duration-300"
                   role="navigation" aria-label="Main navigation"
                   data-mobile-sidebar>
                @include('admin.partials.sidebar-vanilla', [
                    'transitionEnabled' => $transitionEnabled ?? null,
                    'route_name' => Route::currentRouteName()
                ])
            </aside>

            <!-- Sidebar Toggle Button (Desktop) -->
            <button class="hidden sm:flex fixed left-0 top-21 -translate-y-1/2 z-40 backdrop-blur-sm dark:bg-gray-900/75 bg-white/75 text-blue-400 dark:text-white px-1.5 py-4 rounded-r-lg shadow-md border border-l-0 border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-300"
                    aria-label="Toggle desktop sidebar menu"
                    data-desktop-sidebar-toggle>
                <i class="fas text-sm fa-chevron-left"></i>
            </button>

            <!-- Main Content Area -->
            <main class="mt-12 ml-0 md:pl-4 lg:pl-0 flex-1 bg-white text-gray-900 dark:bg-black dark:text-white transition-all duration-300">
                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>
