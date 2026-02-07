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

<div>
    <!-- エラー通知設定 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            {{ __('admin.settings.security.error_notification') }}
        </h2>

        <!-- メール設定未完了の警告 -->
        @if(!$mailConnectionTested || !$mailSendTested || !$mailReceiveTested)
            <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3"></i>
                    <div class="text-sm text-yellow-700 dark:text-yellow-300">
                        {!! __('admin.settings.security.error_notification_mail_test_required') !!}
                    </div>
                </div>
            </div>
        @endif

        <!-- 通知有効/無効 -->
        <div class="mb-6">
            <label class="flex items-center space-x-3 cursor-pointer">
                <input 
                    type="checkbox" 
                    wire:model="notificationEnabled"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                >
                <span class="text-sm font-medium text-gray-900 dark:text-gray-300">
                    {{ __('admin.settings.security.notification_enabled') }}
                </span>
            </label>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.settings.security.notification_enabled_help') }}
            </p>
        </div>

        <!-- ログレベル選択 -->
        <div class="mb-6" x-data="{ enabled: @entangle('notificationEnabled') }">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('admin.settings.security.notification_log_levels') }}
            </label>
            <div class="space-y-2" :class="{ 'opacity-50 pointer-events-none': !enabled }">
                @foreach(\App\Enums\LogLevel::getNotificationLevelStrings() as $level => $levelString)
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input 
                            type="checkbox" 
                            wire:model="notificationLogLevels"
                            value="{{ $level }}"
                            :disabled="!enabled"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                        <span class="text-sm text-gray-900 dark:text-gray-300">
                            {{ __('admin.settings.security.log_levels.' . $levelString) }}
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.settings.security.notification_log_levels_help') }}
            </p>
        </div>
    </div>

    <!-- 保存ボタン（Livewire版） -->
    <div class="mt-6">
        <x-admin.livewire-save-button
            wireClick="save"
            showConfirmation="showSaveConfirmation"
            :label="__('common.save')"
            :title="__('common.save_confirmation_title')"
            :message="__('common.save_confirmation_message')"
            :confirmLabel="__('common.save')"
            :cancelLabel="__('common.cancel')"
        />
    </div>
</div>
