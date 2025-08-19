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

    <!-- メンテナンスモード設定 -->
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

        <!-- メール機能テスト状態の表示 -->
        <div class="mt-6 p-4 border rounded-lg 
            @if($mailConnectionTested && $mailSendTested && $mailReceiveTested)
                bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800
            @else
                bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800
            @endif
        ">
            <div class="flex items-start mb-4">
                <div class="flex-shrink-0">
                    @if($mailConnectionTested && $mailSendTested && $mailReceiveTested)
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    @else
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                    @endif
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium 
                        @if($mailConnectionTested && $mailSendTested && $mailReceiveTested)
                            text-green-800 dark:text-green-200
                        @else
                            text-yellow-800 dark:text-yellow-200
                        @endif
                    ">
                        @if($mailConnectionTested && $mailSendTested && $mailReceiveTested)
                            {{ __('admin.settings.base.view_messages.mail_test_complete') }}
                        @else
                            {{ __('admin.settings.base.view_messages.mail_test_incomplete') }}
                        @endif
                    </h3>
                </div>
            </div>

            <!-- テスト結果の保存に関する注意 -->
            @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
                <div class="ml-5 my-3 text-sm text-yellow-700 dark:text-yellow-300">
                    <ul class="list-disc">
                        <li>メンバー全体設定のロックアウト通知、パスワードリセット、ログイン通知、二段階認証機能を使用するには、すべてのメールテストを完了してください。</li>
                        <li>テスト結果は一時的に保存されます。更新ボタンを押すまで、設定やテスト結果は保存されません。</li>
                    </ul>
                </div>
            @endif

            <!-- テスト進捗状況 -->
            <div class="space-y-2">
                <!-- 接続テスト -->
                <div id="connection-test-status" class="flex items-center">
                    <i id="connection-test-icon" class="mr-2 {{ $mailConnectionTested ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-gray-400' }}"></i>
                    <span id="connection-test-text" class="text-sm {{ $mailConnectionTested ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                        1. {{ __('admin.settings.base.view_messages.connection_test') }}
                        <span id="connection-test-date">
                            @if($mailConnectionTested && $mailConnectionTestDate)
                                ({{ $mailConnectionTestDate }})
                            @endif
                        </span>
                    </span>
                </div>

                <!-- 送信テスト -->
                <div id="send-test-status" class="flex items-center">
                    <i id="send-test-icon" class="mr-2 {{ $mailSendTested ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-gray-400' }}"></i>
                    <span id="send-test-text" class="text-sm {{ $mailSendTested ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                        2. {{ __('admin.settings.base.view_messages.send_test') }}
                        <span id="send-test-date">
                            @if($mailSendTested && $mailSendTestDate)
                                ({{ $mailSendTestDate }})
                            @endif
                        </span>
                    </span>
                </div>

                <!-- 受信確認テスト -->
                <div id="receive-test-status" class="flex items-center">
                    <i id="receive-test-icon" class="mr-2 {{ $mailReceiveTested ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-gray-400' }}"></i>
                    <span id="receive-test-text" class="text-sm {{ $mailReceiveTested ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                        3. {{ __('admin.settings.base.view_messages.receive_test') }}
                        <span id="receive-test-date">
                            @if($mailReceiveTested && $mailReceiveTestDate)
                                ({{ $mailReceiveTestDate }})
                            @endif
                        </span>
                    </span>
                </div>
            </div>
        </div>

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
                <button type="button" id="test-mail-btn" 
                    class="py-2 px-4 rounded transition-colors duration-200 font-bold
                        @if($mailConnectionTested)
                            bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white
                        @else
                            bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed
                        @endif
                    "
                    @if(!$mailConnectionTested) disabled @endif
                >
                    {{ __('admin.settings.base.test_mail_button') }}
                </button>
            </div>
            <div id="test-result" class="mt-3 hidden"></div>
        </div>
    </div>

    <!-- システムエラー通知設定 -->
    <div class="mt-6 border-t pt-6">
        <h2 class="text-xl font-semibold mb-2">{{ __('admin.settings.base.notification_settings') }}</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ __('admin.settings.base.notification_settings_description') }}
        </p>

        <!-- エラー通知機能の有効/無効 -->
        <div class="mt-4">
            @include('components::form.label', [
                'text' => __('admin.settings.base.notification_enabled'),
            ])
            @include('components::form.hidden', [
                'id' => 'notification_enabled',
                'name' => 'notification_enabled',
                'value' => '0'
            ])
            @include('components::form.radio-group', [
                'name' => 'notification_enabled',
                'options' => [
                    1 => __('admin.settings.base.yes'),
                    0 => __('admin.settings.base.no')
                ],
                'value' => $settings['notification_enabled'],
            ])
            <p class="text-sm text-gray-500 mt-1">{{ __('admin.settings.base.notification_enabled_help') }}</p>
        </div>

        <!-- 通知先メールアドレス -->
        <div class="mt-4" x-data="{ enabled: {{ $settings['notification_enabled'] ? 'true' : 'false' }} }" x-init="
            $watch('enabled', value => {
                const radios = document.querySelectorAll('input[name=notification_enabled]');
                radios.forEach(radio => {
                    if (radio.checked) {
                        enabled = radio.value === '1';
                    }
                });
            });
            
            // ラジオボタンの変更を監視
            document.querySelectorAll('input[name=notification_enabled]').forEach(radio => {
                radio.addEventListener('change', () => {
                    enabled = radio.value === '1';
                });
            });
        ">
            @include('components::form.label', [
                'for' => 'notification_email',
                'text' => __('admin.settings.base.notification_email'),
            ])
            @include('components::form.text', [
                'id' => 'notification_email',
                'name' => 'notification_email',
                'value' => old('notification_email', $settings['notification_email']),
                'type' => 'email',
                'placeholder' => 'admin@example.com',
                'x-bind:disabled' => '!enabled',
                'x-bind:class' => '!enabled ? "bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400" : ""'
            ])
            <p class="text-sm text-gray-500 mt-1">{{ __('admin.settings.base.notification_email_help') }}</p>
            
            <!-- メールサーバー設定の確認メッセージ -->
            @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
                <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400 text-sm"></i>
                        </div>
                        <div class="ml-2">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                {{ __('admin.settings.base.notification_mail_test_required') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</form>

<script>
    // 翻訳テキストをJavaScriptで使用するために定義
    const translations = {
        mailTestComplete: '{{ __('admin.settings.base.view_messages.mail_test_complete') }}',
        mailTestIncomplete: '{{ __('admin.settings.base.view_messages.mail_test_incomplete') }}',
        connectionTest: '{{ __('admin.settings.base.view_messages.connection_test') }}',
        sendTest: '{{ __('admin.settings.base.view_messages.send_test') }}',
        receiveTest: '{{ __('admin.settings.base.view_messages.receive_test') }}',
        testPassed: '{{ __('admin.settings.base.view_messages.test_passed') }}',
        testNotCompleted: '{{ __('admin.settings.base.view_messages.test_not_completed') }}'
    };

    document.addEventListener('DOMContentLoaded', function() {
        const testConnectionBtn = document.getElementById('test-connection-btn');
        const testMailBtn = document.getElementById('test-mail-btn');
        const testResult = document.getElementById('test-result');
        const form = document.getElementById('base-settings-form');

        // メール認証ウィンドウからのpostMessageを受信
        window.addEventListener('message', function(event) {
            // セキュリティのため、同一オリジンからのメッセージのみ受信
            if (event.origin !== window.location.origin) {
                return;
            }
            
            if (event.data.type === 'mail_receive_test_completed') {
                // 受信テスト完了時にUIを更新
                updateTestStatus('receive', true, null);
                
                // 成功メッセージを表示
                showNotification('success', translations.mailReceiveTestCompleted);
            }
        });

        // セッション状態を定期的にチェックするポーリング機能
        let lastSessionState = null;
        let sessionPollingInterval = null;

        function startSessionPolling() {
            // 3秒間隔でセッション状態をチェック
            sessionPollingInterval = setInterval(checkSessionStatus, 3000);
        }

        function stopSessionPolling() {
            if (sessionPollingInterval) {
                clearInterval(sessionPollingInterval);
                sessionPollingInterval = null;
            }
        }

        function checkSessionStatus() {
            fetch('{{ route("admin.settings.base.check-test-session") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const currentState = data.data;
                    
                    // 初回チェック時は現在の状態を記録
                    if (lastSessionState === null) {
                        lastSessionState = currentState;
                        return;
                    }
                    
                    // 状態が変更された場合のみUIを更新
                    if (hasSessionStateChanged(lastSessionState, currentState)) {
                        updateUIFromSessionState(currentState);
                        lastSessionState = currentState;
                    }
                }
            })
            .catch(error => {
                console.log('{{ __('admin.settings.base.view_messages.session_clear_error') }}', error);
            });
        }

        function hasSessionStateChanged(oldState, newState) {
            return (
                oldState.connection_tested !== newState.connection_tested ||
                oldState.send_tested !== newState.send_tested ||
                oldState.receive_tested !== newState.receive_tested ||
                oldState.connection_test_date !== newState.connection_test_date ||
                oldState.send_test_date !== newState.send_test_date ||
                oldState.receive_test_date !== newState.receive_test_date
            );
        }

        function updateUIFromSessionState(state) {
            // 接続テスト状態を更新
            if (state.connection_tested) {
                updateTestStatus('connection', true, state.connection_test_date);
            }
            
            // 送信テスト状態を更新
            if (state.send_tested) {
                updateTestStatus('send', true, state.send_test_date);
            }
            
            // 受信テスト状態を更新
            if (state.receive_tested) {
                updateTestStatus('receive', true, state.receive_test_date);
                showNotification('success', translations.mailReceiveTestCompleted);
            }
        }

        // ページ読み込み時にポーリング開始
        startSessionPolling();

        // ページを離れる時にポーリング停止
        window.addEventListener('beforeunload', function() {
            stopSessionPolling();
        });

        // メール設定フィールドの監視
        const mailFields = [
            'mail_mailer',
            'mail_host', 
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address'
        ];

        // 各メール設定フィールドに変更監視を追加
        mailFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('input', function() {
                    resetMailTestStatus();
                });
                field.addEventListener('change', function() {
                    resetMailTestStatus();
                });
            }
        });

        // 接続テストボタンのイベントリスナー
        testConnectionBtn.addEventListener('click', function() {
            performTest('connection', testConnectionBtn, '{{ __("admin.settings.base.testing_connection") }}', '{{ __("admin.settings.base.test_connection_button") }}', '{{ route("admin.settings.base.test-connection") }}');
        });

        // メール送信テストボタンのイベントリスナー
        testMailBtn.addEventListener('click', function() {
            performTest('send', testMailBtn, '{{ __("admin.settings.base.testing_mail") }}', '{{ __("admin.settings.base.test_mail_button") }}', '{{ route("admin.settings.base.test-mail") }}');
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
                    // テスト成功時にUIを即座に更新
                    updateTestStatus(testType, true, data.test_date);
                    
                    // 接続テスト成功時は成功メッセージと保存促しメッセージを統合して表示
                    testResult.innerHTML = `
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-start mb-3">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
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

        // テスト状態を即座に更新する関数
        function updateTestStatus(testType, success, testDate) {
            let iconId, textId, dateId;
            
            // テストタイプに応じてIDを設定
            switch(testType) {
                case 'connection':
                    iconId = 'connection-test-icon';
                    textId = 'connection-test-text';
                    dateId = 'connection-test-date';
                    break;
                case 'send':
                    iconId = 'send-test-icon';
                    textId = 'send-test-text';
                    dateId = 'send-test-date';
                    break;
                case 'receive':
                    iconId = 'receive-test-icon';
                    textId = 'receive-test-text';
                    dateId = 'receive-test-date';
                    break;
            }
            
            if (success && iconId && textId && dateId) {
                // アイコンを緑のチェックマークに変更
                const icon = document.getElementById(iconId);
                icon.className = 'mr-2 fas fa-check-circle text-green-500';
                
                // テキストの色を緑に変更
                const text = document.getElementById(textId);
                text.className = 'text-sm text-green-700 dark:text-green-300';
                
                // 日付を追加
                if (testDate) {
                    const dateSpan = document.getElementById(dateId);
                    dateSpan.textContent = ` (${testDate})`;
                } else {
                    // testDateがない場合は現在の日時を表示
                    const now = new Date();
                    const formattedDate = now.getFullYear() + '-' + 
                        String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(now.getDate()).padStart(2, '0') + ' ' + 
                        String(now.getHours()).padStart(2, '0') + ':' + 
                        String(now.getMinutes()).padStart(2, '0');
                    const dateSpan = document.getElementById(dateId);
                    dateSpan.textContent = ` (${formattedDate})`;
                }
                
                // 全てのテストが完了したかチェックして、メインステータスも更新
                updateMainStatus();
                
                // 接続テスト成功時にメール送信テストボタンを有効化
                if (testType === 'connection') {
                    enableMailTestButton();
                }
            }
        }
        
        // メール送信テストボタンを有効化する関数
        function enableMailTestButton() {
            const mailTestBtn = document.getElementById('test-mail-btn');
            mailTestBtn.disabled = false;
            mailTestBtn.className = 'py-2 px-4 rounded transition-colors duration-200 font-bold bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white';
        }
        
        // メインステータスを更新する関数
        function updateMainStatus() {
            // 各テストの状態をチェック
            const connectionIcon = document.getElementById('connection-test-icon');
            const sendIcon = document.getElementById('send-test-icon');
            const receiveIcon = document.getElementById('receive-test-icon');
            
            const allTestsComplete = 
                connectionIcon.classList.contains('text-green-500') &&
                sendIcon.classList.contains('text-green-500') &&
                receiveIcon.classList.contains('text-green-500');
            
            // メインステータス部分を更新
            const mainStatusDiv = document.querySelector('.mt-6.p-4.border.rounded-lg');
            const mainIcon = mainStatusDiv.querySelector('i');
            const mainTitle = mainStatusDiv.querySelector('h3');
            
            if (allTestsComplete) {
                // 全テスト完了時の表示に変更
                mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
                mainIcon.className = 'fas fa-check-circle text-green-400 text-xl';
                mainTitle.className = 'text-sm font-medium text-green-800 dark:text-green-200';
                mainTitle.textContent = '{{ __('admin.settings.base.view_messages.mail_test_complete') }}';
                
                // 警告メッセージを非表示
                const warningDiv = document.querySelector('.mt-3.text-sm.text-yellow-700');
                if (warningDiv) {
                    warningDiv.style.display = 'none';
                }
            }
        }
        
        // メール設定変更時にテスト結果をリセットする関数
        function resetMailTestStatus() {
            // セッションのテスト結果をクリア（サーバーサイドで処理）
            fetch('{{ route("admin.settings.base.clear-test-session") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).catch(error => {
                console.log('{{ __('admin.settings.base.view_messages.session_clear_error') }}', error);
            });
            
            // UIを未完了状態にリセット
            resetTestStatusUI();
            
            // メール送信テストボタンを無効化
            disableMailTestButton();
        }
        
        // テスト結果UIをリセットする関数
        function resetTestStatusUI() {
            // 各テストアイコンと表示を未完了状態に戻す
            const testTypes = ['connection', 'send', 'receive'];
            
            testTypes.forEach(testType => {
                const iconId = `${testType}-test-icon`;
                const textId = `${testType}-test-text`;
                const dateId = `${testType}-test-date`;
                
                const icon = document.getElementById(iconId);
                const text = document.getElementById(textId);
                const dateSpan = document.getElementById(dateId);
                
                if (icon && text && dateSpan) {
                    // アイコンを灰色のXマークに変更
                    icon.className = 'mr-2 fas fa-times-circle text-gray-400';
                    
                    // テキストの色を灰色に変更
                    text.className = 'text-sm text-gray-600 dark:text-gray-400';
                    
                    // 日付をクリア
                    dateSpan.textContent = '';
                }
            });
            
            // メインステータスを未完了状態に戻す
            const mainStatusDiv = document.querySelector('.mt-6.p-4.border.rounded-lg');
            const mainIcon = mainStatusDiv.querySelector('i');
            const mainTitle = mainStatusDiv.querySelector('h3');
            
            if (mainStatusDiv && mainIcon && mainTitle) {
                mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
                mainIcon.className = 'fas fa-exclamation-triangle text-yellow-400 text-xl';
                mainTitle.className = 'text-sm font-medium text-yellow-800 dark:text-yellow-200';
                mainTitle.textContent = '{{ __('admin.settings.base.view_messages.mail_test_incomplete') }}';
            }
        }
        
        // メール送信テストボタンを無効化する関数
        function disableMailTestButton() {
            const mailTestBtn = document.getElementById('test-mail-btn');
            if (mailTestBtn) {
                mailTestBtn.disabled = true;
                mailTestBtn.className = 'py-2 px-4 rounded transition-colors duration-200 font-bold bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed';
            }
        }
        
        // 通知メッセージを表示する関数
        function showNotification(type, message) {
            // 既存の通知があれば削除
            const existingNotification = document.getElementById('mail-test-notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            // 通知要素を作成
            const notification = document.createElement('div');
            notification.id = 'mail-test-notification';
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
                type === 'success' 
                    ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200'
                    : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'
            }`;
            
            notification.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="${type === 'success' ? 'fas fa-check-circle text-green-400' : 'fas fa-times-circle text-red-400'} text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex ${type === 'success' ? 'text-green-400 hover:text-green-500' : 'text-red-400 hover:text-red-500'}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            // ページに追加
            document.body.appendChild(notification);
            
            // 5秒後に自動削除
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
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
