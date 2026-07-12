{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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

{{--
    Mail test feature common component
    
    @param string $context - 'install' or 'admin' (default: 'admin')
    @param string $connectionTestRoute - Route for connection test
    @param string $mailTestRoute - Route for mail sending test
    @param bool $showStatus - Whether to include test status display (default: false, for admin panel)
    @param array $testStatus - Test status data (for admin panel)
--}}

@php
    $context = $context ?? 'admin';
    $isInstall = $context === 'install';
    $showStatus = $showStatus ?? false;

    // View-only dim for the admin context only. During install the user
    // is anonymous (menuEditable is unset), so `? true` fallback keeps
    // the buttons active. Server-side check.menu.edit:settings.base.mail
    // was added by PR #135 as a second-line guard against curl posts.
    $viewOnly = ! $isInstall && ! ($menuEditable ?? true);
    $tooltipText = $viewOnly ? __('common.view_only_action_disabled') : '';
@endphp

@if($showStatus && !$isInstall)
    <!-- メール機能テスト状態の表示 (管理画面用) -->
    <div id="mail-test-main-status" class="mt-6 p-4 border rounded-lg 
        @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
            bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800
        @else
            bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800
        @endif
    ">
        <div class="flex items-start mb-4">
            <div class="flex-shrink-0">
                @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                @else
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                @endif
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium 
                    @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
                        text-green-800 dark:text-green-200
                    @else
                        text-yellow-800 dark:text-yellow-200
                    @endif
                ">
                    @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
                        {{ __('admin/settings/base/mail.view_messages.mail_test_complete') }}
                    @else
                        {{ __('admin/settings/base/mail.view_messages.mail_test_incomplete') }}
                    @endif
                </h3>
            </div>
        </div>

        <!-- テスト進捗状況 -->
        <div class="space-y-2">
            <!-- 接続テスト -->
            <div id="connection-test-status" class="flex items-center">
                <i id="connection-test-icon" class="mr-2 {{ $testStatus['connection_tested'] ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-gray-400' }}"></i>
                <span id="connection-test-text" class="text-sm {{ $testStatus['connection_tested'] ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                    1. {{ __('admin/settings/base/mail.view_messages.connection_test') }}
                    <span id="connection-test-date">
                        @if($testStatus['connection_tested'] && $testStatus['connection_test_date'])
                            ({{ $testStatus['connection_test_date'] }})
                        @endif
                    </span>
                </span>
            </div>

            <!-- 送信テスト -->
            <div id="send-test-status" class="flex items-center">
                <i id="send-test-icon" class="mr-2 {{ $testStatus['send_tested'] ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-gray-400' }}"></i>
                <span id="send-test-text" class="text-sm {{ $testStatus['send_tested'] ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                    2. {{ __('admin/settings/base/mail.view_messages.send_test') }}
                    <span id="send-test-date">
                        @if($testStatus['send_tested'] && $testStatus['send_test_date'])
                            ({{ $testStatus['send_test_date'] }})
                        @endif
                    </span>
                </span>
            </div>

            <!-- 受信確認テスト -->
            <div id="receive-test-status" class="flex items-center">
                <i id="receive-test-icon-main" class="mr-2 {{ $testStatus['receive_tested'] ? 'fas fa-check-circle text-green-500' : 'fas fa-times-circle text-gray-400' }}"></i>
                <span id="receive-test-text-main" class="text-sm {{ $testStatus['receive_tested'] ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400' }}">
                    3. {{ __('admin/settings/base/mail.view_messages.receive_test') }}
                    <span id="receive-test-date-main">
                        @if($testStatus['receive_tested'] && $testStatus['receive_test_date'])
                            ({{ $testStatus['receive_test_date'] }})
                        @endif
                    </span>
                </span>
            </div>
        </div>
    </div>
@endif

<!-- メールテスト機能 -->
<div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
        {{ __('mail-server/test.title') }}
    </h3>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        {{ __('mail-server/config.settings.mail_test_description') }}
        @if($isInstall)
            {{ __('mail-server/test.description_admin_email') }}
        @else
            {{ __('mail-server/test.description_logged_in_account') }}
        @endif
        <br>
        {{ __('mail-server/config.settings.mail_test_description_2') }}
    </p>
    <div class="flex flex-wrap gap-3">
        <button type="button" id="test-connection-btn"
            @if($viewOnly) disabled @endif
            @if($viewOnly) title="{{ $tooltipText }}" @endif
            class="bg-green-600 dark:bg-green-500 hover:bg-green-700 dark:hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-plug mr-2"></i>{{ __('mail-server/config.settings.test_connection_button') }}
        </button>
        <button
            type="button"
            id="test-mail-btn"
            @if($viewOnly) title="{{ $tooltipText }}" @endif
            class="
                font-bold py-2 px-4 rounded transition-colors duration-200
                disabled:opacity-50 disabled:cursor-not-allowed
                @if(($isInstall && $testStatus['connection_tested']) || (!$isInstall && (!$showStatus || $testStatus['connection_tested'])))
                    bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white
                @else
                    bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed
                @endif
            "
            @if($viewOnly || ($isInstall && !$testStatus['connection_tested']) || (!$isInstall && $showStatus && !$testStatus['connection_tested'])) disabled @endif
        >
            <i class="fas fa-envelope mr-2"></i>{{ __('mail-server/config.settings.test_mail_button') }}
        </button>
    </div>
    <div id="test-result" class="hidden mt-4"></div>

    @if($context === 'install')
    <!-- メール受信確認ステータス（インストール用） -->
    <div id="mail-test-status" class="mt-6 p-4 border rounded-lg @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested']) bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 @else bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 @endif">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i id="status-icon" class="@if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested']) fas fa-check-circle text-green-400 @else fas fa-exclamation-triangle text-yellow-400 @endif text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 id="status-title" class="text-sm font-medium @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested']) text-green-800 dark:text-green-200 @else text-yellow-800 dark:text-yellow-200 @endif">
                    @if($testStatus['connection_tested'] && $testStatus['send_tested'] && $testStatus['receive_tested'])
                        {{ __('mail-server/test.three_stage_test_complete') }}
                    @else
                        {{ __('mail-server/test.three_stage_test_incomplete') }}
                    @endif
                </h3>
                <div class="mt-3 text-sm text-yellow-700 dark:text-yellow-300">
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i id="connection-test-icon" class="mr-2 fas @if($testStatus['connection_tested']) fa-circle-check text-green-600 @else fa-times-circle text-gray-400 @endif"></i>
                            <span id="connection-test-text" class="text-sm @if($testStatus['connection_tested']) text-green-700 dark:text-green-300 @else text-gray-600 dark:text-gray-400 @endif">
                                {{ __('mail-server/test.connection_test') }}
                            </span>
                            <span id="connection-test-date" class="text-xs text-gray-500">@if($testStatus['connection_tested']) ({{ $testStatus['connection_test_date'] }}) @endif</span>
                        </li>
                        <li class="flex items-center">
                            <i id="send-test-icon" class="mr-2 fas @if($testStatus['send_tested']) fa-circle-check text-green-600 @else fa-times-circle text-gray-400 @endif"></i>
                            <span id="send-test-text" class="text-sm @if($testStatus['send_tested']) text-green-700 dark:text-green-300 @else text-gray-600 dark:text-gray-400 @endif">
                                {{ __('mail-server/test.send_test') }}
                            </span>
                            <span id="send-test-date" class="text-xs text-gray-500">@if($testStatus['send_tested']) ({{ $testStatus['send_test_date'] }}) @endif</span>
                        </li>
                        <li class="flex items-center">
                            <i id="receive-test-icon" class="mr-2 fas @if($testStatus['receive_tested']) fa-circle-check text-green-600 @else fa-circle-xmark text-gray-400 @endif"></i>
                            <span id="receive-test-text" class="text-sm @if($testStatus['receive_tested']) text-green-700 dark:text-green-300 @else text-gray-600 dark:text-gray-400 @endif">
                                {{ __('mail-server/test.receive_test') }}
                            </span>
                            <span id="receive-test-date" class="text-xs text-gray-500">@if($testStatus['receive_tested']) ({{ $testStatus['receive_test_date'] }}) @endif</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- CSP compliant: Pass settings via data attributes --}}
