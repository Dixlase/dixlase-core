@props([
    'id' => null, // selectのid属性
    'name' => null, // selectのname属性
    'options' => [], // 選択肢の配列
    'value' => null, // 初期選択値
    'class' => 'default-class', // 追加クラス
    'required' => false, // 必須フラグ
    'theme' => 'light', // テーマ
])

@php
    $theme_class = $theme === 'light' ? 'bg-white' : 'bg-gray-900';
@endphp

<select id="{{ $id }}"
        name="{{ $name }}"
        class="mt-1 block rounded-md shadow-sm {{ $theme_class }} {{ $class }}"
        @if ($required) required @endif>
    @foreach ($options as $optionValue => $optionText)
        <option value="{{ $optionValue }}" {{ $value == $optionValue ? 'selected' : '' }}>
            {{ $optionText }}
        </option>
    @endforeach
</select>
