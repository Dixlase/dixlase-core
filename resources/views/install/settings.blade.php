@extends('layouts.install')

@section('title', __('install.settings_title'))
@section('header', __('install.settings_header'))
@section('description', __('install.settings_description'))

@section('content')

<form action="{{ route('install.settings.store') }}" method="POST" class="space-y-4">
    @csrf

    <div>
        <label class="block text-gray-700">{{ __('install.site_name') }}</label>
        <input type="text" name="site_name" value="{{ old('site_name', session('install_data.site_name', '')) }}" class="w-full p-2 border rounded-lg" required>
    </div>

    <div>
        <label class="block text-gray-700">{{ __('install.admin_name') }}</label>
        <input type="text" name="admin_name" id="admin_name"
            value="{{ old('admin_name', session('install_data.admin_name', '')) }}"
            class="w-full p-2 border rounded-lg"
            pattern="^[a-zA-Z0-9]+$"
            minlength="3"
            maxlength="20"
            required
            placeholder="{{ __('install.admin_name_placeholder') }}"
            oninvalid="setCustomValidity('{{ __('install.validation.admin_name_required') }}')"
            oninput="setCustomValidity('')">
        <p class="text-sm text-gray-600 mt-1">{{ __('install.admin_name_requirements') }}</p>
    </div>

    <div>
        <label class="block text-gray-700">{{ __('install.admin_email') }}</label>
        <input type="email" name="admin_email" value="{{ old('admin_email', session('install_data.admin_email', '')) }}" class="w-full p-2 border rounded-lg" required>
    </div>

    <!-- ✅ パスワード入力 -->
    <div>
        <label class="block text-gray-700">{{ __('install.admin_password') }}</label>
        <div class="relative flex items-center">
            <input type="password" name="admin_password" id="admin_password"
                class="w-full p-2 border rounded-lg pr-32" required onkeyup="checkPasswordStrength()">

            <!-- ✅ 自動生成ボタン -->
            <button type="button" class="group absolute right-20 px-3 py-2 text-green-600 hover:text-green-800" onclick="generatePassword()">
                <i class="fa-solid fa-random"></i>
                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100
                    bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                    {{ __('install.tooltip_generate') }}
                </span>
            </button>

            <!-- ✅ コピー -->
            <button type="button" class="group absolute right-10 px-3 py-2 text-blue-600 hover:text-blue-800" onclick="copyPassword()">
                <i class="fa-solid fa-copy"></i>
                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100
                    bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                    {{ __('install.tooltip_copy') }}
                </span>
            </button>

            <!-- ✅ 表示切り替え -->
            <button type="button" class="group absolute right-4 flex items-center text-gray-600 hover:text-gray-800"
                onclick="togglePassword()">
                <i id="password-eye" class="fa-solid fa-eye"></i>
                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100
                    bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                    {{ __('install.tooltip_toggle') }}
                </span>
            </button>
        </div>
        <!-- ✅ パスワード条件の明示 -->
        <ul id="password-requirements" class="text-sm mt-2 text-gray-600 space-y-1">
            <li id="req-length">🔴 {{ __('install.password_requirements.length') }}</li>
            <li id="req-uppercase">🔴 {{ __('install.password_requirements.uppercase') }}</li>
            <li id="req-lowercase">🔴 {{ __('install.password_requirements.lowercase') }}</li>
            <li id="req-number">🔴 {{ __('install.password_requirements.number') }}</li>
            <li id="req-symbol">🔴 {{ __('install.password_requirements.symbol') }}</li>
        </ul>


        <p id="password-strength-message" class="text-sm mt-1"></p>
        <div id="password-strength-bar" class="h-2 w-full bg-gray-200 rounded-lg mt-2">
            <div id="password-strength-fill" class="h-2 bg-red-500 rounded-lg transition-all" style="width: 0%;"></div>
        </div>
    </div>

    <!-- ✅ パスワード確認 -->
    <div>
        <label class="block text-gray-700">{{ __('install.admin_password_confirmation') }}</label>
        <input type="password" name="admin_password_confirmation" id="admin_password_confirmation"
            class="w-full p-2 border rounded-lg" required
            onpaste="return false;"
            oncopy="return false;"
            oncut="return false;"
            oncontextmenu="return false;">
        <p class="text-sm text-gray-600 mt-1">{{ __('install.admin_password_confirmation_note') }}</p>
    </div>

    <!-- ✅ ボタン（戻る・次へ） -->
    <div class="flex justify-between mt-6">
        <a href="{{ route('install.index') }}"
           class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition">
            {{ __('install.back') }}
        </a>
        <button type="submit"
                class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
            {{ __('install.next') }}
        </button>
    </div>
