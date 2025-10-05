<header class="bg-white dark:bg-gray-800 shadow-sm">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            {{-- Site Logo --}}
            <div class="flex-shrink-0">
                <x-themes::site-logo />
            </div>

            {{-- Navigation --}}
            <x-front.navigation :items="$navigationItems ?? []" />
        </div>
    </div>
</header>
