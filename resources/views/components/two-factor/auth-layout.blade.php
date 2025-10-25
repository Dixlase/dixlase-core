@props([
    'title' => '二段階認証',
    'subtitle' => null,
    'context' => 'admin' // admin, user, plugin
])

<div class="flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-6 px-2">
    <div class="max-w-md w-full space-y-8">
        <div class="mt-8 space-y-6">
            <!-- メインコンテンツ -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                {{ $slot }}
            </div>
            <!-- 代替認証方法 -->
            @isset($alternatives)
                <div class="text-center">
                    {{ $alternatives }}
                </div>
            @endisset
        </div>
    </div>
</div>
