@php
    $id = $id ?? 'toggle-' . uniqid();
    $checked = $checked ?? false;
    $label = $label ?? '';
@endphp

<div class="flex items-center space-x-3">
    <label for="{{ $id }}" class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox"
               id="{{ $id }}"
               name="{{ $name }}"
               {{ $checked ? 'checked' : '' }}
               class="sr-only peer">
        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none dark:bg-gray-600 rounded-full peer peer-checked:bg-indigo-600 transition-colors"></div>
        <div class="absolute left-1 top-1 w-4 h-4 bg-white border border-gray-300 rounded-full transition-all peer-checked:translate-x-full peer-checked:border-white"></div>
    </label>
    @if ($label)
        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
    @endif
</div>
