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
    <section>
        <h2>{{ __('admin.settings.base.site_settings') }}</h2>
        
        <fieldset>
            <legend>{{ __('admin.settings.base.app_name') }}</legend>
            @include('components.form.text', [
                'name' => 'app_name',
                'value' => old('app_name', $settings['app_name']),
                'required' => true,
            ])
        </fieldset>

        <fieldset>
            <legend>{{ __('admin.settings.base.locale') }}</legend>
            @include('components.form.select', [
                'name' => 'locale',
                'options' => $locales,
                'value' => old('locale', $settings['locale']),
                'required' => true,
            ])
        </fieldset>

        <fieldset>
            <legend>{{ __('common.timezone') }}</legend>
            @include('components.form.select', [
                'name' => 'timezone',
                'options' => $timezones,
                'value' => $settings['timezone'],
            ])
        </fieldset>

        <fieldset>
            <legend>{{ __('admin.settings.base.admin_url') }}</legend>
            @include('components.form.text', [
                'name' => 'admin_url',
                'value' => old('admin_url', $settings['admin_url']),
                'required' => true,
            ])
            <p>{!! __('admin.settings.base.admin_url_help') !!}</p>
        </fieldset>

        <fieldset>
            <legend>{{ __('admin.settings.base.force_ssl') }}</legend>
            @include('components.form.checkbox', [
                'label' => __('admin.settings.base.force_ssl'),
                'name' => 'force_ssl',
                'value' => old('force_ssl', $settings['force_ssl']),
            ])
            <p>{{ __('admin.settings.base.force_ssl_help') }}</p>
        </fieldset>
    </section>

    <!-- メンテナンスモード設定 -->
    <section>
        <h2>{{ __('admin.settings.base.maintenance_settings') }}</h2>
        
        <fieldset>
            <legend>{{ __('admin.settings.base.maintenance_mode') }}</legend>
            @include('components.form.hidden', [
                'name' => 'maintenance_mode',
                'value' => '0'
            ])
            @include('components.form.radio-group', [
                'name' => 'maintenance_mode',
                'options' => [
                    1 => __('common.yes'),
                    0 => __('common.no')
                ],
                'value' => $settings['maintenance_mode'],
            ])
        </fieldset>

        <fieldset>
            <legend>{{ __('admin.settings.base.maintenance_message') }}</legend>
            @include('components.form.textarea', [
                'name' => 'maintenance_message',
                'value' => old('maintenance_message', $settings['maintenance_message']),
                'rows' => 3,
            ])
            <p>{{ __('admin.settings.base.maintenance_message_help') }}</p>
        </fieldset>
    </section>

    <!-- メールサーバー設定 -->
    <section>
        <h2>{{ __('admin.settings.base.mail_server_settings') }}</h2>

        @include('components.mail-server-form', [
            'settings' => $settings,
            'mailers' => $mailers,
            'encryptions' => $encryptions,
            'context' => 'admin'
        ])

        @include('components.mail-test', [
            'context' => 'admin',
            'connectionTestRoute' => route('admin.settings.base.test-connection'),
            'mailTestRoute' => route('admin.settings.base.test-mail'),
            'showStatus' => true,
            'testStatus' => [
                'connection_tested' => $mailConnectionTested,
                'send_tested' => $mailSendTested,
                'receive_tested' => $mailReceiveTested,
                'connection_test_date' => $mailConnectionTestDate,
                'send_test_date' => $mailSendTestDate,
                'receive_test_date' => $mailReceiveTestDate
            ]
        ])
    </section>

    <!-- システム管理者メールアドレス -->
    <section>
        <h2>{{ __('admin.settings.base.admin_email_settings') }}</h2>
        <p>{{ __('admin.settings.base.admin_email_settings_description') }}</p>

        <!-- メールサーバー設定の確認メッセージ -->
        @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
            @include('components.message', [
                'type' => 'warning',
                'message' => __('admin.settings.base.admin_email_mail_test_required')
            ])
        @endif

        <fieldset>
            <legend>{{ __('admin.settings.base.admin_email') }}</legend>
            @include('components.form.text', [
                'type' => 'email',
                'name' => 'system_admin_email',
                'value' => old('system_admin_email', $settings['system_admin_email']),
            ])
            <p>{{ __('admin.settings.base.admin_email_help') }}</p>
        </fieldset>
    </section>
</form>



@endsection

@section('save')
    <!-- 保存ボタンとモーダル -->
    @include('components::form.save', [
        'id' => 'confirmationModal',
        'label' => __('common.submit'),
        'onclick' => "openModal('confirmationModal')",
        'title' => __('common.save_confirmation_title'),
        'message' => __('common.save_confirmation_message'),
        'confirm_label' => __('common.form.save_button'),
        'cancel_label' => __('common.form.cancel_button'),
        'form' => 'base-settings-form',
    ])
