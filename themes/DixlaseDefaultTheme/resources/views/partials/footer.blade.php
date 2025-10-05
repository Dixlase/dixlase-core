<footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- About Section --}}
            <div>
                <h3 class="text-lg font-semibold mb-4">{{ config('app.name', 'Dixlase') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $footerDescription ?? 'Powered by Dixlase CMS' }}
                </p>
            </div>

            {{-- Links Section --}}
            <div>
                <h3 class="text-lg font-semibold mb-4">リンク</h3>
                <ul class="space-y-2 text-sm">
                    @foreach($footerLinks ?? [] as $link)
                        <li>
                            <a href="{{ $link['url'] ?? '#' }}" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                {{ $link['label'] ?? '' }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Contact Section --}}
            <div>
                <h3 class="text-lg font-semibold mb-4">お問い合わせ</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $footerContact ?? '' }}
                </p>
            </div>
        </div>

        {{-- Copyright --}}
        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700 text-center text-sm text-gray-600 dark:text-gray-400">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Dixlase') }}. All rights reserved.</p>
            <p class="mt-2">
                Powered by <a href="https://exc-d.com" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">Dixlase</a>
            </p>
        </div>
    </div>
</footer>
