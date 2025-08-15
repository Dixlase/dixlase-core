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

        <!-- メール接続テスト状態の警告 -->
        @if (!$mailConnectionTested)
            <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            {{ __('admin.settings.base.mail_server_warning') }}
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>{{ __('admin.settings.base.mail_server_warning_message') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif ($mailConnectionTested && $mailConnectionTestDate)
            <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ __('admin.settings.base.mail_server_test_passed') }}
                        </h3>
                        <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                            <p>{{ __('admin.settings.base.last_test_date') }}: {{ $mailConnectionTestDate }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- メール送信テスト -->
        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.base.mail_test') }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('admin.settings.base.mail_test_description') }}<br>
                {{ __('admin.settings.base.mail_test_description_2') }}
            </p>
            <div class="flex space-x-3">
                <button type="button" id="test-connection-btn" class="bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                    {{ __('admin.settings.base.test_connection_button') }}
                </button>
                <button type="button" id="test-mail-btn" class="bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                    {{ __('admin.settings.base.test_mail_button') }}
                </button>
            </div>
            <div id="test-result" class="mt-3 hidden"></div>
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
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testConnectionBtn = document.getElementById('test-connection-btn');
    const testMailBtn = document.getElementById('test-mail-btn');
    const testResult = document.getElementById('test-result');
    const form = document.getElementById('base-settings-form');

    // 接続テストボタンのイベントリスナー
    testConnectionBtn.addEventListener('click', function() {
        performTest('connection', testConnectionBtn, '{{ __("admin.settings.base.testing_connection") }}', '{{ __("admin.settings.base.test_connection_button") }}', '{{ route("admin.settings.base.test-connection") }}');
    });

    // メール送信テストボタンのイベントリスナー
    testMailBtn.addEventListener('click', function() {
        performTest('mail', testMailBtn, '{{ __("admin.settings.base.testing_mail") }}', '{{ __("admin.settings.base.test_mail_button") }}', '{{ route("admin.settings.base.test-mail") }}');
    });

    function performTest(testType, button, loadingText, originalText, url) {
        // ボタンを無効化してローディング状態にする
        button.disabled = true;
        button.textContent = loadingText;
        
        // 結果エリアをクリア
        testResult.innerHTML = '';
        testResult.classList.add('hidden');

        // フォームデータを取得（_methodフィールドを除外）
        const formData = new FormData();
        const formElements = form.elements;
        
        for (let element of formElements) {
            if (element.name && element.name !== '_method' && element.type !== 'submit') {
                if (element.type === 'radio' || element.type === 'checkbox') {
                    if (element.checked) {
                        formData.append(element.name, element.value);
                    }
                } else {
                    formData.append(element.name, element.value);
                }
            }
        }
        
        // CSRFトークンを追加
        formData.append('_token', '{{ csrf_token() }}');

        // AJAX リクエストを送信
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // 結果を表示
            testResult.classList.remove('hidden');
            
            if (data.success) {
                // 接続テスト成功時は成功メッセージと保存促しメッセージを統合して表示
                testResult.innerHTML = `
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-start mb-3">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                    ${data.message}
                                </h3>
                                <div class="mt-2 text-sm text-blue-700 dark:text-green-200">
                                    <p>{{ __('admin.settings.base.save_settings_reminder_message') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                testResult.innerHTML = `
                    <div class="p-3 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            // エラーを表示
            testResult.classList.remove('hidden');
            testResult.innerHTML = `
                <div class="p-3 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 rounded">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    {{ __("admin.settings.base.mail_test_error") }}
                </div>
            `;
        })
        .finally(() => {
            // ボタンを元に戻す
            button.disabled = false;
            button.textContent = originalText;
        });
    }
});
</script>

@endsection

@section('save')
    <!-- 保存ボタンとモーダル -->
    @include('components::form.save', [
        'id' => 'confirmationModal',
        'label' => __('admin.settings.base.submit'),
        'onclick' => "openModal('confirmationModal')",
        'title' => __('admin.settings.base.save_confirmation_title'),
        'message' => __('admin.settings.base.save_confirmation_message'),
        'confirm_label' => __('admin.settings.base.save_button'),
        'cancel_label' => __('admin.settings.base.cancel_button'),
        'form' => 'base-settings-form',
    ])
@endsection
