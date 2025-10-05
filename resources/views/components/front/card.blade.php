{{--
    Card Component
    
    Usage:
    <x-front.card title="Card Title">
        Card content goes here
    </x-front.card>
    
    <x-front.card>
        <x-slot:title>Custom Title</x-slot:title>
        <x-slot:footer>Footer content</x-slot:footer>
        Card content
    </x-front.card>
    
    Props:
    - title: string - Card title (optional)
    - class: string - Additional CSS classes (optional)
    
    Slots:
    - title: Custom title slot
    - footer: Footer slot
    - default: Card content
--}}

@props(['title' => null, 'class' => ''])

<div {{ $attributes->merge(['class' => 'card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden ' . $class]) }}>
    @if($title || isset($title))
        <div class="card__header px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="card__title text-xl font-bold text-gray-900 dark:text-white">
                @if(isset($title))
                    {{ $title }}
                @else
                    {{ $title }}
                @endif
            </h3>
        </div>
    @endif
    
    <div class="card__content p-6 text-gray-700 dark:text-gray-300">
        {{ $slot }}
    </div>
    
    @isset($footer)
        <div class="card__footer px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            {{ $footer }}
        </div>
    @endisset
</div>
