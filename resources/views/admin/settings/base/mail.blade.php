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
<form id="base-mail-form" action="{{ route('admin.settings.base.mail.update') }}" method="POST" class="overflow-x-hidden">
    @csrf

    <!-- メールサーバー設定 -->
    <section>
        <h2>{{ __('admin/settings/base/mail.mail_server_settings') }}</h2>

        <x-mail_server_form
            :settings="$settings"
            :mailers="$mailers"
            :encryptions="$encryptions"
            context="admin"
        />

        <x-mail_test
            context="admin"
            :connectionTestRoute="route('admin.settings.base.mail.test-connection')"
            :mailTestRoute="route('admin.settings.base.mail.test-mail')"
            :showStatus="true"
            :testStatus="[
                'connection_tested' => $mailConnectionTested,
                'send_tested' => $mailSendTested,
                'receive_tested' => $mailReceiveTested,
                'connection_test_date' => $mailConnectionTestDate,
                'send_test_date' => $mailSendTestDate,
                'receive_test_date' => $mailReceiveTestDate
            ]"
        />
    </section>

    <!-- システム管理者メールアドレス -->
    <section>
        <h2>{{ __('admin/settings/base/mail.admin_email_settings') }}</h2>
        <p>{{ __('admin/settings/base/mail.admin_email_settings_description') }}</p>

        <!-- メールサーバー設定の確認メッセージ -->
        @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
            <x-message
                type="warning"
                :message="__('admin/settings/base/mail.admin_email_mail_test_required')"
            />
        @endif

        <fieldset>
            <legend>{{ __('admin/settings/base/mail.admin_email') }}</legend>
            <x-form.text
                type="email"
                name="system_admin_email"
                :value="old('system_admin_email', $settings['system_admin_email'])"
                class="input-lg"
            />
            <p>{{ __('admin/settings/base/mail.admin_email_help') }}</p>
        </fieldset>
    </section>
</form>
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
        form="base-mail-form"
    />
@endsection

@section('scripts')
<script @cspNonce>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('base-mail-form');

        // メール認証ウィンドウからのpostMessageを受信
        window.addEventListener('message', function(event) {
            if (event.origin !== window.location.origin) {
                return;
            }
            
            if (event.data.type === 'mail_receive_test_completed') {
                updateTestStatus('receive', true, null);
                showNotification('success', '{{ __('admin/settings/base/mail.mail_receive_test_completed') }}');
            }
        });

        // セッション状態を定期的にチェック
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
            fetch('{{ route("admin.settings.base.mail.check-test-session") }}', {
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
                console.log('Session check error', error);
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
                        showNotification('success', '{{ __('admin/settings/base/mail.mail_receive_test_completed') }}');
                    }
                }
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
        
        function resetMailTestStatus() {
            fetch('{{ route("admin.settings.base.mail.clear-test-session") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).catch(error => {
                console.log('Session clear error', error);
            });
            
            resetTestStatusUI();
            disableMailTestButton();
        }
        
        function resetTestStatusUI() {
            const testTypes = ['connection', 'send', 'receive'];
            
            testTypes.forEach(testType => {
                const icon = document.getElementById(`${testType}-test-icon`);
                const text = document.getElementById(`${testType}-test-text`);
                const dateSpan = document.getElementById(`${testType}-test-date`);
                
                if (icon && text && dateSpan) {
                    icon.className = 'mr-2 fas fa-times-circle text-gray-400';
                    text.className = 'text-sm text-gray-600 dark:text-gray-400';
                    dateSpan.textContent = '';
                }
            });
            
            const mainStatusDiv = document.querySelector('.mt-6.p-4.border.rounded-lg');
            const mainIcon = mainStatusDiv?.querySelector('i');
            const mainTitle = mainStatusDiv?.querySelector('h3');
            
            if (mainStatusDiv && mainIcon && mainTitle) {
                mainStatusDiv.className = 'mt-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
                mainIcon.className = 'fas fa-exclamation-triangle text-yellow-400 text-xl';
                mainTitle.className = 'text-sm font-medium text-yellow-800 dark:text-yellow-200';
                mainTitle.textContent = '{{ __('admin/settings/base/mail.mail_test_incomplete') }}';
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
