@php
    $themeSettings = DB::table('dixlase_default_theme_settings')->first();
    $footerDescription = $themeSettings->footer_description ?? 'Powered by Dixlase CMS';
    $footerLinks = $themeSettings->footer_links ?? '[]';
    if (is_string($footerLinks)) {
        $footerLinks = json_decode($footerLinks, true) ?? [];
    }
    $footerCopyright = $themeSettings->footer_copyright ?? '© ' . date('Y') . ' ' . config('app.name', 'Dixlase') . '. All rights reserved.';
    $snsLinks = [
        'facebook' => $themeSettings->footer_sns_facebook ?? null,
        'twitter' => $themeSettings->footer_sns_twitter ?? null,
        'instagram' => $themeSettings->footer_sns_instagram ?? null,
        'linkedin' => $themeSettings->footer_sns_linkedin ?? null,
        'youtube' => $themeSettings->footer_sns_youtube ?? null,
    ];
@endphp

<footer class="bg-[#12141C] pt-16 pb-8">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 pb-8">
            {{-- About Section --}}
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-white mb-4">
                    {{ config('app.name', 'Dixlase') }}<span class="text-purple-500">Flow</span>
                </h2>
                <p class="text-gray-400 mb-6 max-w-xs">
                    {{ $footerDescription }}
                </p>
                
                {{-- SNS Links --}}
                @if(array_filter($snsLinks))
                    <div class="flex space-x-4">
                        @if($snsLinks['facebook'])
                            <a href="{{ $snsLinks['facebook'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-purple-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                                </svg>
                                <span class="sr-only">Facebook</span>
                            </a>
                        @endif
                        @if($snsLinks['twitter'])
                            <a href="{{ $snsLinks['twitter'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-purple-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path>
                                </svg>
                                <span class="sr-only">Twitter</span>
                            </a>
                        @endif
                        @if($snsLinks['instagram'])
                            <a href="{{ $snsLinks['instagram'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-purple-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                    <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line>
                                </svg>
                                <span class="sr-only">Instagram</span>
                            </a>
                        @endif
                        @if($snsLinks['linkedin'])
                            <a href="{{ $snsLinks['linkedin'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-purple-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                                    <rect width="4" height="12" x="2" y="9"></rect>
                                    <circle cx="4" cy="4" r="2"></circle>
                                </svg>
                                <span class="sr-only">LinkedIn</span>
                            </a>
                        @endif
                        @if($snsLinks['youtube'])
                            <a href="{{ $snsLinks['youtube'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-purple-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"></path>
                                    <path d="m10 15 5-3-5-3z"></path>
                                </svg>
                                <span class="sr-only">YouTube</span>
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Links Sections --}}
            @if(!empty($footerLinks))
                @php
                    $linkChunks = array_chunk($footerLinks, ceil(count($footerLinks) / 3));
                @endphp
                @foreach($linkChunks as $index => $chunk)
                    <div>
                        <h3 class="text-white font-medium mb-4">{{ __('themes::footer.links') }} {{ $index + 1 }}</h3>
                        <ul class="space-y-2">
                            @foreach($chunk as $link)
                                <li>
                                    <a href="{{ $link['url'] ?? '#' }}" class="text-gray-400 hover:text-purple-500 transition-colors">
                                        {{ $link['title'] ?? '' }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @else
                <div>
                    <h3 class="text-white font-medium mb-4">Products</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-purple-500 transition-colors">Features</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-purple-500 transition-colors">Pricing</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-medium mb-4">Resources</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-purple-500 transition-colors">Blog</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-purple-500 transition-colors">Documentation</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-medium mb-4">Company</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-purple-500 transition-colors">About</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-purple-500 transition-colors">Contact</a></li>
                    </ul>
                </div>
            @endif
        </div>

        {{-- Copyright --}}
        <div class="border-t border-white/10 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">
                    {{ $footerCopyright }}
                </p>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-purple-500 text-sm transition-colors">Terms of Service</a>
                    <a href="#" class="text-gray-400 hover:text-purple-500 text-sm transition-colors">Privacy Policy</a>
                    <a href="#" class="text-gray-400 hover:text-purple-500 text-sm transition-colors">Cookie Policy</a>
                </div>
            </div>
        </div>
    </div>
</footer>
