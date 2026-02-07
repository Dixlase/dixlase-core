<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', env('APP_LOCALE', config('app.locale', 'en'))) }}" class="{{ $htmlClass ?? '' }}">
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
<body class="admin font-sans antialiased transition-colors-unified dark:bg-black dark:text-white" data-default-appearance="{{ $appearance }}">
    <div class="min-h-screen">
        <!-- CSP Safe Mode Banner -->
        <x-security.csp-safe-mode-banner />
        
        <!-- Admin Bar (Header) -->
        <x-ui-admin-bar-vanilla isAdminLayout="true" />
        
        <div class="min-h-screen flex">
            <!-- Navigation Sidebar -->
            <aside class="fixed h-full w-64 flex-shrink-0 border-gray-300 -translate-x-64 z-50"
                   role="navigation" aria-label="Main navigation"
                   data-mobile-sidebar
                   style="transition: none;">
                @include('admin.partials.sidebar-vanilla', [
                    'transitionEnabled' => $transitionEnabled ?? null,
                    'route_name' => Route::currentRouteName()
                ])
            </aside>

            <!-- Main Content Area -->
            <main class="mt-12 flex-1 bg-white text-gray-900 dark:bg-black dark:text-white" role="main" style="transition: none;">

                <!-- Page Header -->
                @if(!empty($heading))
                    <header class="mx-auto py-6 px-8 mb-2 bg-white text-gray-800 border-b border-gray-300 dark:border-gray-700 dark:bg-black dark:text-white">
                        <h1 class="font-semibold text-xl leading-tight text-gray-800 dark:text-white">
                            {{ __($heading) }}
                        </h1>
                    </header>
                @endif

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

                <!-- Page Content -->
                <article class="w-full px-6 lg:px-8 pb-8 mt-8">
                    <x-ui-flash-message />
                    @yield('content')
                </article>

                <!-- Save Button Area -->
                @hasSection('save')
                    <div class="sticky bottom-0 z-30 backdrop-blur-sm bg-white/75 dark:bg-gray-900/75 border-t border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-3">
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
