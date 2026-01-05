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
    'name' => 'email',
    'id' => 'email',
    'value' => '',
    'required' => true,
    'showConfirmation' => false,
    
    // 変更時のみ確認欄を表示（true = 入力時に確認欄を表示, false = 常に表示）
    'showConfirmationOnChange' => false,
])

<div class="space-y-4">
    <!-- メインのメールアドレス入力 -->
    <fieldset>
        <legend>{{ __('common.email') }}</legend>
        <x-form.text
            type="email"
            :id="$id"
            :name="$name"
            :value="$value"
            :required="$required"
            autocomplete="email"
            class="w-full"
        />
    </fieldset>

    @if($showConfirmation)
        <!-- メールアドレス確認入力 -->
        <fieldset id="{{ $id }}-confirmation-fieldset" @if($showConfirmationOnChange) style="display: none;" @endif>
            <legend>{{ __('components.email_input.confirmation_label') }}</legend>
            <div class="relative">
                <x-form.text
                    type="email"
                    :id="$id . '_confirmation'"
                    :name="$name . '_confirmation'"
                    value=""
                    :required="$required"
                    autocomplete="off"
                    onpaste="return false"
                    oncopy="return false"
                    oncut="return false"
                    class="w-full"
                    aria-describedby="{{ $id }}-confirmation-help"
                />
                <!-- メールアドレス一致判定アイコン -->
                <div id="{{ $id }}-match-indicator" class="absolute top-0 right-2 h-full flex items-center" style="display: none;" role="status" aria-live="polite">
                    <i id="{{ $id }}-match-icon" class="fas fa-check text-green-500" aria-label="{{ __('components.email_input.match_status') }}"></i>
                </div>
            </div>
            <p id="{{ $id }}-confirmation-help" class="help-text">{{ __('components.email_input.confirmation_help') }}</p>
        </fieldset>
    @endif
</div>

<script @cspNonce>
    window.EmailInputTools = window.EmailInputTools || {};

    EmailInputTools.checkEmailMatch = function(emailId) {
        const emailInput = document.getElementById(emailId);
        const confirmInput = document.getElementById(emailId + '_confirmation');
        const matchIndicator = document.getElementById(emailId + '-match-indicator');
        const matchIcon = document.getElementById(emailId + '-match-icon');

        if (!emailInput || !confirmInput || !matchIndicator || !matchIcon) {
            return;
        }

        const email = emailInput.value.trim();
        const confirm = confirmInput.value.trim();

        // 確認欄が空の場合はアイコンを非表示
        if (confirm === '') {
            matchIndicator.style.display = 'none';
            matchIcon.setAttribute('aria-label', '');
            return;
        }

        // 確認欄に入力がある場合はアイコンを表示
        matchIndicator.style.display = 'flex';

        // メールアドレスが一致しているかチェック
        if (email === confirm && email !== '') {
            // 一致: 緑のチェックマーク
            matchIcon.className = 'fas fa-check text-green-500';
            matchIcon.setAttribute('aria-label', @json(__('components.email_input.match_success')));
        } else {
            // 不一致: 赤のバツマーク
            matchIcon.className = 'fas fa-times text-red-500';
            matchIcon.setAttribute('aria-label', @json(__('components.email_input.match_error')));
        }
    };

    // イベントリスナーの設定
    (function() {
        const emailId = '{{ $id }}';
        const showConfirmation = {{ $showConfirmation ? 'true' : 'false' }};
        const showConfirmationOnChange = {{ $showConfirmationOnChange ? 'true' : 'false' }};

        const setupEmailListener = function() {
            const emailInput = document.getElementById(emailId);
            const confirmInput = document.getElementById(emailId + '_confirmation');
            const confirmationFieldset = document.getElementById(emailId + '-confirmation-fieldset');
            const originalEmail = emailInput ? emailInput.value.trim() : '';

            if (emailInput && !emailInput.hasAttribute('data-email-listener')) {
                emailInput.setAttribute('data-email-listener', 'true');
                emailInput.addEventListener('input', function() {
                    if (showConfirmation && typeof EmailInputTools !== 'undefined' && typeof EmailInputTools.checkEmailMatch === 'function') {
                        EmailInputTools.checkEmailMatch(emailId);
                    }
                });
                
                // 変更時のみ確認欄を表示する場合のイベントリスナー
                if (showConfirmationOnChange && confirmationFieldset) {
                    emailInput.addEventListener('input', function() {
                        const currentEmail = this.value.trim();
                        if (currentEmail !== originalEmail && currentEmail !== '') {
                            // メールアドレスが変更された場合は確認欄を表示
                            confirmationFieldset.style.display = 'block';
                            if (confirmInput) {
                                confirmInput.required = true;
                            }
                        } else {
                            // 元に戻した場合は確認欄を非表示
                            confirmationFieldset.style.display = 'none';
                            if (confirmInput) {
                                confirmInput.required = false;
                                confirmInput.value = '';
                            }
                        }
                    });
                }
            }

            if (showConfirmation && confirmInput && !confirmInput.hasAttribute('data-email-listener')) {
                confirmInput.setAttribute('data-email-listener', 'true');
                confirmInput.addEventListener('input', function() {
                    if (typeof EmailInputTools !== 'undefined' && typeof EmailInputTools.checkEmailMatch === 'function') {
                        EmailInputTools.checkEmailMatch(emailId);
                    }
                });
            }
        };

        const initListener = function() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupEmailListener);
            } else {
                if (typeof EmailInputTools !== 'undefined') {
                    setupEmailListener();
                } else {
                    setTimeout(setupEmailListener, 50);
                }
            }
        };

        initListener();
    })();
</script>
