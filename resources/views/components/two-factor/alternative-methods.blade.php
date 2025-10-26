@props([
    'methods' => [],
    'currentMethod' => null,
    'context' => 'admin'
])

@if(count($methods) > 0)
<div class="text-center">
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
        {{ __('two-factor.alternative_methods_prompt') }}
    </p>
    <div class="space-x-4">
        @foreach($methods as $method)
            @if($method['value'] !== $currentMethod)
                <a href="{{ $method['url'] }}" class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                    {{ $method['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>
@endif
