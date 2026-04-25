/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Alpine.js Password Tools Component
 * 
 * Usage in Blade:
 * <div x-data="passwordTools({ ... })" ...>
 */
window.passwordTools = function (config = {}) {
    return {
        password: '',
        passwordConfirmation: '',
        showPassword: false,
        showConfirmation: config.showConfirmation || false,
        showConfirmationOnChange: config.showConfirmationOnChange || false,
        disableConfirmationCopyPaste: config.disableConfirmationCopyPaste || false,
        confirmationInputId: config.confirmationInputId || '',

        // Password policy
        policy: {
            minLength: config.minLength || 8,
            recommendedLength: config.recommendedLength || 12,
            requireUppercase: config.requireUppercase !== false,
            requireLowercase: config.requireLowercase !== false,
            requireNumber: config.requireNumber !== false,
            requireSymbol: config.requireSymbol || false,
        },

        // Messages (will be populated from data attributes in init)
        messages: {},

        init() {
            // Read messages from data attributes
            const el = this.$el;
            this.messages = {
                error: el.dataset.msgError || 'エラー',
                weak: el.dataset.msgWeak || '弱い',
                normal: el.dataset.msgNormal || '普通',
                strong: el.dataset.msgStrong || '強い',
                veryStrong: el.dataset.msgVeryStrong || '非常に強い',
                copySuccess: 'パスワードがコピーされました！',
                pasteError: el.dataset.msgPasteError || 'パスワード確認欄へのペーストは禁止されています。'
            };

            this.$watch('password', () => {
                this.checkPasswordStrength();
                if (this.showConfirmation) {
                    this.checkPasswordMatch();
                }
            });

            this.$watch('passwordConfirmation', () => {
                if (this.showConfirmation) {
                    this.checkPasswordMatch();
                }
            });

            // 確認欄のコピペ禁止を設定
            if (this.disableConfirmationCopyPaste && this.confirmationInputId) {
                this.$nextTick(() => {
                    this.setupConfirmationCopyPasteProtection();
                });
            }
        },

        // 確認欄のコピペ禁止を設定
        setupConfirmationCopyPasteProtection() {
            const confirmInput = document.getElementById(this.confirmationInputId);

            if (confirmInput) {
                confirmInput.addEventListener('paste', (e) => {
                    e.preventDefault();
                    alert(this.messages.pasteError);
                });

                confirmInput.addEventListener('copy', (e) => {
                    e.preventDefault();
                });

                confirmInput.addEventListener('cut', (e) => {
                    e.preventDefault();
                });

                confirmInput.addEventListener('contextmenu', (e) => {
                    e.preventDefault();
                });
            }
        },

        // 確認欄を表示するかどうか
        get shouldShowConfirmation() {
            if (!this.showConfirmation) return false;
            if (!this.showConfirmationOnChange) return true;
            return this.password !== '';
        },

        // パスワード表示切り替え
        togglePasswordVisibility() {
            this.showPassword = !this.showPassword;
        },

        // パスワード生成
        generatePassword() {
            const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const lowercase = 'abcdefghijklmnopqrstuvwxyz';
            const numbers = '0123456789';
            const symbols = '!@#$%^&*()_-+=[]{}|:;"<>,.?/~';
            const allChars = uppercase + lowercase + numbers + symbols;

            const desiredLength = Math.max(this.policy.minLength, this.policy.recommendedLength, 16);
            let password = [];

            // 必須文字を1文字ずつ追加
            if (this.policy.requireUppercase) {
                password.push(uppercase.charAt(Math.floor(Math.random() * uppercase.length)));
            }
            if (this.policy.requireLowercase) {
                password.push(lowercase.charAt(Math.floor(Math.random() * lowercase.length)));
            }
            if (this.policy.requireNumber) {
                password.push(numbers.charAt(Math.floor(Math.random() * numbers.length)));
            }
            if (this.policy.requireSymbol) {
                password.push(symbols.charAt(Math.floor(Math.random() * symbols.length)));
            }

            // 残りをランダムに埋める
            while (password.length < desiredLength) {
                password.push(allChars.charAt(Math.floor(Math.random() * allChars.length)));
            }

            // シャッフル
            password = password.sort(() => Math.random() - 0.5).join('');

            this.password = password;
            this.passwordConfirmation = password;
            this.showPassword = true;
        },

        // パスワードコピー
        async copyPassword() {
            if (this.password) {
                try {
                    await navigator.clipboard.writeText(this.password);
                    alert(this.messages.copySuccess || 'パスワードがコピーされました！');
                } catch (err) {
                    console.error('Failed to copy password:', err);
                }
            }
        },

        // パスワード強度チェック
        checkPasswordStrength() {
            const password = this.password;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSymbol = /[!@#$%^&*()_\-+=\[\]{}|\\:;"'<>,.?/~`]/.test(password);
            const length = password.length;

            // 要件チェック結果を保存
            this.requirements = {
                length: length >= this.policy.minLength,
                lowercase: hasLower,
                number: hasNumber,
                uppercase: hasUpper,
                symbol: hasSymbol,
            };

            // 必須条件をすべて満たしているか
            const allRequiredValid =
                length >= this.policy.minLength &&
                (!this.policy.requireLowercase || hasLower) &&
                (!this.policy.requireNumber || hasNumber) &&
                (!this.policy.requireUppercase || hasUpper) &&
                (!this.policy.requireSymbol || hasSymbol);

            if (!allRequiredValid) {
                this.strengthLevel = 0; // エラー
                return;
            }

            // 強度レベルを計算
            const hasAllTypes = hasUpper && hasLower && hasNumber && hasSymbol;
            const hasThreeTypes = hasUpper && hasLower && hasNumber;

            if (hasAllTypes && length >= 16) {
                this.strengthLevel = 4; // 非常に強い
            } else if (hasAllTypes && length >= 12) {
                this.strengthLevel = 3; // 強い
            } else if (hasAllTypes && length < 12) {
                this.strengthLevel = 2; // 普通
            } else if (!this.policy.requireSymbol && hasThreeTypes && length >= this.policy.minLength) {
                this.strengthLevel = 2; // 普通
            } else if (length >= 12) {
                this.strengthLevel = 2; // 普通
            } else {
                this.strengthLevel = 1; // 弱い
            }
        },

        // 強度メッセージ
        get strengthMessage() {
            const msgs = this.messages;
            switch (this.strengthLevel) {
                case 0: return msgs.error || 'エラー';
                case 1: return msgs.weak || '弱い';
                case 2: return msgs.normal || '普通';
                case 3: return msgs.strong || '強い';
                case 4: return msgs.veryStrong || '非常に強い';
                default: return '';
            }
        },

        // 強度バーの幅
        get strengthBarWidth() {
            switch (this.strengthLevel) {
                case 0: return '20%';
                case 1: return '40%';
                case 2: return '60%';
                case 3: return '80%';
                case 4: return '100%';
                default: return '0%';
            }
        },

        // 強度バーの色
        get strengthBarColor() {
            switch (this.strengthLevel) {
                case 0: return 'bg-red-500';
                case 1: return 'bg-orange-400';
                case 2: return 'bg-yellow-500';
                case 3: return 'bg-green-500';
                case 4: return 'bg-blue-500';
                default: return 'bg-gray-300';
            }
        },

        // パスワード一致チェック
        checkPasswordMatch() {
            // Alpine.jsのリアクティブシステムが自動的に処理
        },

        // パスワードが一致しているか
        get isPasswordMatching() {
            return this.password === this.passwordConfirmation && this.password !== '';
        },

        // 確認欄に入力があるか
        get hasConfirmationInput() {
            return this.passwordConfirmation !== '';
        },

        // 一致アイコンを表示するか
        get showMatchIndicator() {
            return this.hasConfirmationInput;
        },

        // 一致アイコンのクラス
        get matchIconClass() {
            if (this.isPasswordMatching) {
                return 'fas fa-check text-green-600 dark:text-green-400';
            }
            return 'fas fa-times text-red-600 dark:text-red-400';
        },

        // 一致アイコンのHTML（完全に置き換え）
        get matchIconHtml() {
            if (this.isPasswordMatching) {
                return '<i class="fas fa-check text-green-600 dark:text-green-400"></i>';
            }
            return '<i class="fas fa-times text-red-600 dark:text-red-400"></i>';
        },

        // 要件アイコンのクラス
        requirementIconClass(requirement) {
            const isValid = this.requirements?.[requirement] || false;
            return isValid ? 'fas fa-check-circle text-green-500 mr-2' : 'fas fa-times-circle text-red-500 mr-2';
        },

        // 初期化
        requirements: {
            length: false,
            lowercase: false,
            number: false,
            uppercase: false,
            symbol: false,
        },
        strengthLevel: 0,
    };
};
