@php
    $id = $id ?? 'toggle-' . uniqid();
    $checked = $checked ?? false;
    $label = $label ?? '';
    $disabled = $disabled ?? false;
    $xBind = $xBind ?? null;      // disabled状態を制御するAlpine.js変数
    $xModel = $xModel ?? null;    // 双方向バインディング用Alpine.js変数
    $color = $color ?? 'blue';    // ON時の背景色
    
    // 色のマッピング（ライト/ダークモード対応）
    $colorClasses = [
        'primary' => 'peer-checked:bg-blue-600 dark:peer-checked:bg-blue-500',
        'secondary' => 'peer-checked:bg-gray-600 dark:peer-checked:bg-gray-400',
        'success' => 'peer-checked:bg-green-600 dark:peer-checked:bg-green-500',
        'warning' => 'peer-checked:bg-yellow-500 dark:peer-checked:bg-yellow-400',
        'danger' => 'peer-checked:bg-red-600 dark:peer-checked:bg-red-500',
        'blue' => 'peer-checked:bg-blue-600 dark:peer-checked:bg-blue-500',
        'green' => 'peer-checked:bg-green-600 dark:peer-checked:bg-green-500',
        'yellow' => 'peer-checked:bg-yellow-500 dark:peer-checked:bg-yellow-400',
        'orange' => 'peer-checked:bg-orange-500 dark:peer-checked:bg-orange-400',
        'red' => 'peer-checked:bg-red-600 dark:peer-checked:bg-red-500',
        'purple' => 'peer-checked:bg-purple-600 dark:peer-checked:bg-purple-500',
        'gray' => 'peer-checked:bg-gray-600 dark:peer-checked:bg-gray-400',
    ];
    
    // disabled時の色マッピング
    $disabledColorClasses = [
        'primary' => 'bg-blue-900 dark:bg-blue-900',
        'secondary' => 'bg-gray-700 dark:bg-gray-700',
        'success' => 'bg-green-900 dark:bg-green-900',
        'warning' => 'bg-yellow-800 dark:bg-yellow-800',
        'danger' => 'bg-red-900 dark:bg-red-900',
        'blue' => 'bg-blue-900 dark:bg-blue-900',
        'green' => 'bg-green-900 dark:bg-green-900',
        'yellow' => 'bg-yellow-800 dark:bg-yellow-800',
        'orange' => 'bg-orange-800 dark:bg-orange-800',
        'red' => 'bg-red-900 dark:bg-red-900',
        'purple' => 'bg-purple-900 dark:bg-purple-900',
        'gray' => 'bg-gray-700 dark:bg-gray-700',
    ];
    
    $checkedClass = $colorClasses[$color] ?? $colorClasses['blue'];
    $disabledCheckedClass = $disabledColorClasses[$color] ?? $disabledColorClasses['blue'];
@endphp

<div class="flex items-center space-x-3 my-3" @if($xBind) :class="{{ $xBind }} ? '' : 'opacity-50'" @elseif($disabled) class="opacity-50" @endif>
    <label for="{{ $id }}" class="relative inline-flex items-center" @if($xBind) :class="{{ $xBind }} ? 'cursor-pointer' : 'cursor-not-allowed'" @else class="{{ $disabled ? 'cursor-not-allowed' : 'cursor-pointer' }}" @endif>
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox"
               id="{{ $id }}"
               name="{{ $name }}"
               value="1"
               @if($xModel) :checked="{{ $xModel }} == '1'" @change="{{ $xModel }} = $event.target.checked ? '1' : '0'" @else {{ $checked ? 'checked' : '' }} @endif
               @if($xBind) :disabled="!{{ $xBind }}" @elseif($disabled) disabled @endif
               class="sr-only peer">
        <div class="w-11 h-6 rounded-full transition-colors peer-focus:outline-none
            {{ $disabled && $checked ? $disabledCheckedClass : '' }}
            {{ $disabled && !$checked ? 'bg-gray-300 dark:bg-gray-700' : '' }}
            {{ !$disabled ? 'bg-gray-200 dark:bg-gray-600 ' . $checkedClass : '' }}
        "></div>
        <div class="absolute left-1 top-1 w-4 h-4 bg-white border border-gray-300 rounded-full transition-all peer-checked:translate-x-full peer-checked:border-white"></div>
    </label>
    @if ($label)
        <span class="leading-none text-sm {{ $disabled ? 'text-gray-400 dark:text-gray-500' : 'text-gray-700 dark:text-gray-300' }}" @if($xBind) :class="{{ $xBind }} ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-500'" @endif>{{ $label }}</span>
    @endif
</div>
