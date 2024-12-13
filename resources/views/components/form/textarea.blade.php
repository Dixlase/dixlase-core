@props([
    'id' => null,
    'name' => null,
    'value' => '',
    'rows' => 10,
    'placeholder' => '',
    'required' => false,

    'readonly' => false,
    'class' => '',
    'xBindReadonly' => null,  // Alpine.jsのx-bind:readonly
    'xBindClass' => null,     // Alpine.jsのx-bind:class
])

<textarea
    name="{{ $name }}"
    id="{{ $id }}"
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    class="shadow appearance-none border rounded w-full py-2 px-3 leading-tight focus:outline-none focus:shadow-outline {{ config('admin.appearance_class.form.textarea') }} {{ $class }}"


    {{ $xBindReadonly ? "x-bind:readonly=$xBindReadonly" : '' }}
    {{ $xBindClass ? "x-bind:class=$xBindClass" : '' }}
>{{ old($name, $value) }}
</textarea>
