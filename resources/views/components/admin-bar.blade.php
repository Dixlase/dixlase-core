{{-- 管理者ログイン時のみ表示される管理バー --}}
@auth('member')
<div id="admin-bar" class="fixed top-0 left-0 right-0 bg-gray-900 text-white shadow-lg" style="z-index: 9999;">
    <div class="max-w-screen-2xl mx-auto px-4">
        <div class="flex items-center justify-between h-12">
            {{-- 左側: サイト名とメニュー --}}
            <div class="flex items-center space-x-4">
                {{-- サイト名/ロゴ --}}
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2 hover:text-blue-400 transition-colors">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="font-semibold hidden sm:inline">{{ config('app.name') }}</span>
                </a>

                {{-- 区切り線 --}}
                <div class="h-6 w-px bg-gray-700 hidden sm:block"></div>

                {{-- メニュー項目 --}}
                <nav class="flex items-center space-x-1">
                    {{-- ダッシュボード --}}
                    <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded hover:bg-gray-800 transition-colors text-sm">
                        <i class="fas fa-home mr-1"></i>
                        <span class="hidden md:inline">{{ __('admin.nav.dashboard') }}</span>
                    </a>

                    {{-- テーマ設定 --}}
                    <a href="{{ route('admin.settings.themes.settings') }}" class="px-3 py-1.5 rounded hover:bg-gray-800 transition-colors text-sm">
                        <i class="fas fa-palette mr-1"></i>
                        <span class="hidden md:inline">{{ __('admin.nav.settings.themes.settings') }}</span>
                    </a>

                    {{-- フロントページデザイン --}}
                    <a href="{{ url('/admin/front/design') }}" class="px-3 py-1.5 rounded hover:bg-gray-800 transition-colors text-sm">
                        <i class="fas fa-paint-brush mr-1"></i>
                        <span class="hidden md:inline">{{ __('common.design') }}</span>
                    </a>
                </nav>
            </div>

            {{-- 右側: ユーザー情報 --}}
            <div class="flex items-center space-x-4">
                {{-- フロントページへ --}}
                <a href="{{ url('/') }}" class="px-3 py-1.5 rounded hover:bg-gray-800 transition-colors text-sm hidden sm:flex items-center">
                    <i class="fas fa-external-link-alt mr-1"></i>
                    <span class="hidden lg:inline">{{ __('common.view_site') }}</span>
                </a>

                {{-- ユーザーメニュー --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" class="flex items-center space-x-2 px-3 py-1.5 rounded hover:bg-gray-800 transition-colors">
                        <i class="fas fa-user-circle text-lg"></i>
                        <span class="text-sm hidden sm:inline">{{ auth('member')->user()->name }}</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-50"
                         style="display: none;">
                        <a href="{{ route('admin.profile') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-user mr-2"></i>{{ __('admin.nav.profile') }}
                        </a>
                        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fas fa-sign-out-alt mr-2"></i>{{ __('auth.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 管理バーの高さ分のスペーサー --}}
<div class="h-12"></div>

{{-- 管理バー用のスタイル調整 --}}
<style>
    body.has-admin-bar {
        padding-top: 0;
    }
    
    #admin-bar a,
    #admin-bar button {
        user-select: none;
    }
    
    /* モバイル対応 */
    @media (max-width: 640px) {
        #admin-bar .hidden {
            display: none !important;
        }
    }
</style>

<script>
    document.body.classList.add('has-admin-bar');
</script>
@endauth
