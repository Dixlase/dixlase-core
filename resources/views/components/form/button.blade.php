@props([
    'type' => 'button',      // ボタンのタイプ (button, submit, reset)
    'class' => '',           // カスタムクラス
    'label' => 'Button',     // ボタンのテキスト
    'onclick' => null,       // onclick属性を追加
    'theme' => 'light',      // テーマ
])

@php
    // テーマに応じたクラス設定
    $theme_class = $theme === 'light'
        ? 'bg-indigo-600 text-white hover:bg-indigo-500 focus:outline-none'
        : 'bg-indigo-600 text-white hover:bg-indigo-500 focus:outline-none';
@endphp


<button type="{{ $type }}"
    @if ($onclick) onclick="{{ $onclick }}" @endif
    class="py-2 px-4 rounded-md shadow-sm {{ $theme_class }} {{ $class }}">
    {{ $label }}
</button>
