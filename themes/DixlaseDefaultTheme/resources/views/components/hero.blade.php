@php
    // テーマ設定はServiceProviderから自動的に渡される
    $heroBackground = $themeSettings->hero_background_image_id ?? null;
    $heroMainTitle = $themeSettings->hero_main_title ?? 'Welcome to ' . config('app.name', 'Dixlase');
    $heroSubTitle = $themeSettings->hero_sub_title ?? 'Modern CMS Platform for Building Amazing Websites';
    $heroButtonText = $themeSettings->hero_button_text ?? 'Get Started';
    $heroButtonLink = $themeSettings->hero_button_link ?? '#';
    $heroButtonSecondaryText = $themeSettings->hero_button_secondary_text ?? null;
    $heroButtonSecondaryLink = $themeSettings->hero_button_secondary_link ?? null;
@endphp

<section class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-gradient-to-br from-[#0F1117] via-[#1a1d2e] to-[#0F1117]">
    {{-- Background Glow Effects --}}
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 left-10 w-72 h-72 bg-purple-500/10 rounded-full filter blur-3xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-purple-400/10 rounded-full filter blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
    </div>

    {{-- Content --}}
    <div class="container mx-auto px-4 py-20 relative z-10">
        <div class="flex flex-col lg:flex-row items-center">
            <div class="lg:w-1/2">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                    <span class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">{{ $heroMainTitle }}</span>
                </h1>
                
                @if($heroSubTitle)
                    <p class="text-lg text-gray-300 mb-8 max-w-lg">
                        {{ $heroSubTitle }}
                    </p>
                @endif
                
                <div class="flex flex-col sm:flex-row gap-4">
                    @if($heroButtonText)
                        <a href="{{ $heroButtonLink }}" class="inline-flex items-center justify-center gap-2 h-11 rounded-xl bg-purple-600 hover:bg-purple-700 text-white px-8 py-6 transition-colors">
                            {{ $heroButtonText }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-5 w-5">
                                <path d="M5 12h14"></path>
                                <path d="m12 5 7 7-7 7"></path>
                            </svg>
                        </a>
                    @endif
                    
                    @if($heroButtonSecondaryText)
                        <a href="{{ $heroButtonSecondaryLink }}" class="inline-flex items-center justify-center gap-2 h-11 rounded-xl px-8 border border-gray-700 text-white hover:bg-white/5 py-6 transition-colors">
                            {{ $heroButtonSecondaryText }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-5 w-5">
                                <path d="M7 7h10v10"></path>
                                <path d="M7 17 17 7"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
            
            <div class="lg:w-1/2 mt-12 lg:mt-0">
                {{-- Placeholder for hero image/illustration --}}
            </div>
        </div>
    </div>
</section>

<style>
    @keyframes pulse-slow {
        0%, 100% {
            opacity: 0.3;
        }
        50% {
            opacity: 0.6;
        }
    }
    .animate-pulse-slow {
        animation: pulse-slow 4s ease-in-out infinite;
    }
</style>
