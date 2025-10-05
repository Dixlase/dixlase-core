{{--
    Site Logo Component
    
    Usage:
    <x-site-logo />
    <x-site-logo size="lg" />
--}}

@props(['size' => 'md'])

@php
$sizeClasses = [
    'sm' => 'h-8',
    'md' => 'h-10',
    'lg' => 'h-12',
];
$heightClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<a href="{{ url('/') }}" class="flex items-center space-x-2" aria-label="Home">
    {{-- ロゴ画像がある場合 --}}
    @if(config('app.logo'))
        <img 
            src="{{ asset(config('app.logo')) }}" 
            alt="{{ config('app.name', 'Dixlase') }}" 
            class="{{ $heightClass }} w-auto"
        >
    @else
        {{-- デフォルトロゴ (SVG) --}}
        <svg class="{{ $heightClass }} w-auto" viewBox="0 0 120 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="40" height="40" rx="8" class="fill-blue-600"/>
            <text x="50" y="28" class="fill-gray-900 dark:fill-white font-bold text-2xl" font-family="system-ui, sans-serif">
                {{ substr(config('app.name', 'Dixlase'), 0, 1) }}
            </text>
        </svg>
        <span class="text-xl font-bold text-gray-900 dark:text-white">
            {{ config('app.name', 'Dixlase') }}
        </span>
    @endif
</a>