</form>

<script>
    // ✅ パスワードの表示・非表示切り替え
    function togglePassword() {
        let passwordInput = document.getElementById('admin_password');
        let confirmPasswordInput = document.getElementById('admin_password_confirmation');
        let passwordEye = document.getElementById('password-eye');

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            confirmPasswordInput.type = "text";
            passwordEye.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            passwordInput.type = "password";
            confirmPasswordInput.type = "password";
            passwordEye.classList.replace("fa-eye-slash", "fa-eye");
        }
    }

    // ✅ パスワード自動生成（強いパスワード）
    function generatePassword() {
        const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()";
        let password = "";
        for (let i = 0; i < 14; i++) { // 14文字以上
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        let passwordInput = document.getElementById('admin_password');
        let confirmPasswordInput = document.getElementById('admin_password_confirmation');

        passwordInput.value = password;
        confirmPasswordInput.value = password;

        // 自動生成時は強制的に表示
        passwordInput.type = "text";
        confirmPasswordInput.type = "text";
        document.getElementById('password-eye').classList.replace("fa-eye", "fa-eye-slash");

        checkPasswordStrength(); // 強度チェック実行（必ず「強い」に）
    }

    // ✅ パスワードコピー
    function copyPassword() {
        let passwordInput = document.getElementById('admin_password');
        navigator.clipboard.writeText(passwordInput.value).then(() => {
            alert("パスワードがコピーされました！");
        });
    }

    // 言語ファイルのメッセージを取得
    const passwordMessages = {
        length: "{{ __('install.password_requirements.length') }}",
        uppercase: "{{ __('install.password_requirements.uppercase') }}",
        lowercase: "{{ __('install.password_requirements.lowercase') }}",
        number: "{{ __('install.password_requirements.number') }}",
        symbol: "{{ __('install.password_requirements.symbol') }}",
        weak: "{{ __('install.password_strength_messages.weak') }}",
        medium: "{{ __('install.password_strength_messages.medium') }}",
        strong: "{{ __('install.password_strength_messages.strong') }}"
    };


    // ✅ パスワード強度チェック
    function checkPasswordStrength() {
        let password = document.getElementById('admin_password').value;
        let strengthBar = document.getElementById('password-strength-fill');
        let strengthMessage = document.getElementById('password-strength-message');

        let lengthValid = password.length >= 8;
        let uppercaseValid = /[A-Z]/.test(password);
        let lowercaseValid = /[a-z]/.test(password);
        let numberValid = /[0-9]/.test(password);
        let symbolValid = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        let isLong = password.length >= 12;

        let message = passwordMessages.weak;
        let barWidth = "30%";
        let barColor = "bg-red-500";

        if (lengthValid && uppercaseValid && lowercaseValid && numberValid) {
            message = isLong || symbolValid ? passwordMessages.strong : passwordMessages.medium;
            barWidth = isLong || symbolValid ? "100%" : "60%";
            barColor = isLong || symbolValid ? "bg-green-500" : "bg-yellow-500";
        }

        strengthMessage.innerText = message;
        strengthBar.style.width = barWidth;
        strengthBar.className = `h-2 rounded-lg transition-all ${barColor}`;
    }

    // ✅ パスワード確認欄でコピー＆ペーストを禁止
    document.getElementById("admin_password_confirmation").addEventListener("paste", function(e) {
        e.preventDefault();
        alert("{{ __('install.password_paste_error') }}");
    });

    document.getElementById("admin_password_confirmation").addEventListener("copy", function(e) {
        e.preventDefault();
    });

    document.getElementById("admin_password_confirmation").addEventListener("cut", function(e) {
        e.preventDefault();
    });

    document.getElementById("admin_password_confirmation").addEventListener("contextmenu", function(e) {
        e.preventDefault();
    });

</script>

@endsection