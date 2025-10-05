{{--
    Breadcrumb Component
    
    Usage:
    <x-front.breadcrumb :items="$breadcrumbs" />
    
    Props:
    - items: array - Breadcrumb items [['label' => 'Home', 'url' => '/'], ...]
    - class: string - Additional CSS classes (optional)
--}}

@props(['items' => [], 'class' => ''])

@if(count($items) > 0)
<nav {{ $attributes->merge(['class' => 'breadcrumb ' . $class, 'aria-label' => 'Breadcrumb']) }}>
    <ol class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
        @foreach($items as $index => $item)
            <li class="breadcrumb__item flex items-center">
                @if($index > 0)
                    <span class="breadcrumb__separator mx-2 text-gray-400">/</span>
                @endif
                
                @if(isset($item['url']) && $index < count($items) - 1)
                    <a 
                        href="{{ $item['url'] }}" 
                        class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                    >
                        {{ $item['label'] ?? '' }}
                    </a>
                @else
                    <span class="text-gray-900 dark:text-white font-medium" aria-current="page">
                        {{ $item['label'] ?? '' }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
