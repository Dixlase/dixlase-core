{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-security.two-fa-detailed-settings />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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
    'expireMinutes' => 5,
    'resendIntervalSeconds' => 60,
    'maxAttempts' => 5,
    'attemptWindow' => 15,
    'lockoutDuration' => 30,
    'lockoutNotificationEnabled' => true,
    'recoveryCodesCount' => 5,
    'recoveryCodeRegenerateInterval' => 24,
])

<div :class="{ 'opacity-50 pointer-events-none': !twoFaEnabled }">
    <input type="hidden" name="two_fa_expire_minutes" :value="twoFaEnabled ? null : '{{ $expireMinutes }}'" x-show="!twoFaEnabled">
    <input type="hidden" name="two_fa_resend_interval_seconds" :value="twoFaEnabled ? null : '{{ $resendIntervalSeconds }}'" x-show="!twoFaEnabled">
    <input type="hidden" name="two_fa_max_attempts" :value="twoFaEnabled ? null : '{{ $maxAttempts }}'" x-show="!twoFaEnabled">
    <input type="hidden" name="two_fa_attempt_window" :value="twoFaEnabled ? null : '{{ $attemptWindow }}'" x-show="!twoFaEnabled">
    <input type="hidden" name="two_fa_lockout_duration" :value="twoFaEnabled ? null : '{{ $lockoutDuration }}'" x-show="!twoFaEnabled">
    <input type="hidden" name="two_fa_lockout_notification_enabled" :value="twoFaEnabled ? null : '{{ $lockoutNotificationEnabled ? '1' : '0' }}'" x-show="!twoFaEnabled">
    <input type="hidden" name="two_fa_recovery_codes_count" :value="twoFaEnabled ? null : '{{ $recoveryCodesCount }}'" x-show="!twoFaEnabled">
    <input type="hidden" name="two_fa_recovery_code_regenerate_interval" :value="twoFaEnabled ? null : '{{ $recoveryCodeRegenerateInterval }}'" x-show="!twoFaEnabled">

    <!-- 認証の有効期限設定 -->
    <fieldset>
        <legend>{{ __('components/security/two-fa-detailed-settings.expire_settings') }}</legend>

        <div class="space-y-4">
            <div>
                <label for="two_fa_expire_minutes" class="block text-sm font-medium">
                    {{ __('components/security/two-fa-detailed-settings.expire_minutes') }}
                </label>
                <div class="mt-1 flex items-center space-x-2">
                    <x-form-text
                        type="number"
                        id="two_fa_expire_minutes"
                        name="two_fa_expire_minutes"
                        :value="old('two_fa_expire_minutes', $expireMinutes)"
                        :min="1"
                        :max="60"
                        class="input-common input-sm"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.minutes') }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('components/security/two-fa-detailed-settings.expire_minutes_help') }}
                </p>
            </div>

            <div>
                <label for="two_fa_resend_interval_seconds" class="block text-sm font-medium">
                    {{ __('components/security/two-fa-detailed-settings.resend_interval_seconds') }}
                </label>
                <div class="mt-1 flex items-center space-x-2">
                    <x-form-text
                        type="number"
                        id="two_fa_resend_interval_seconds"
                        name="two_fa_resend_interval_seconds"
                        :value="old('two_fa_resend_interval_seconds', $resendIntervalSeconds)"
                        :min="60"
                        :max="600"
                        :step="60"
                        class="input-common input-sm"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.seconds') }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('components/security/two-fa-detailed-settings.resend_interval_seconds_help') }}
                </p>
            </div>
        </div>
    </fieldset>

    <!-- 2FA試行制限設定 -->
    <fieldset>
        <legend>{{ __('components/security/two-fa-detailed-settings.attempt_limit_settings') }}</legend>

        <div class="space-y-4">
            <div>
                <label for="two_fa_max_attempts" class="block text-sm font-medium">
                    {{ __('components/security/two-fa-detailed-settings.max_attempts') }}
                </label>
                <div class="mt-1 flex items-center space-x-2">
                    <x-form-text
                        type="number"
                        id="two_fa_max_attempts"
                        name="two_fa_max_attempts"
                        :value="old('two_fa_max_attempts', $maxAttempts)"
                        :min="1"
                        :max="10"
                        class="input-common input-sm"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.times') }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('components/security/two-fa-detailed-settings.max_attempts_help') }}
                </p>
            </div>

            <div>
                <label for="two_fa_attempt_window" class="block text-sm font-medium">
                    {{ __('components/security/two-fa-detailed-settings.attempt_window') }}
                </label>
                <div class="mt-1 flex items-center space-x-2">
                    <x-form-text
                        type="number"
                        id="two_fa_attempt_window"
                        name="two_fa_attempt_window"
                        :value="old('two_fa_attempt_window', $attemptWindow)"
                        :min="5"
                        :max="60"
                        class="input-common input-sm"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.minutes') }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('components/security/two-fa-detailed-settings.attempt_window_help') }}
                </p>
            </div>

            <div>
                <label for="two_fa_lockout_duration" class="block text-sm font-medium">
                    {{ __('components/security/two-fa-detailed-settings.lockout_duration') }}
                </label>
                <div class="mt-1 flex items-center space-x-2">
                    <x-form-text
                        type="number"
                        id="two_fa_lockout_duration"
                        name="two_fa_lockout_duration"
                        :value="old('two_fa_lockout_duration', $lockoutDuration)"
                        :min="5"
                        :max="1440"
                        class="input-common input-sm"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.minutes') }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('components/security/two-fa-detailed-settings.lockout_duration_help') }}
                </p>
            </div>

            <div>
                <x-form-toggle
                    name="two_fa_lockout_notification_enabled"
                    :label="__('components/security/two-fa-detailed-settings.lockout_notification_enabled')"
                    :checked="old('two_fa_lockout_notification_enabled', $lockoutNotificationEnabled)"
                />
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('components/security/two-fa-detailed-settings.lockout_notification_enabled_help') }}
                </p>
            </div>
        </div>
    </fieldset>

    <!-- 回復コード設定 -->
    <fieldset>
        <legend>{{ __('components/security/two-fa-detailed-settings.recovery_code_settings') }}</legend>

        <div class="space-y-4">
            <div>
                <label for="two_fa_recovery_codes_count" class="block text-sm font-medium">
                    {{ __('components/security/two-fa-detailed-settings.recovery_codes_count') }}
                </label>
                <div class="mt-1 flex items-center space-x-2">
                    <x-form-text
                        type="number"
                        id="two_fa_recovery_codes_count"
                        name="two_fa_recovery_codes_count"
                        :value="old('two_fa_recovery_codes_count', $recoveryCodesCount)"
                        :min="1"
                        :max="10"
                        class="input-common input-sm"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.codes') }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('components/security/two-fa-detailed-settings.recovery_codes_count_help') }}
                </p>
            </div>

            <div>
                <label for="two_fa_recovery_code_regenerate_interval" class="block text-sm font-medium">
                    {{ __('components/security/two-fa-detailed-settings.recovery_code_regenerate_interval') }}
                </label>
                <div class="mt-1 flex items-center space-x-2">
                    <x-form-text
                        type="number"
                        id="two_fa_recovery_code_regenerate_interval"
                        name="two_fa_recovery_code_regenerate_interval"
                        :value="old('two_fa_recovery_code_regenerate_interval', $recoveryCodeRegenerateInterval)"
                        :min="1"
                        :max="168"
                        class="input-common input-sm"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('common.hours') }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('components/security/two-fa-detailed-settings.recovery_code_regenerate_interval_help') }}
                </p>
            </div>
        </div>
    </fieldset>
</div>
