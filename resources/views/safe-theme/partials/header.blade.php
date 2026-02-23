{{--
Safe Theme Header - セーフモード用の最小限ヘッダー
--}}

<header class="bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <a href="/" class="text-xl font-bold text-gray-900 dark:text-white">
                {{ config('app.name', 'Dixlase') }}
            </a>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                <i class="fas fa-paint-brush mr-1"></i>
                {{ __('admin/safe-mode.theme_safe_mode_label') }}
            </span>
        </div>
    </div>
</header>
