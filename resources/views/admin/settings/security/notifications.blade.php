{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the  implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
<div x-data="{ notificationEnabled: '{{ ($settings['notification_enabled'] ?? 0) ? '1' : '0' }}' }">
    <form id="security-notifications-form" method="POST" action="{{ route('admin.settings.security.notifications.update') }}">
        @csrf
        
        <!-- システムエラー通知設定 -->
        <section>
            <h2>{{ __('admin/settings/security/notifications.title') }}</h2>
            <p>{{ __('admin/settings/security/notifications.description') }}</p>

            <!-- システム管理者メール未設定の警告 -->
            @if(!$hasSystemAdminEmail)
                <div class="mt-4">
                    <x-message
                        type="warning"
                        :message="__('admin/settings/security/notifications.system_admin_email_required', ['url' => route('admin.settings.base.mail')])"
                    />
                </div>
            @endif

            <!-- メールサーバー設定の確認メッセージ -->
            @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
                <div class="mt-4">
                    <x-message
                        type="warning"
                        :message="__('admin/settings/security/notifications.mail_test_required', ['url' => route('admin.settings.base.mail')])"
                    />
                </div>
            @endif

            <!-- エラー通知機能の有効/無効 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/notifications.enabled') }}</legend>
                
                <x-form.toggle
                    :label="__('admin/settings/security/notifications.enabled')"
                    id="notification_enabled"
                    name="notification_enabled"
                    :checked="$settings['notification_enabled'] ?? false"
                    xModel="notificationEnabled"
                />
                
                <p>{{ __('admin/settings/security/notifications.enabled_help') }}</p>
            </fieldset>

            <!-- 通知するログレベル -->
            <fieldset>
                <legend>{{ __('admin/settings/security/notifications.log_levels') }}</legend>
                
                <div class="my-3" :class="{ 'opacity-50 pointer-events-none': notificationEnabled === '0' }">
                    <input type="hidden" name="notification_log_levels" :value="notificationEnabled === '1' ? null : '{{ implode(',', $settings['notification_log_levels'] ?? \App\Enums\LogLevel::getDefaultNotificationLevels()) }}'" x-show="notificationEnabled === '0'">
                    
                    @php
                        $logLevelOptions = [];
                        foreach (\App\Enums\LogLevel::getNotificationLevels() as $level) {
                            $levelString = \App\Enums\LogLevel::from($level)->toString();
                            $logLevelOptions[$level] = 'admin/settings/security/notifications.log_level_options.' . $levelString;
                        }
                    @endphp
                    
                    <x-form.toggle-group
                        name="notification_log_levels"
                        :options="$logLevelOptions"
                        :values="$settings['notification_log_levels'] ?? \App\Enums\LogLevel::getDefaultNotificationLevels()"
                        xBindDisabled="notificationEnabled === '0'"
                        flexDirection="col"
                    />
                </div>
                
                <p>{{ __('admin/settings/security/notifications.log_levels_help') }}</p>
            </fieldset>
        </section>

    </form>
</div>
</div>
@endsection

@section('save')
    <x-save
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="security-notifications-form"
    />
@endsection
