@php
    $id = $id ?? 'toggle-' . uniqid();
    $checked = $checked ?? false;
    $label = $label ?? '';
    $disabled = $disabled ?? false;
    $xBind = $xBind ?? null;      // disabled状態を制御するAlpine.js変数
    $xModel = $xModel ?? null;    // 双方向バインディング用Alpine.js変数
@endphp

<div class="flex items-center space-x-3" @if($xBind) :class="{{ $xBind }} ? '' : 'opacity-50'" @elseif($disabled) class="opacity-50" @endif>
    <label for="{{ $id }}" class="relative inline-flex items-center mb-2" @if($xBind) :class="{{ $xBind }} ? 'cursor-pointer' : 'cursor-not-allowed'" @else class="{{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}" @endif>
        <input type="checkbox"
               id="{{ $id }}"
               name="{{ $name }}"
               @if($xModel) x-model="{{ $xModel }}" @else {{ $checked ? 'checked' : '' }} @endif
               @if($xBind) :disabled="!{{ $xBind }}" @elseif($disabled) disabled @endif
               class="sr-only peer">
        <div class="w-11 h-6 rounded-full transition-colors peer-focus:outline-none
            {{ $disabled && $checked ? 'bg-indigo-900 dark:bg-indigo-900' : '' }}
            {{ $disabled && !$checked ? 'bg-gray-300 dark:bg-gray-700' : '' }}
            {{ !$disabled ? 'bg-gray-200 dark:bg-gray-600 peer-checked:bg-indigo-600' : '' }}
        "></div>
        <div class="absolute left-1 top-1 w-4 h-4 bg-white border border-gray-300 rounded-full transition-all peer-checked:translate-x-full peer-checked:border-white"></div>
    </label>
    @if ($label)
        <span class="text-sm" @if($xBind) :class="{{ $xBind }} ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500'" @else class="{{ $disabled ? 'text-gray-400 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300' }}" @endif>{{ $label }}</span>
    @endif
</div>
