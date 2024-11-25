@props([
    'name' => '',          // チェックボックスの共通name
    'options' => [],       // 選択肢（キー: name, 値: label）
    'values' => [],        // 現在の選択値
    'class' => '',         // カスタムクラス
    'theme' => 'light',  // テーマ
])

@php
    $theme_class = $theme === 'light'
        ? 'text-gray-600'
        : 'text-gray-600';
@endphp

<div class="flex flex-wrap gap-4">
    @foreach ($options as $optionName => $optionLabel)
        <input type="hidden" name="{{ $name }}[{{ $optionName }}]" value="0">
        <label class="inline-flex items-center">
            <input type="checkbox"
                id="{{ $optionName }}"
                name="{{ $name }}[{{ $optionName }}]"
                value="1"
                class="{{ $theme_class }} {{ $class }}"
                @if (!empty($values[$optionName])) checked @endif>
            <span class="ml-2">{{ $optionLabel }}</span>
        </label>
    @endforeach
</div>
