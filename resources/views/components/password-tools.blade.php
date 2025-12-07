{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

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

<div class="relative">
    <x-form.text
        type="password"
        :id="$id"
        :name="$name"
        value=""
        :required="$required"
        autocomplete="new-password"
        class="password-input input-full"
    />

    <!-- 自動生成ボタン -->
    <div class="group absolute top-0 right-20 h-full flex items-center">
        <button type="button" class="px-2 py-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
            onclick="PasswordTools.generatePassword('{{ $id }}', '{{ $id }}_confirmation')">
            <i class="fa-solid fa-random"></i>
        </button>
        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block
            text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10">
            {{ __('components.password_messages.tooltip.generate') }}
        </span>
    </div>

    <!-- コピー -->
    <div class="group absolute top-0 right-12 h-full flex items-center">
        <button type="button" class="px-2 py-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            onclick="PasswordTools.copyPassword('{{ $id }}')">
            <i class="fa-solid fa-copy"></i>
        </button>
        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block
            text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10">
            {{ __('components.password_messages.tooltip.copy') }}
        </span>
    </div>

    <!-- 表示切り替え -->
    <div class="group absolute top-0 right-2 h-full flex items-center">
        <button type="button" class="px-2 py-1 text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white"
            onclick="PasswordTools.togglePassword('{{ $id }}', '{{ $id }}_confirmation', '{{ $id }}-eye')">
            <i id="{{ $id }}-eye" class="fa-solid fa-eye"></i>
        </button>
        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block
            text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10">
            {{ __('components.password_messages.tooltip.toggle') }}
        </span>
    </div>
</div>

@if($showConfirmation)
    <fieldset>
        <legend class="text-gray-700 dark:text-gray-300">{{ __('common.password') }}（{{ __('common.confirm') }}）</legend>
        <x-form.text
            type="password"
            :id="$id . '_confirmation'"
            :name="$name . '_confirmation'"
            value=""
            :required="$required"
            autocomplete="new-password"
            class="password-confirmation-input"
        />
    </fieldset>
@endif



@php
    // 長さの要件（常に必須）
    $lengthBase = $minLength < $recommendedLength
        ? __('components.password_messages.requirements.length_full', ['min' => $minLength, 'recommended' => $recommendedLength])
        : __('components.password_messages.requirements.length_simple', ['min' => $minLength]);
    $lengthText = $lengthBase . '（' . __('common.required') . '）';

    // 小文字の要件（常に必須）
    $lowercaseText = __('components.password_messages.requirements.lowercase') . '（' . __('common.required') . '）';

    // 数字の要件（常に必須）
    $numberText = __('components.password_messages.requirements.number') . '（' . __('common.required') . '）';

    // 大文字の要件（必須 or 任意）
    if ($requireUppercase) {
        $uppercaseText = __('components.password_messages.requirements.uppercase') . '（' . __('common.required') . '）';
    } else {
        $uppercaseText = __('components.password_messages.requirements.uppercase_optional_note') . '（' . __('common.optional') . '）';
    }

    // 記号の要件（必須 or 任意）
    if ($requireSymbol) {
        $symbolText = __('components.password_messages.requirements.symbol') . '（' . __('common.required') . '）';
    } else {
        $symbolText = __('components.password_messages.requirements.symbol_optional_note') . '（' . __('common.optional') . '）';
    }
@endphp

<p id="{{ $id }}-strength-message" class="text-sm mt-1 text-gray-700 dark:text-gray-300 h-[1em]"></p>

<div id="{{ $id }}-strength-bar" class="h-2 w-32 bg-gray-200 dark:bg-gray-700 rounded-lg mt-2">
    <div id="{{ $id }}-strength-fill" class="h-2 bg-red-500 rounded-lg transition-all" style="width: 0%;"></div>
</div>

<!-- パスワード要件リスト -->
<ul id="{{ $id }}-requirements" class="text-sm mt-2 text-gray-700 dark:text-gray-300 space-y-1">
    <li id="{{ $id }}-req-lowercase" data-text="{{ $lowercaseText }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ $lowercaseText }}</span>
    </li>
    <li id="{{ $id }}-req-number" data-text="{{ $numberText }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ $numberText }}</span>
    </li>
    <li id="{{ $id }}-req-length" data-text="{{ $lengthText }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ $lengthText }}</span>
    </li>
    <li id="{{ $id }}-req-uppercase" data-text="{{ $uppercaseText }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ $uppercaseText }}</span>
    </li>
    <li id="{{ $id }}-req-symbol" data-text="{{ $symbolText }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ $symbolText }}</span>
    </li>
</ul>


<script>
    window.PasswordMessages = {
        error: @json(__('components.password_messages.error')),
        weak: @json(__('components.password_messages.requirements.weak')),
        normal: @json(__('components.password_messages.requirements.normal')),
        strong: @json(__('components.password_messages.requirements.strong')),
        veryStrong: @json(__('components.password_messages.requirements.very_strong')),
        requiredLabel: @json(__('components.password_messages.requirements.required_label')),
        optionalLabel: @json(__('components.password_messages.requirements.optional_label')),
    };

    window.PasswordTooltips = {
        generate: @json(__('components.password_messages.tooltip.generate')),
        copy: @json(__('components.password_messages.tooltip.copy')),
        toggle: @json(__('components.password_messages.tooltip.toggle')),
    };

    window.PasswordPolicy = {
        minLength: @json($minLength),
        recommendedLength: @json($recommendedLength),
        requireUppercase: @json($requireUppercase),
        requireLowercase: @json($requireLowercase),
        requireNumber: @json($requireNumber),
        requireSymbol: @json($requireSymbol),
    };

    // PasswordTools 機能を統合
    window.PasswordTools = {
        togglePassword: function(passwordId = 'password', confirmId = 'password_confirmation', eyeId = 'password-eye') {
            const passwordInput = document.getElementById(passwordId);
            const confirmInput = document.getElementById(confirmId);
            const eyeIcon = document.getElementById(eyeId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                if (confirmInput) confirmInput.type = 'text';
                if (eyeIcon) eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                if (confirmInput) confirmInput.type = 'password';
                if (eyeIcon) eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        },

        generatePassword: function(passwordId = 'password', confirmId = 'password_confirmation') {
            const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const lowercase = 'abcdefghijklmnopqrstuvwxyz';
            const numbers = '0123456789';
            const symbols = '!@#$%^&*()_-+=[]{}|:;"<>,.?/~';
            const allChars = uppercase + lowercase + numbers + symbols;

            const policy = window.PasswordPolicy || {
                minLength: 8,
                recommendedLength: 12,
                requireUppercase: true,
                requireLowercase: true,
                requireNumber: true,
                requireSymbol: false,
            };

            // 強いパスワード用に16文字をデフォルトとする
            const desiredLength = Math.max(policy.minLength, policy.recommendedLength, 16);

            // 必須文字をそれぞれ1文字ずつ入れる
            let password = [];

            if (policy.requireUppercase) {
                password.push(uppercase.charAt(Math.floor(Math.random() * uppercase.length)));
            }
            if (policy.requireLowercase) {
                password.push(lowercase.charAt(Math.floor(Math.random() * lowercase.length)));
            }
            if (policy.requireNumber) {
                password.push(numbers.charAt(Math.floor(Math.random() * numbers.length)));
            }
            if (policy.requireSymbol) {
                password.push(symbols.charAt(Math.floor(Math.random() * symbols.length)));
            }

            // 必須以外の残りを埋める
            while (password.length < desiredLength) {
                password.push(allChars.charAt(Math.floor(Math.random() * allChars.length)));
            }

            // シャッフル
            password = password.sort(() => Math.random() - 0.5).join('');

            const passwordInput = document.getElementById(passwordId);
            const confirmInput = document.getElementById(confirmId);

            if (passwordInput) {
                passwordInput.value = password;
                passwordInput.type = 'text';
            }
            if (confirmInput) {
                confirmInput.value = password;
                confirmInput.type = 'text';
            }

            const eyeIcon = document.getElementById(passwordId + '-eye');
            if (eyeIcon) eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');

            this.checkPasswordStrength(passwordId);
        },

        copyPassword: function(passwordId = 'password') {
            const passwordInput = document.getElementById(passwordId);
            if (passwordInput) {
                navigator.clipboard.writeText(passwordInput.value).then(() => {
                    alert('パスワードがコピーされました！');
                });
            }
        },

        checkPasswordStrength: function(passwordId = 'password') {
            const password = document.getElementById(passwordId)?.value || '';
            const strengthBar = document.getElementById(passwordId + '-strength-fill');
            const strengthMessage = document.getElementById(passwordId + '-strength-message');
            const errorMessage = document.getElementById(passwordId + '-validation-error');

            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSymbol = /[!@#$%^&*()_\-+=\[\]{}|\\:;"'<>,.?/~`]/.test(password);
            const length = password.length;

            const policy = window.PasswordPolicy || {
                minLength: 8,
                recommendedLength: 12,
                requireUppercase: true,
                requireLowercase: true,
                requireNumber: true,
                requireSymbol: false,
            };

            // インジケーター更新
            this.updateRequirementIndicator(passwordId + '-req-length', length >= policy.minLength, true);
            this.updateRequirementIndicator(passwordId + '-req-lowercase', hasLower, policy.requireLowercase);
            this.updateRequirementIndicator(passwordId + '-req-number', hasNumber, policy.requireNumber);
            this.updateRequirementIndicator(passwordId + '-req-uppercase', hasUpper, policy.requireUppercase);
            this.updateRequirementIndicator(passwordId + '-req-symbol', hasSymbol, policy.requireSymbol);

            const allRequiredValid =
                length >= policy.minLength &&
                (!policy.requireLowercase || hasLower) &&
                (!policy.requireNumber || hasNumber) &&
                (!policy.requireUppercase || hasUpper) &&
                (!policy.requireSymbol || hasSymbol);

            let message = '';
            let barWidth = '0%';
            let barColor = 'bg-red-500';

            if (!allRequiredValid) {
                message = window.PasswordMessages.error;
                barWidth = '20%';
                barColor = 'bg-red-500';
                errorMessage?.classList.remove('hidden');
            } else {
                errorMessage?.classList.add('hidden');

                // 大文字・小文字・数字・記号の全てを含むか
                const hasAllTypes = hasUpper && hasLower && hasNumber && hasSymbol;
                // 記号以外の3タイプ（大文字・小文字・数字）を含むか
                const hasThreeTypes = hasUpper && hasLower && hasNumber;

                if (hasAllTypes && length >= 16) {
                    // 全タイプ + 16文字以上 → 非常に強い
                    message = window.PasswordMessages.veryStrong;
                    barWidth = '100%';
                    barColor = 'bg-blue-500';
                } else if (hasAllTypes && length >= 12) {
                    // 全タイプ + 12文字以上 → 強い
                    message = window.PasswordMessages.strong;
                    barWidth = '80%';
                    barColor = 'bg-green-500';
                } else if (hasAllTypes && length < 12) {
                    // 全タイプだが12文字未満 → 普通
                    message = window.PasswordMessages.normal;
                    barWidth = '60%';
                    barColor = 'bg-yellow-500';
                } else if (!policy.requireSymbol && hasThreeTypes && length >= policy.minLength) {
                    // 記号が任意 かつ 3タイプ（大文字・小文字・数字）+ 最小文字数以上 → 普通
                    message = window.PasswordMessages.normal;
                    barWidth = '60%';
                    barColor = 'bg-yellow-500';
                } else if (length >= 12) {
                    // 12文字以上（但し条件不足）→ 普通
                    message = window.PasswordMessages.normal;
                    barWidth = '60%';
                    barColor = 'bg-yellow-500';
                } else {
                    // その他（必須条件のみ満たす）→ 弱い
                    message = window.PasswordMessages.weak;
                    barWidth = '40%';
                    barColor = 'bg-orange-400';
                }
            }

            if (strengthMessage) strengthMessage.innerText = message;
            if (strengthBar) {
                strengthBar.style.width = barWidth;
                strengthBar.className = `h-2 rounded-lg transition-all ${barColor}`;
            }
        },

        updateRequirementIndicator: function(elementId, isValid, required = false, extraText = '') {
            const element = document.getElementById(elementId);
            
            if (!element) {
                return;
            }

            // data-text属性の値をそのまま使用（Blade側で既に必須/任意が含まれている）
            const displayText = element.dataset.text || '';
            const suffix = extraText || '';
            
            // アイコンとテキスト要素を取得（子要素として直接取得）
            const children = element.children;
            let iconElement = null;
            let textElement = null;
            
            // 子要素を順に確認
            // FontAwesome 6は <i> を <svg> に変換するため、両方をチェック
            for (let i = 0; i < children.length; i++) {
                const child = children[i];
                
                // アイコン要素: <i> または <svg> (FontAwesome変換後)
                if (child.tagName === 'I' || child.tagName === 'SVG' || child.tagName === 'svg') {
                    iconElement = child;
                } else if (child.tagName === 'SPAN') {
                    textElement = child;
                }
            }
            
            if (iconElement && textElement) {
                // アイコンのクラスを更新（FontAwesomeのSVG変換に対応）
                // 既存のクラスを保持しながら、アイコンと色のクラスのみを変更
                
                // 古いアイコンと色のクラスを削除
                iconElement.classList.remove('fa-times-circle', 'fa-check-circle');
                iconElement.classList.remove('text-red-500', 'text-green-500');
                
                // 新しいアイコンと色のクラスを追加
                if (isValid) {
                    iconElement.classList.add('fa-check-circle', 'text-green-500');
                } else {
                    iconElement.classList.add('fa-times-circle', 'text-red-500');
                }
                
                // 必須のクラスが存在することを確認（FontAwesome変換後も維持）
                if (!iconElement.classList.contains('fas')) {
                    iconElement.classList.add('fas');
                }
                if (!iconElement.classList.contains('mr-2')) {
                    iconElement.classList.add('mr-2');
                }
                
                textElement.textContent = `${displayText}${suffix}`;
            }
        }
    };

    // パスワード入力フィールドのイベントリスナーを設定
    (function() {
        const passwordId = '{{ $id }}';
        
        const setupPasswordListener = function() {
            const passwordInput = document.getElementById(passwordId);
            if (passwordInput && !passwordInput.hasAttribute('data-password-listener')) {
                passwordInput.setAttribute('data-password-listener', 'true');
                passwordInput.addEventListener('keyup', function() {
                    if (typeof PasswordTools !== 'undefined' && typeof PasswordTools.checkPasswordStrength === 'function') {
                        PasswordTools.checkPasswordStrength(passwordId);
                    }
                });
                // 初期チェック
                if (typeof PasswordTools !== 'undefined' && typeof PasswordTools.checkPasswordStrength === 'function') {
                    PasswordTools.checkPasswordStrength(passwordId);
                }
            }
        };

        // DOMとPasswordToolsの両方が準備できるまで待つ
        const initListener = function() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupPasswordListener);
            } else {
                // DOMは既に読み込まれているが、PasswordToolsが定義されているか確認
                if (typeof PasswordTools !== 'undefined') {
                    setupPasswordListener();
                } else {
                    // PasswordToolsの定義を少し待つ
                    setTimeout(setupPasswordListener, 50);
                }
            }
        };
        
        initListener();
    })();
</script>




