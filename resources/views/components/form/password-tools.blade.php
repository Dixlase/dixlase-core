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
    @include('components.form.text', [
        'type' => 'password',
        'name' => $name,
        'id' => $id,
        'required' => $required,
        'class' => 'pr-32',
    ])
    <script>
        document.getElementById('{{ $id }}').addEventListener('keyup', function() {
            PasswordTools.checkPasswordStrength('{{ $id }}');
        });
    </script>

    <!-- 自動生成ボタン -->
    <div class="group absolute top-0 right-20 h-full flex items-center">
        <button type="button" class="px-2 py-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
            onclick="PasswordTools.generatePassword('{{ $id }}', '{{ $id }}_confirmation')">
            <i class="fa-solid fa-random"></i>
        </button>
        <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 hidden group-hover:block
            text-xs rounded bg-gray-800 text-white px-2 py-1 whitespace-nowrap z-10">
            {{ __('password.tooltip.generate') }}
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
            {{ __('password.tooltip.copy') }}
        </span>
    </div>

    <!-- 表示切り替え -->
    <div class="group absolute top-0 right-2 h-full flex items-center">
        <button type="button" class="px-2 py-1 text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white"
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
    <fieldset>
        <legend>{{ __('admin.settings.members.form.password') }}（{{ __('common.confirm') }}）</legend>
        @include('components.form.text', [
            'type' => 'password',
            'name' => $name . '_confirmation',
            'id' => $id . '_confirmation',
            'required' => $required,
        ])
    </fieldset>
@endif



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
    <li id="req-lowercase" data-text="{{ __('common.password_messages.requirements.lowercase') }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ __('passwords.requirements.lowercase') }}</span>
    </li>
    <li id="req-number" data-text="{{ __('common.password_messages.requirements.number') }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ __('passwords.requirements.number') }}</span>
    </li>
    <li id="req-length" data-text="{{ $lengthText }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ $lengthText }}</span>
    </li>
    <li id="req-uppercase" data-text="{{ $uppercaseText }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ $uppercaseText }}</span>
    </li>
    <li id="req-symbol" data-text="{{ $symbolText }}" class="flex items-center">
        <i class="fas fa-times-circle text-red-500 mr-2"></i>
        <span>{{ $symbolText }}</span>
    </li>
</ul>


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
            const symbols = '!@#$%^&*()';
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

            const eyeIcon = document.getElementById('password-eye');
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
            const strengthBar = document.getElementById('password-strength-fill');
            const strengthMessage = document.getElementById('password-strength-message');
            const errorMessage = document.getElementById('password-validation-error');

            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(password);
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
            this.updateRequirementIndicator('req-length', length >= policy.minLength, true);
            this.updateRequirementIndicator('req-lowercase', hasLower, policy.requireLowercase);
            this.updateRequirementIndicator('req-number', hasNumber, policy.requireNumber);
            this.updateRequirementIndicator('req-uppercase', hasUpper, policy.requireUppercase);
            this.updateRequirementIndicator('req-symbol', hasSymbol, policy.requireSymbol);

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

                const hasAllTypes = hasUpper && hasLower && hasNumber && hasSymbol;

                if (length >= 16 && hasAllTypes) {
                    message = window.PasswordMessages.veryStrong;
                    barWidth = '100%';
                    barColor = 'bg-blue-500';
                } else if (length >= 12 && hasAllTypes) {
                    message = window.PasswordMessages.strong;
                    barWidth = '80%';
                    barColor = 'bg-green-500';
                } else if (length >= 12) {
                    message = window.PasswordMessages.normal;
                    barWidth = '60%';
                    barColor = 'bg-yellow-500';
                } else {
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
            if (!element) return;

            const baseText = element.dataset.text || '';
            const displayText = required ? baseText : `${baseText}`;
            const suffix = extraText || '';
            
            // FontAwesome アイコンを使用
            const iconElement = element.querySelector('i');
            const textElement = element.querySelector('span');
            
            if (iconElement && textElement) {
                if (isValid) {
                    // OK時: 緑のチェックアイコン
                    iconElement.className = 'fas fa-check-circle text-green-500 mr-2';
                } else {
                    // NG時: 赤のバツアイコン
                    iconElement.className = 'fas fa-times-circle text-red-500 mr-2';
                }
                textElement.textContent = `${displayText}${suffix}`;
            }
        }
    };
</script>




