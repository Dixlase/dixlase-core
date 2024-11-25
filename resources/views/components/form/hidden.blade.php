@props([
    'id' => null,      // hiddenのid属性
    'name' => '',      // hiddenのname属性
    'value' => '',     // hiddenのvalue属性
])

<input type="hidden"
    @if ($id) id="{{ $id }}" @endif
    name="{{ $name }}"
    value="{{ $value }}">
