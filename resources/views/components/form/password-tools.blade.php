@props([
    'name' => 'password',
    'id' => 'password',
    'required' => false,

    // 新しく追加するパスワードルール（JSにも渡す）
    'minLength' => 8,
    'requireUppercase' => true,
    'requireLowercase' => true,
    'requireNumber' => true,
    'requireSymbol' => false,

    // 推奨長（デフォルト12）
    'recommendedLength' => 12,

    // 確認欄の表示を制御（true = 表示, false = 非表示）
    'showConfirmation' => false,
])

<div class="relative flex items-center">
    <input type="password" name="{{ $name }}" id="{{ $id }}"
        class="w-full p-2 border rounded-lg pr-32 dark:bg-gray-800 dark:text-white"
        @if ($required) required @endif
        onkeyup="PasswordTools.checkPasswordStrength('{{ $id }}')">

    <!-- 自動生成ボタン -->
    <div class="group absolute right-20">
        <button type="button" class="px-3 py-2 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
            onclick="PasswordTools.generatePassword('{{ $id }}', '{{ $id }}_confirmation')">
            <i class="fa-solid fa-random"></i>
        </button>
        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block
            text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10">
            {{ __('password.tooltip.generate') }}
        </span>
    </div>

    <!-- コピー -->
    <div class="group absolute right-10">
        <button type="button" class="px-3 py-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            onclick="PasswordTools.copyPassword('{{ $id }}')">
            <i class="fa-solid fa-copy"></i>
        </button>
        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block
            text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10">
            {{ __('password.tooltip.copy') }}
        </span>
    </div>

    <!-- 表示切り替え -->
    <div class="group absolute right-4">
        <button type="button" class="text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white"
            onclick="PasswordTools.togglePassword('{{ $id }}', '{{ $id }}_confirmation', '{{ $id }}-eye')">
            <i id="password-eye" class="fa-solid fa-eye"></i>
        </button>
        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block
            text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10">
            {{ __('password.tooltip.toggle') }}
        </span>
    </div>
</div>

@if($showConfirmation)
    <div class="mt-4">
        <label for="{{ $id }}_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ __('パスワード（確認）') }}
        </label>
        <input type="password" name="{{ $name }}_confirmation" id="{{ $id }}_confirmation"
            class="w-full mt-1 p-2 border rounded-lg dark:bg-gray-800 dark:text-white"
            @if ($required) required @endif>
    </div>
@endif


<script>
    window.PasswordMessages = {
        error: @json(__('passwords.error')),
        weak: @json(__('passwords.requirements.weak')),
        normal: @json(__('passwords.requirements.normal')),
        strong: @json(__('passwords.requirements.strong')),
        veryStrong: @json(__('passwords.requirements.very_strong')),
    };

    window.PasswordTooltips = {
        generate: @json(__('passwords.tooltip.generate')),
        copy: @json(__('passwords.tooltip.copy')),
        toggle: @json(__('passwords.tooltip.toggle')),
    };

    window.PasswordPolicy = {
        minLength: @json($minLength),
        recommendedLength: @json($recommendedLength),
        requireUppercase: @json($requireUppercase),
        requireLowercase: @json($requireLowercase),
        requireNumber: @json($requireNumber),
        requireSymbol: @json($requireSymbol),
    };
</script>

@php
    $lengthText = $minLength < $recommendedLength
        ? __('passwords.requirements.length_full', ['min' => $minLength, 'recommended' => $recommendedLength])
        : __('passwords.requirements.length_simple', ['min' => $minLength]);

    $uppercaseText = $requireUppercase
        ? __('passwords.requirements.uppercase_required')
        : __('passwords.requirements.uppercase_optional');

    $symbolText = $requireSymbol
        ? __('passwords.requirements.symbol_required')
        : __('passwords.requirements.symbol_optional');
@endphp

<p id="password-strength-message" class="text-sm mt-1 text-gray-700 dark:text-gray-200 h-[1em]"></p>

<div id="password-strength-bar" class="h-2 w-32 bg-gray-200 dark:bg-gray-700 rounded-lg mt-2">
    <div id="password-strength-fill" class="h-2 bg-red-500 rounded-lg transition-all" style="width: 0%;"></div>
</div>

<!-- 確認欄の表示制御 -->


<ul id="password-requirements" class="text-sm mt-2 text-gray-600 dark:text-gray-300 space-y-1">
    <li id="req-lowercase" data-text="{{ __('common.password_messages.requirements.lowercase') }}">
        🔴 {{ __('passwords.requirements.lowercase') }}
    </li>
    <li id="req-number" data-text="{{ __('common.password_messages.requirements.number') }}">
        🔴 {{ __('passwords.requirements.number') }}
    </li>
    <li id="req-length" data-text="{{ $lengthText }}">
        🔴 {{ $lengthText }}
    </li>
    <li id="req-uppercase" data-text="{{ $uppercaseText }}">
        🔴 {{ $uppercaseText }}
    </li>
    <li id="req-symbol" data-text="{{ $symbolText }}">
        🔴 {{ $symbolText }}
    </li>
</ul>



