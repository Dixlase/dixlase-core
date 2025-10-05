{{--
    Button Component
    
    Usage:
    <x-front.button variant="primary">Click me</x-front.button>
    <x-front.button variant="secondary" href="/about">Link Button</x-front.button>
    
    Props:
    - variant: string - Button style (primary, secondary, outline, ghost) - default: primary
    - size: string - Button size (sm, md, lg) - default: md
    - href: string - If set, renders as <a> tag instead of <button>
    - type: string - Button type (button, submit, reset) - default: button
--}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button'
])

@php
$baseClasses = 'btn inline-flex items-center justify-center font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';

$variantClasses = [
    'primary' => 'btn--primary bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'secondary' => 'btn--secondary bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
    'outline' => 'btn--outline border-2 border-blue-600 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 focus:ring-blue-500',
    'ghost' => 'btn--ghost text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:ring-gray-500',
];

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-base',
    'lg' => 'px-6 py-3 text-lg',
];

$classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
