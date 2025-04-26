export function togglePassword(passwordId = 'password', confirmId = 'password_confirmation', eyeId = 'password-eye') {
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
}

export function generatePassword(passwordId = 'password', confirmId = 'password_confirmation') {
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

    // ⛳ 強いパスワード用に16文字をデフォルトとする
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

    checkPasswordStrength(passwordId);
}

export function copyPassword(passwordId = 'password') {
    const passwordInput = document.getElementById(passwordId);
    if (passwordInput) {
        navigator.clipboard.writeText(passwordInput.value).then(() => {
            alert('パスワードがコピーされました！');
        });
    }
}

const messages = window.PasswordMessages || {
    error: window.PasswordMessages?.error || '条件を満たしていません',
    normal: window.PasswordMessages?.normal || '普通の強度',
    strong: window.PasswordMessages?.strong || '強いパスワード',
};


export function checkPasswordStrength(passwordId = 'password') {
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
    updateRequirementIndicator('req-length', length >= policy.minLength, true);
    updateRequirementIndicator('req-lowercase', hasLower, policy.requireLowercase);
    updateRequirementIndicator('req-number', hasNumber, policy.requireNumber);
    updateRequirementIndicator('req-uppercase', hasUpper, policy.requireUppercase);
    updateRequirementIndicator('req-symbol', hasSymbol, policy.requireSymbol);

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
}

function updateRequirementIndicator(elementId, isValid, required = false, extraText = '') {
    const element = document.getElementById(elementId);
    if (!element) return;

    const baseText = element.dataset.text || '';
    const displayText = required ? baseText : `${baseText}`;
    const suffix = extraText || '';
    const statusIcon = isValid ? '🟢' : '🔴';

    // 先頭アイコンだけ変える
    element.innerHTML = `${statusIcon} ${displayText}${suffix}`;
}
