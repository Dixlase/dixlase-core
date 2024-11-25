@props([
    'name' => '',      // radioのname属性
    'options' => [],   // 選択肢の配列
    'value' => '',     // 現在の選択値
    'class' => '',     // カスタムクラス
    'theme' => 'light' // テーマ
])

@php
    $theme_class = $theme === 'light'
        ? 'text-gray-600'
        : 'text-gray-600';
@endphp

<div class="flex items-center space-x-4">
    @foreach ($options as $optionValue => $optionLabel)
        <label class="inline-flex items-center">
            <input type="radio"
                name="{{ $name }}"
                value="{{ $optionValue }}"
                class="{{ $theme_class }} {{ $class }}"
                @if ($value == $optionValue) checked @endif>
            <span class="ml-2">{{ $optionLabel }}</span>
        </label>
    @endforeach
</div>
