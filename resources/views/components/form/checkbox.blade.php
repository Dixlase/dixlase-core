@props([
    'label' => '', // チェックボックスのラベル
    'name' => '',          // チェックボックスの共通name
    'value' => false,        // 現在の選択値
    'class' => '',         // カスタムクラス
    'xModel' => null, // Alpine.jsのx-model属性
])

<div class="flex flex-wrap gap-4">
        <input type="hidden" name="{{ $name }}" value="0">
        <label class="inline-flex items-center">
            <input type="checkbox"
                id="{{ $name }}"
                name="{{ $name }}"
                value="1"
                {{ $xModel ? "x-model=$xModel" : '' }}
                class="{{ config('admin.appearance_class.form.checkbox') }} {{ $class }}"
                @if ($value) checked @endif>
            <span class="ml-2">{{ __($label) }}</span>
        </label>
</div>
