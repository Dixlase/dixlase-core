{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'passkeyEnabled' => false,
    'passkeyDevices' => null,
    'hasRecoveryCodes' => false,
    'recoveryCodesCount' => 0,
])

<section class="mt-8 transition-colors-unified">
    <h2>{{ __('admin/profile.2fa_management') }}</h2>

    <!-- Passkeyデバイス -->
    @if($passkeyEnabled)
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Passkeyデバイス</h3>
            @if($passkeyDevices && !$passkeyDevices->isEmpty())
                <button 
                    type="button"
                    onclick="openModal('deleteAllPasskeysModal')"
                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                    <i class="fas fa-trash-alt mr-1"></i>全て削除
                </button>
            @endif
        </div>
        
        @if(!$passkeyDevices || $passkeyDevices->isEmpty())
            <p class="text-gray-600 dark:text-gray-400 mb-4">Passkeyデバイスが登録されていません</p>
        @else
            <div class="space-y-4 mb-4">
                @foreach($passkeyDevices as $device)
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-key text-green-600 dark:text-green-400 mr-2"></i>
                                <h4 class="font-semibold">{{ $device->name }}</h4>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <p><strong>登録日時:</strong> {{ $device->created_at->format('Y-m-d H:i') }}</p>
                                @if($device->last_used_at)
                                    <p><strong>最終使用:</strong> {{ $device->last_used_at->format('Y-m-d H:i') }}</p>
                                @endif
                            </div>
                        </div>
                        <button 
                            type="button"
                            onclick="openDeletePasskeyModal('{{ $device->id }}', '{{ $device->name }}')"
                            class="ml-4 px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                            削除
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
        
        <!-- 新しいPasskeyを追加 -->
        <button 
            type="button"
            onclick="registerPasskey()"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
            <i class="fas fa-plus mr-2"></i>Passkeyを追加
        </button>

        <!-- Passkeyの説明 -->
        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-info-circle mr-2"></i>{{ __('admin/profile.passkey_info_title') }}
            </h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                <li>{{ __('admin/profile.passkey_info_1') }}</li>
                <li>{{ __('admin/profile.passkey_info_2') }}</li>
                <li>{{ __('admin/profile.passkey_info_3') }}</li>
            </ul>
        </div>
    </div>
    @endif

    <!-- 回復コード -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ __('two-factor.recovery_codes.title') }}</h3>
        </div>

        @if($hasRecoveryCodes)
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <i class="fas fa-info-circle mr-2"></i>
                    {{ __('two-factor.recovery_codes.remaining', ['count' => $recoveryCodesCount]) }}
                </p>
            </div>
        @else
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('two-factor.recovery_codes.not_generated') }}</p>
        @endif
        
        <button 
            type="button"
            onclick="openModal('recoveryCodesConfirmModal')"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-{{ $hasRecoveryCodes ? 'sync-alt' : 'plus' }} mr-2"></i>{{ __('two-factor.recovery_codes.' . ($hasRecoveryCodes ? 'regenerate' : 'generate')) }}
        </button>
        
        <!-- 回復コードの説明 -->
        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-info-circle mr-2"></i>{{ __('admin/profile.recovery_codes_info_title') }}
            </h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                <li>{{ __('admin/profile.recovery_codes_info_1') }}</li>
                <li>{{ __('admin/profile.recovery_codes_info_2') }}</li>
                <li>{{ __('admin/profile.recovery_codes_info_3') }}</li>
                <li>{{ __('admin/profile.recovery_codes_info_4') }}</li>
                <li>{{ __('admin/profile.recovery_codes_info_5') }}</li>
                <li>{{ __('admin/profile.recovery_codes_info_6') }}</li>
            </ul>
        </div>            
    </div>
</section>
