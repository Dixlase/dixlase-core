@props([
    'id' => null,
    'name' => null,
    'value' => '',
    'rows' => 10,
    'placeholder' => '',
    'required' => false,
    'class' => '',
    'theme' => 'light',
])

@php
    $theme_class = $theme === 'light'
        ? 'bg-white text-gray-700 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
        : 'bg-gray-900 border-gray-500 focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<textarea
    name="{{ $name }}"
    id="{{ $id }}"
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline {{ $theme_class }} {{ $class }}"
>{{ $value }}</textarea>