@endsection


@section('scripts')
<script>
    // 管理画面用の追加JavaScript（メール設定変更監視など）
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('base-settings-form');

        // メール認証ウィンドウからのpostMessageを受信
        window.addEventListener('message', function(event) {
            if (event.origin !== window.location.origin) {
                return;
            }
            
            if (event.data.type === 'mail_receive_test_completed') {
                // 受信テスト完了時にUIを更新
                updateTestStatus('receive', true, null);
                showNotification('success', '{{ __('admin.settings.base.view_messages.mail_receive_test_completed') }}');
            }
        });

        // セッション状態を定期的にチェックするポーリング機能
        let lastSessionState = null;
        let sessionPollingInterval = null;

        function startSessionPolling() {
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
                    
                    if (lastSessionState === null) {
                        lastSessionState = currentState;
                        return;
                    }
                    
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
            // Check if updateTestStatus function exists (defined in mail-test component)
            if (typeof updateTestStatus === 'function') {
                if (state.connection_tested) {
                    updateTestStatus('connection', true, state.connection_test_date);
                }
                
                if (state.send_tested) {
                    updateTestStatus('send', true, state.send_test_date);
                }
                
                if (state.receive_tested) {
                    updateTestStatus('receive', true, state.receive_test_date);
                    if (typeof showNotification === 'function') {
                        showNotification('success', '{{ __('admin.settings.base.view_messages.mail_receive_test_completed') }}');
                    }
                }
            } else {
                console.warn('updateTestStatus function not available');
            }
        }

        startSessionPolling();

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
        
        // メール設定変更時にテスト結果をリセットする関数
        function resetMailTestStatus() {
            fetch('{{ route("admin.settings.base.clear-test-session") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).catch(error => {
                console.log('{{ __('admin.settings.base.view_messages.session_clear_error') }}', error);
            });
            
            resetTestStatusUI();
            disableMailTestButton();
        }
        
        function resetTestStatusUI() {
            console.log('=== resetTestStatusUI デバッグ開始 ===');
            const testTypes = ['connection', 'send', 'receive'];
            
            testTypes.forEach(testType => {
                const iconId = `${testType}-test-icon`;
                const textId = `${testType}-test-text`;
                const dateId = `${testType}-test-date`;
                
                // メイン要素のIDも確認
                const iconMainId = `${testType}-test-icon-main`;
                const textMainId = `${testType}-test-text-main`;
                const dateMainId = `${testType}-test-date-main`;
                
                const icon = document.getElementById(iconId);
                const text = document.getElementById(textId);
                const dateSpan = document.getElementById(dateId);
                
                const iconMain = document.getElementById(iconMainId);
                const textMain = document.getElementById(textMainId);
                const dateSpanMain = document.getElementById(dateMainId);
                
                console.log(`${testType}テスト要素:`, {
                    icon: icon ? 'found' : 'not found',
                    text: text ? 'found' : 'not found',
                    dateSpan: dateSpan ? 'found' : 'not found',
                    iconMain: iconMain ? 'found' : 'not found',
                    textMain: textMain ? 'found' : 'not found',
                    dateSpanMain: dateSpanMain ? 'found' : 'not found'
                });
                
                // ステータス表示要素をリセット
                if (icon && text && dateSpan) {
                    console.log(`${testType}ステータス表示をリセット`);
                    icon.className = 'mr-2 fas fa-times-circle text-gray-400';
                    text.className = 'text-sm text-gray-600 dark:text-gray-400';
                    dateSpan.textContent = '';
                }
                
                // メイン表示要素もリセット
                if (iconMain && textMain && dateSpanMain) {
                    console.log(`${testType}メイン表示をリセット`);
                    iconMain.className = 'mr-2 fas fa-times-circle text-gray-400';
                    textMain.className = 'text-sm text-gray-600 dark:text-gray-400';
                    dateSpanMain.textContent = '';
                }
            });
            console.log('=== resetTestStatusUI デバッグ終了 ===');
            
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
        
        function disableMailTestButton() {
            const mailTestBtn = document.getElementById('test-mail-btn');
            if (mailTestBtn) {
                mailTestBtn.disabled = true;
                mailTestBtn.className = 'py-2 px-4 rounded transition-colors duration-200 font-bold bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed';
            }
        }
        
        function showNotification(type, message) {
            const existingNotification = document.getElementById('mail-test-notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
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
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
    });
</script>
@endsection
