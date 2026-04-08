{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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

@extends('layouts.admin')

@section('content')

    <form method="POST" action="{{ route('admin.profile.notifications.update') }}" id="profile-notifications-form">
        @csrf

        <!-- ログイン通知設定 -->
        <section class="transition-colors-unified">
            <h2>{{ __('admin/profile/notifications.login_notification_mode') }}</h2>
            
            @if(!$isMailServerTested)
                <x-ui-message
                    type="warning"
                    :message="__('admin/profile/notifications.mail_server_not_tested')"
                />
            @endif

            <div :class="{ 'opacity-50 pointer-events-none': {{ !$isMailServerTested ? 'true' : 'false' }} }">
                <x-security.login-notification-selector
                    name="login_notification_mode"
                    :value="old('login_notification_mode', (string) $loginNotificationModeValue)"
                    :globalSetting="(int) ($loginNoticeGlobal ?? 0)"
                    :excludeUseProfileSetting="true"
                    :columns="3"
                />
            </div>
        </section>

    </form>

@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmProfileNotificationsModal"
        :label="__('common.update')"
        :title="__('admin/profile/common.confirm_title')"
        :message="__('admin/profile/common.confirm_message')"
        :confirm_label="__('common.update')"
        :cancel_label="__('common.cancel')"
        form="profile-notifications-form"
    />
@endsection
