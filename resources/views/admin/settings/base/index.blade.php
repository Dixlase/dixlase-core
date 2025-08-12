{{--
This file is part of MySoftware.

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

@extends('admin::partials.layout')

@section('content')
<form id="base-settings-form" action="{{ route('admin.settings.base.update') }}" method="POST">
    @csrf
    @method('PUT')

    <!-- サイト設定 -->
    <div>
        <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.base.site_settings') }}</h2>
        <div>
            @include('components::form.label', [
                'for' => 'app_name',
                'text' => __('admin.settings.base.app_name'),
            ])
            @include('components::form.text', [
                'id' => 'app_name',
                'name' => 'app_name',
                'value' => old('app_name', $settings['app_name']),
                'required' => true,
            ])
        </div>

        <!-- 言語設定 -->
        <div class="mt-4">
            @include('components::form.label', [
                'for' => 'locale',
                'text' => __('admin.settings.base.locale'),
            ])

            @include('components::form.select', [
                'id' => 'locale',
                'name' => 'locale',
                'options' => $locales,
                'value' => old('locale', $settings['locale']),
                'required' => true,
            ])

        </div>

        <!-- タイムゾーン -->
        <div class="mt-4">
            @include('components::form.label', [
                'for' => 'timezone',
                'text' => __('admin.settings.base.timezone'),
            ])
            @include('components::form.select', [
                'id' => 'timezone',
                'name' => 'timezone',
                'options' => $timezones,
                'value' => $settings['timezone'],
            ])
        </div>
    </div>

    <!-- メールサーバー設定 -->
    <div class="mt-6 border-t pt-6">
        <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.base.mail_server_settings') }}</h2>

        <!-- Mailer -->
        <div class="mt-4">
            @include('components::form.label', [
                'for' => 'mail_mailer',
                'text' => __('admin.settings.base.mailer'),
            ])
            @include('components::form.select', [
                'id' => 'mail_mailer',
                'name' => 'mail_mailer',
                'options' => $mailers,
                'value' => old('mail_mailer', $settings['mail_mailer']),
            ])
        </div>

        <!-- ホスト名 -->
        <div class="mt-4">
            @include('components::form.label', [
                'for' => 'mail_host',
                'text' => __('admin.settings.base.mail_host'),
            ])
            @include('components::form.text', [
                'id' => 'mail_host',
                'name' => 'mail_host',
                'value' => old('mail_host', $settings['mail_host']),
            ])
        </div>

        <!-- ポート番号 -->
        <div class="mt-4">
            @include('components::form.label', [
                'for' => 'mail_port',
                'text' => __('admin.settings.base.mail_port'),
            ])
            @include('components::form.text', [
                'id' => 'mail_port',
                'name' => 'mail_port',
                'value' => old('mail_port', $settings['mail_port']),
            ])
        </div>

        <!-- ユーザー名 -->
        <div class="mt-4">
            @include('components::form.label', [
                'for' => 'mail_username',
                'text' => __('admin.settings.base.mail_username'),
            ])
            @include('components::form.text', [
                'id' => 'mail_username',
                'name' => 'mail_username',
                'value' => old('mail_username', $settings['mail_username']),
            ])
        </div>

        <!-- パスワード -->
        <div class="mt-4">
            @include('components::form.label', [
                'for' => 'mail_password',
                'text' => __('admin.settings.base.mail_password'),
            ])
            @include('components::form.text', [
                'id' => 'mail_password',
                'name' => 'mail_password',
                'value' => old('mail_password', $settings['mail_password']),
            ])
        </div>

        <!-- 暗号化方式 -->
        <div class="mt-4">
            @include('components::form.label', [
                'for' => 'mail_encryption',
                'text' => __('admin.settings.base.mail_encryption'),
            ])
            @include('components::form.select', [
                'id' => 'mail_encryption',
                'name' => 'mail_encryption',
                'options' => $encryptions,
                'value' => old('mail_encryption', $settings['mail_encryption']),
            ])
        </div>

        <!-- 送信元メールアドレス -->
        <div class="mt-4">
            @include('components::form.label', [
                'for' => 'mail_from_address',
                'text' => __('admin.settings.base.mail_from_address'),
            ])
            @include('components::form.text', [
                'id' => 'mail_from_address',
                'name' => 'mail_from_address',
                'value' => old('mail_from_address', $settings['mail_from_address']),
            ])
        </div>


    </div>


    <div class="mt-6 border-t pt-6">
        <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.base.maintenance_settings') }}</h2>
        @include('components::form.label', [
            'text' => __('admin.settings.base.maintenance_mode'),
        ])
        @include('components::form.hidden', [
            'id' => 'maintenance_mode',
            'name' => 'maintenance_mode',
            'value' => '0'
        ])
        @include('components::form.radio-group', [
            'name' => 'maintenance_mode',
            'options' => [
                1 => __('admin.settings.base.yes'),
                0 => __('admin.settings.base.no')
            ],
            'value' => $settings['maintenance_mode'],
        ])
    </div>

    <!-- メンテナンス時のメッセージ -->
    <div class="mt-6">
        @include('components::form.label', [
            'for' => 'maintenance_message',
            'text' => __('admin.settings.base.maintenance_message'),
        ])
        @include('components::form.textarea', [
            'id' => 'maintenance_message',
            'name' => 'maintenance_message',
            'value' => old('maintenance_message', $settings['maintenance_message']),
            'rows' => 3,
        ])
        <p class="text-sm text-gray-500 mt-1">{{ __('admin.settings.base.maintenance_message_help') }}</p>
    </div>

    <!-- 保存ボタンとモーダル -->
    <div class="mt-4">
        @include('components::form.save', [
            'id' => 'confirmationModal',
            'onclick' => "openModal('confirmationModal')",
            'title' => __('admin.settings.base.save_confirmation_title'),
            'message' => __('admin.settings.base.save_confirmation_message'),
            'confirm_label' => __('admin.settings.base.save_button'),
            'cancel_label' => __('admin.settings.base.cancel_button'),
            'form' => 'base-settings-form',
        ])
    </div>
</form>
@endsection
