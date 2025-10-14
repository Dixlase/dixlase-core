@php
    $themeSettings = DB::table('dixlase_default_theme_settings')->first();
    $logoUrl = $themeSettings->logo_url ?? null;
    $logoText = $themeSettings->logo_text ?? config('app.name', 'Dixlase');
    $hasAdminBar = auth('member')->check();
@endphp

<header class="fixed w-full z-50 transition-all duration-300 py-6 {{ $hasAdminBar ? 'top-12' : 'top-0' }}">
    <div class="container mx-auto px-4 flex justify-between items-center">
        <div class="flex items-center justify-between w-full">
            {{-- Site Logo --}}
            <div class="flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $logoText }}" class="h-8 w-auto">
                    @else
                        <h1 class="text-2xl font-bold text-white">
                            {{ $logoText }}
                        </h1>
                    @endif
                </a>
            </div>

            {{-- Navigation --}}
            <ul class="hidden lg:flex items-center space-x-8">
                <x-front.navigation :items="$navigationItems ?? []" />
            </ul>

            {{-- Mobile Menu Button --}}
            <button class="lg:hidden text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu">
                    <line x1="4" x2="20" y1="12" y2="12"></line>
                    <line x1="4" x2="20" y1="6" y2="6"></line>
                    <line x1="4" x2="20" y1="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
</header>
