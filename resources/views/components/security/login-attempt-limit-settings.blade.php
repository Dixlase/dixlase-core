{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-security.login-attempt-limit-settings />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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
    'enabled' => true,
    'maxAttempts' => 5,
    'maxAttemptsIp' => 10,
    'timeWindow' => 15,
    'lockoutDuration' => 30,
    'notificationEnabled' => true,
    'showIpBasedAttempts' => true,
])

<div x-data="{ enabled: {{ $enabled ? 'true' : 'false' }} }">
    <fieldset>
        <x-form-toggle
            name="login_attempt_limit_enabled"
            :label="__('components/security/login-attempt-limit-settings.enabled')"
            :checked="old('login_attempt_limit_enabled', $enabled)"
            x-model="enabled"
        />
        <p class="mt-2">
            {{ __('components/security/login-attempt-limit-settings.enabled_help') }}
        </p>
    </fieldset>

    <div :class="{ 'opacity-50 pointer-events-none': !enabled }">
        <input type="hidden" name="login_attempt_max_attempts" :value="enabled ? null : '{{ $maxAttempts }}'" x-show="!enabled">
        @if($showIpBasedAttempts)
            <input type="hidden" name="login_attempt_max_attempts_ip" :value="enabled ? null : '{{ $maxAttemptsIp }}'" x-show="!enabled">
        @endif
        <input type="hidden" name="login_attempt_time_window" :value="enabled ? null : '{{ $timeWindow }}'" x-show="!enabled">
        <input type="hidden" name="login_attempt_lockout_duration" :value="enabled ? null : '{{ $lockoutDuration }}'" x-show="!enabled">
        <input type="hidden" name="login_attempt_lockout_notification_enabled" :value="enabled ? null : '{{ $notificationEnabled ? '1' : '0' }}'" x-show="!enabled">

        <fieldset>
            <legend>{{ __('components/security/login-attempt-limit-settings.max_attempts') }}</legend>
            <x-form-text
                type="number"
                name="login_attempt_max_attempts"
                :value="old('login_attempt_max_attempts', $maxAttempts)"
                :min="1"
                :max="100"
                class="input-common input-sm"
                ::disabled="!enabled"
            />
            <p>
                {{ __('components/security/login-attempt-limit-settings.max_attempts_help') }}
            </p>
        </fieldset>

        @if($showIpBasedAttempts)
            <fieldset>
                <legend>{{ __('components/security/login-attempt-limit-settings.max_attempts_ip') }}</legend>
                <x-form-text
                    type="number"
                    name="login_attempt_max_attempts_ip"
                    :value="old('login_attempt_max_attempts_ip', $maxAttemptsIp)"
                    :min="1"
                    :max="100"
                    class="input-common input-sm"
                    ::disabled="!enabled"
                />
                <p>
                    {{ __('components/security/login-attempt-limit-settings.max_attempts_ip_help') }}
                </p>
            </fieldset>
        @endif

        <fieldset>
            <legend>{{ __('components/security/login-attempt-limit-settings.time_window') }}</legend>
            <x-form-text
                type="number"
                name="login_attempt_time_window"
                :value="old('login_attempt_time_window', $timeWindow)"
                :min="1"
                :max="1440"
                class="input-common input-sm"
                ::disabled="!enabled"
            />
            <p>
                {{ __('components/security/login-attempt-limit-settings.time_window_help') }}
            </p>
        </fieldset>

        <fieldset>
            <legend>{{ __('components/security/login-attempt-limit-settings.lockout_duration') }}</legend>
            <x-form-text
                type="number"
                name="login_attempt_lockout_duration"
                :value="old('login_attempt_lockout_duration', $lockoutDuration)"
                :min="1"
                :max="10080"
                class="input-common input-sm"
                ::disabled="!enabled"
            />
            <p>
                {{ __('components/security/login-attempt-limit-settings.lockout_duration_help') }}
            </p>
        </fieldset>

        <fieldset>
            <x-form-toggle
                name="login_attempt_lockout_notification_enabled"
                :label="__('components/security/login-attempt-limit-settings.notification_enabled')"
                :checked="old('login_attempt_lockout_notification_enabled', $notificationEnabled)"
                ::disabled="!enabled"
            />
            <p class="mt-2">
                {!! __('components/security/login-attempt-limit-settings.notification_help') !!}
            </p>
        </fieldset>
    </div>
</div>