@php
$mailTestConfig = [
    'translations' => [
        'mailReceiveVerified' => __('mail-server/test.js_messages.mail_receive_verified'),
        'testRouteNotSet' => __('mail-server/test.js_messages.test_route_not_set'),
        'testing' => __('mail-server/test.js_messages.testing'),
        'connectionTestSuccessDefault' => __('mail-server/test.js_messages.connection_test_success_default'),
        'connectionTestFailedDefault' => __('mail-server/test.js_messages.connection_test_failed_default'),
        'mailTestFailedSideNote' => __('mail-server/test.js_messages.mail_test_failed_side_note'),
        'connectionTestError' => __('mail-server/test.js_messages.connection_test_error'),
        'testConnectionButton' => __('mail-server/config.settings.test_connection_button'),
        'mailTestRouteNotSet' => __('mail-server/test.js_messages.mail_test_route_not_set'),
        'connectionTestFirst' => __('mail-server/test.js_messages.connection_test_first'),
        'mailTestSuccessDefault' => __('mail-server/test.js_messages.mail_test_success_default'),
        'mailTestFailedDefault' => __('mail-server/test.js_messages.mail_test_failed_default'),
        'mailTestError' => __('mail-server/test.js_messages.mail_test_error'),
        'testMailButton' => __('mail-server/config.settings.test_mail_button'),
        'threeStageTestComplete' => __('mail-server/test.three_stage_test_complete'),
        'threeStageTestIncomplete' => __('mail-server/test.three_stage_test_incomplete')
    ],
    'routes' => [
        'connectionTest' => $connectionTestRoute ?? '',
        'mailTest' => $mailTestRoute ?? '',
        'mailSettings' => $context === 'admin' ? route('admin.settings.base.mail') : null
    ],
    'isInstall' => $isInstall,
    'context' => $context ?? 'admin'
];
@endphp
<div data-mail-test-config='@json($mailTestConfig)' style="display:none;"></div>

<!-- 通知コンポーネントを読み込み -->
<x-ui-notification />
