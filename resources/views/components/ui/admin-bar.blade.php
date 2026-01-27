{{-- 
管理者ログイン時のみ表示される管理バー
@props(['isAdminLayout' => false]) - 管理画面レイアウトモードの場合true
--}}
@php
    // データベースが存在しない場合（アンインストール後など）は何も表示しない
    try {
        $isAuthenticated = auth('member')->check();
    } catch (\Exception $e) {
        $isAuthenticated = false;
    }
@endphp

@if($isAuthenticated)
@props(['isAdminLayout' => false])

<div x-data="adminBar()" 
     x-init="init()"
     id="admin-bar" class="fixed top-0 left-0 right-0 backdrop-blur-sm text-gray-700 dark:text-white bg-white/75 dark:bg-gray-900/75 border-b border-gray-300 dark:border-gray-700 shadow-md" style="z-index: 9900;">
    <div class="w-full mx-auto px-4">
        <div class="flex items-center justify-between h-12">
            {{-- 左側: サイト名とメニュー --}}
            <div class="flex items-center {{ $isAdminLayout ? '' : 'space-x-4' }}">
                {{-- サイト名/ロゴ --}}
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2 hover:opacity-80 transition-opacity {{ $isAdminLayout ? 'mr-4' : '' }}">
                    <x-application-logo
                        class="text-gray-900 dark:text-white"
                        :site_name="config('app.name')"
                        size="h-6 w-6"
                    />
                    <span class="font-semibold hidden sm:inline">{{ config('app.name') }}</span>
                </a>

                {{-- 区切り線 --}}
                <div class="h-6 w-px bg-gray-700 hidden sm:block {{ $isAdminLayout ? 'mr-4' : '' }}"></div>

                {{-- メニュー項目（常に表示） --}}
                <nav class="flex items-center space-x-1">
                    {{-- ダッシュボード --}}
                    <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-home mr-1"></i>
                        <span class="hidden md:inline">{{ __('admin/nav.dashboard') }}</span>
                    </a>
                    
                    {{-- フロントページデザイン --}}
                    <a href="{{ url('/admin/front/design') }}" class="px-3 py-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-paint-brush mr-1"></i>
                        <span class="hidden md:inline">{{ __('common.design') }}</span>
                    </a>

                    {{-- テーマ設定（ルートが存在する場合のみ表示） --}}
                    @if(Route::has('admin.settings.themes.settings'))
                        <a href="{{ route('admin.settings.themes.settings') }}" class="px-3 py-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-sm">
                            <i class="fas fa-palette mr-1"></i>
                            <span class="hidden md:inline">{{ __('admin/nav.settings.themes.settings') }}</span>
                        </a>
                    @endif


                </nav>
            </div>

            {{-- 右側: ユーザー情報 --}}
            <div class="flex items-center space-x-4">
                {{-- サイトを表示 --}}
                <a href="{{ url('/') }}" class="px-3 py-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-sm flex items-center">
                    <i class="fas fa-external-link-alt mr-1"></i>
                    <span class="hidden lg:inline">{{ __('common.view_site') }}</span>
                </a>

                {{-- モバイル用ユーザーメニュートグル --}}
                @if($isAdminLayout)
                    <button @click="openUserMenu = true"
                            class="sm:hidden inline-flex items-center justify-center rounded-md hover:text-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none transition"
                            aria-label="Open user menu">
                        <i class="fas fa-user-circle text-2xl" aria-hidden="true"></i>
                    </button>
                @else
                    <a href="{{ route('admin.profile') }}"
                       class="sm:hidden inline-flex items-center justify-center rounded-md text-gray-800 hover:text-gray-800 hover:bg-gray-800 focus:outline-none transition"
                       aria-label="Profile">
                        <i class="fas fa-user-circle text-2xl" aria-hidden="true"></i>
                    </a>
                @endif

                {{-- デスクトップ用ユーザーメニュー --}}
                <div class="hidden sm:block relative">
                    <button @click="userMenuOpen = !userMenuOpen" @click.away="userMenuOpen = false" class="flex items-center space-x-2 px-3 py-1.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-user-circle text-xl"></i>
                        <span class="text-sm hidden sm:inline">{{ auth('member')->user()->display_name ?? auth('member')->user()->account_name }}</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div x-show="userMenuOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg overflow-hidden z-50">
                        {{-- ユーザー情報 --}}
                        <div class="px-4 py-3 flex items-center space-x-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <i class="fas fa-user-circle text-3xl text-gray-600 dark:text-gray-400"></i>
                            <div>
                                <div class="font-medium text-base text-gray-900 dark:text-white">{{ auth('member')->user()->display_name ?? auth('member')->user()->account_name }}</div>
                                <div class="text-sm text-gray-900 dark:text-gray-400">{{ auth('member')->user()->email }}</div>
                            </div>
                        </div>
                        {{-- メニュー項目 --}}
                        <div class="bg-white dark:bg-black">
                            <a href="{{ route('admin.profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                <i class="fas fa-user w-5 text-center mr-2 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ __('admin/nav.profile.text') }}</span>
                            </a>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700"></div>
                        <div class="bg-white dark:bg-black">
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    <i class="fas fa-sign-out-alt w-5 text-center mr-2 text-gray-500 dark:text-gray-400"></i>
                                    <span>{{ __('common.logout') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($isAdminLayout)
    {{-- モバイルメニューオーバーレイ --}}
    <div x-show="openSidebar || openUserMenu" 
         @click="openSidebar = false; openUserMenu = false" 
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-40" 
         aria-hidden="true"></div>

    {{-- 左側スライドインサイドバー（モバイル） --}}
    <div x-cloak 
         class="sm:hidden fixed h-full inset-y-12 left-0 transform transition-transform duration-300 ease-in-out z-50"
         :class="{ '-translate-x-64': !openSidebar, 'translate-x-0': openSidebar }">
        
        {{-- スクロール可能なメニュー部分（タブボタン含む） --}}
        <div class="flex-1 h-full ">
            @include('admin.partials.sidebar', [
                'route_name' => Route::currentRouteName()
            ])
        </div>
    </div>

    {{-- 右側スライドインユーザーメニュー（モバイル） --}}
    <div x-cloak 
         class="fixed right-0 top-0 w-64 h-full shadow-lg transform transition-transform duration-300 ease-in-out z-50 bg-white dark:bg-black border-l border-gray-300 dark:border-gray-700"
         :class="{ 'translate-x-full': !openUserMenu, 'translate-x-0': openUserMenu }">
        
        {{-- 閉じるボタン --}}
        <button @click="openUserMenu = false" class="absolute top-4 right-4 p-2 text-gray-700 dark:text-gray-300">
            <i class="fas fa-times text-xl"></i>
        </button>

        {{-- ユーザーメニュー --}}
        <div class="pt-16 px-4">
            {{-- ユーザー情報 --}}
            <div class="flex items-center space-x-3 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                <i class="fas fa-user-circle text-3xl text-gray-600 dark:text-gray-300"></i>
                <div>
                    <div class="font-medium text-base text-gray-900 dark:text-gray-100">{{ auth('member')->user()->display_name ?? auth('member')->user()->account_name }}</div>
                    <div class="font-medium text-sm text-gray-600 dark:text-gray-400">{{ auth('member')->user()->email }}</div>
                </div>
            </div>

            {{-- メニュー項目 --}}
            <div class="space-y-2">
                <a href="{{ route('admin.profile') }}"
                   class="flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition">
                    <i class="fas fa-user w-5 text-center mr-3 text-gray-500 dark:text-gray-400"></i>
                    <span>{{ __('admin/nav.profile.text') }}</span>
                </a>

                {{-- ログアウト --}}
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition text-left">
                        <i class="fas fa-sign-out-alt w-5 text-center mr-3 text-gray-500 dark:text-gray-400"></i>
                        <span>{{ __('common.logout') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
@endif
