{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-two-fa.management />

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

@props([
    'twoFaPasskeyEnabled' => false,
    'twoFaPasskeyDevices' => null,
    'twoFaPasskeyMaxDevices' => 5,
    'twoFaPasskeyCurrentCount' => 0,
    'canRegisterMorePasskeys' => true,
    'twoFaHasRecoveryCodes' => false,
    'twoFaRecoveryCodesCount' => 0,
    'twoFaTrustedDevices' => null,
    'twoFaShowTrustedDevices' => false,
    'hideAddButtons' => false,
    'hideGenerateButton' => false,
    'adminContext' => false,
    'disabled' => false,
    'twoFaDisabled' => false,
    'passkeyDisabled' => false,
    'routes' => [
        'passkey_register_options' => '',
        'passkey_register' => '',
        'passkey_delete' => '',
        'passkey_delete_all' => '',
        'recovery_codes_generate' => '',
        'recovery_codes_delete' => '',
        'trusted_device_delete' => '',
        'trusted_device_delete_all' => '',
    ],
    'csrfToken' => '',
])

@php
    $translations = [
        'error' => __('common.error'),
        'passkey_not_supported' => __('components/security/two-fa-management.passkey_not_supported'),
        'passkey_register_success' => __('components/security/two-fa-management.passkey_register_success'),
        'passkey_register_error' => __('components/security/two-fa-management.passkey_register_error'),
        'passkey_cancelled' => __('components/security/two-fa-management.passkey_cancelled'),
        'passkey_already_registered' => __('components/security/two-fa-management.passkey_already_registered'),
        'passkey_delete_success' => __('components/security/two-fa-management.passkey_delete_success'),
        'passkey_delete_error' => __('components/security/two-fa-management.passkey_delete_error'),
        'passkey_delete_all_error' => __('components/security/two-fa-management.passkey_delete_all_error'),
        'confirm_delete_passkey' => __('components/security/two-fa-management.confirm_delete_passkey'),
        'recovery_codes_error' => __('components/security/two-fa-management.recovery_codes_error'),
    ];
@endphp

<section class="mt-8 transition-colors-unified {{ $disabled ? 'opacity-50 pointer-events-none' : '' }}" 
    x-data='twoFaManagement(@json($routes), "{{ $csrfToken }}", @json($translations))'
    x-init="window.twoFaManagementInstance = $data">
    <h2>{{ __('components/security/two-fa-management.title') }}</h2>

    <!-- Passkeyデバイス -->
    @if($twoFaPasskeyEnabled)
    <div class="{{ $passkeyDisabled ? 'opacity-50 pointer-events-none' : '' }}">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ __('components/security/two-fa-management.passkey_devices') }}</h3>
            @if($twoFaPasskeyDevices && !$twoFaPasskeyDevices->isEmpty())
                <button 
                    type="button"
                    @click="openModal('deleteAllPasskeysModal')"
                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                    <i class="fas fa-trash-alt mr-1"></i>{{ __('components/security/two-fa-management.delete_all') }}
                </button>
            @endif
        </div>
        
        @if(!$twoFaPasskeyDevices || $twoFaPasskeyDevices->isEmpty())
            <!-- Passkeyデバイス未登録の警告 -->
            @if(!$adminContext)
                <x-ui-message 
                    type="warning" 
                    :message="'<strong>' . __('components/security/two-fa-management.passkey_warning_title') . '</strong><br>' . __('components/security/two-fa-management.passkey_warning_message') . '<br>' . __('components/security/two-fa-management.passkey_warning_action')" 
                />
            @endif
            
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('components/security/two-fa-management.no_passkey_devices') }}</p>
        @else
            <div class="space-y-4 mb-4">
                @foreach($twoFaPasskeyDevices as $device)
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-key text-green-600 dark:text-green-400 mr-2"></i>
                                <h4 class="font-semibold">{{ $device->name }}</h4>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <p><strong>{{ __('components/security/two-fa-management.registered_at') }}:</strong> {{ $device->created_at->format('Y-m-d H:i') }}</p>
                                @if($device->last_used_at)
                                    <p><strong>{{ __('components/security/two-fa-management.last_used') }}:</strong> {{ $device->last_used_at->format('Y-m-d H:i') }}</p>
                                @endif
                            </div>
                        </div>
                        <button 
                            type="button"
                            @click="openDeletePasskeyModal('{{ $device->id }}', '{{ $device->name }}')"
                            class="ml-4 px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                            {{ __('components/security/two-fa-management.delete') }}
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
        
        @if(!$hideAddButtons)
        <!-- デバイス登録数の表示 -->
        <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
            {{ __('components/security/two-fa-management.device_count', [
                'current' => $twoFaPasskeyCurrentCount,
                'max' => $twoFaPasskeyMaxDevices
            ]) }}
        </div>
        
        <!-- 新しいPasskeyを追加 -->
        @if($canRegisterMorePasskeys)
            <button 
                type="button"
                @click="registerPasskey()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                <i class="fas fa-plus mr-2"></i>{{ __('components/security/two-fa-management.add_passkey') }}
            </button>
        @else
            <button 
                type="button"
                disabled
                class="px-4 py-2 bg-gray-400 text-white rounded cursor-not-allowed opacity-50">
                <i class="fas fa-plus mr-2"></i>{{ __('components/security/two-fa-management.add_passkey') }}
            </button>
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ __('components/security/two-fa-management.max_devices_reached', ['max' => $twoFaPasskeyMaxDevices]) }}
            </p>
        @endif
        @endif

        <!-- Passkeyの説明（管理者コンテキストでは非表示） -->
        @if(!$adminContext)
        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-info-circle mr-2"></i>{{ __('components/security/two-fa-management.passkey_info_title') }}
            </h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                <li>{{ __('components/security/two-fa-management.passkey_info_1') }}</li>
                <li>{{ __('components/security/two-fa-management.passkey_info_2') }}</li>
                <li>{{ __('components/security/two-fa-management.passkey_info_3') }}</li>
            </ul>
        </div>
        @endif
    </div>
    @endif

    <!-- 回復コード -->
    @if(!$twoFaDisabled)
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ __('components/security/two-fa-management.recovery_codes_title') }}</h3>
        </div>

        @if($twoFaHasRecoveryCodes)
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <i class="fas fa-info-circle mr-2"></i>
                        {{ __('components/security/two-fa-management.recovery_codes_remaining', ['count' => $twoFaRecoveryCodesCount]) }}
                    </p>
                    @if($adminContext && isset($routes['recovery_codes_delete']))
                    <button 
                        type="button"
                        @click="openModal('deleteRecoveryCodesModal')"
                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                        <i class="fas fa-trash mr-1"></i>{{ __('components/security/two-fa-management.delete') }}
                    </button>
                    @endif
                </div>
            </div>
        @else
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('components/security/two-fa-management.recovery_codes_not_generated') }}</p>
        @endif
        
        @if(!$hideGenerateButton)
        <button 
            type="button"
            @click="openModal('recoveryCodesConfirmModal')"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-{{ $twoFaHasRecoveryCodes ? 'sync-alt' : 'plus' }} mr-2"></i>{{ __('components/security/two-fa-management.recovery_codes_' . ($twoFaHasRecoveryCodes ? 'regenerate' : 'generate')) }}
        </button>
        @endif
        
        <!-- 回復コードの説明（管理者コンテキストでは非表示） -->
        @if(!$adminContext)
        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-info-circle mr-2"></i>{{ __('components/security/two-fa-management.recovery_codes_info_title') }}
            </h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                <li>{{ __('components/security/two-fa-management.recovery_codes_info_1') }}</li>
                <li>{{ __('components/security/two-fa-management.recovery_codes_info_2') }}</li>
                <li>{{ __('components/security/two-fa-management.recovery_codes_info_3') }}</li>
                <li>{{ __('components/security/two-fa-management.recovery_codes_info_4') }}</li>
                <li>{{ __('components/security/two-fa-management.recovery_codes_info_5') }}</li>
                <li>{{ __('components/security/two-fa-management.recovery_codes_info_6') }}</li>
            </ul>
        </div>
        @endif
    </div>
    @endif

    <!-- 信頼済みデバイス管理 -->
    @if($twoFaShowTrustedDevices)
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">{{ __('components/security/two-fa-management.trusted_devices_title') }}</h3>
            @if($twoFaTrustedDevices && !$twoFaTrustedDevices->isEmpty())
                <button 
                    type="button"
                    @click="openModal('deleteAllTrustedDevicesModal')"
                    class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                    <i class="fas fa-trash-alt mr-1"></i>{{ __('components/security/two-fa-management.delete_all') }}
                </button>
            @endif
        </div>
        
        @if(!$twoFaTrustedDevices || $twoFaTrustedDevices->isEmpty())
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('components/security/two-fa-management.no_trusted_devices') }}</p>
        @else
            <div class="space-y-4 mb-4">
                @foreach($twoFaTrustedDevices as $device)
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-mobile-alt text-blue-600 dark:text-blue-400 mr-2"></i>
                                <h4 class="font-semibold">{{ $device->device_name ?? __('components/security/two-fa-management.unknown_device') }}</h4>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <p><strong>{{ __('components/security/two-fa-management.ip_address') }}:</strong> {{ $device->ip_address }}</p>
                                <p><strong>{{ __('components/security/two-fa-management.registered_at') }}:</strong> {{ $device->created_at->format('Y-m-d H:i') }}</p>
                                @if($device->last_used_at)
                                    <p><strong>{{ __('components/security/two-fa-management.last_used') }}:</strong> {{ $device->last_used_at->format('Y-m-d H:i') }}</p>
                                @endif
                            </div>
                        </div>
                        <button 
                            type="button"
                            @click="openDeleteTrustedDeviceModal('{{ $device->id }}', '{{ $device->device_name ?? __('components/security/two-fa-management.unknown_device') }}')"
                            class="ml-4 px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-sm">
                            {{ __('components/security/two-fa-management.delete') }}
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- 信頼済みデバイスの説明 -->
        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                <i class="fas fa-info-circle mr-2"></i>{{ __('components/security/two-fa-management.trusted_devices_info_title') }}
            </h4>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
                <li>{{ __('components/security/two-fa-management.trusted_devices_info_1') }}</li>
                <li>{{ __('components/security/two-fa-management.trusted_devices_info_2') }}</li>
                <li>{{ __('components/security/two-fa-management.trusted_devices_info_3') }}</li>
            </ul>
        </div>
    </div>
    @endif
</section>

{{-- Modal --}}
<x-ui-modal 
    id="deleteTrustedDeviceModal"
    title="{{ __('components/security/two-fa-management.confirm_delete_trusted_device_title') }}"
    message="{{ __('components/security/two-fa-management.confirm_delete_trusted_device_message') }}"
    confirm_label="{{ __('common.delete') }}"
    cancel_label="{{ __('common.cancel') }}"
    icon_type="danger"
    confirm_color="red"
    form="deleteTrustedDeviceForm"
/>

<x-ui-modal 
    id="deleteAllTrustedDevicesModal"
    title="{{ __('components/security/two-fa-management.confirm_delete_all_trusted_devices_title') }}"
    message="{{ __('components/security/two-fa-management.confirm_delete_all_trusted_devices_message') }}"
    confirm_label="{{ __('common.delete') }}"
    cancel_label="{{ __('common.cancel') }}"
    icon_type="danger"
    confirm_color="red"
    form="deleteAllTrustedDevicesForm"
/>

<x-ui-modal 
    id="deletePasskeyModal"
    title="{{ __('components/security/two-fa-management.confirm_delete_passkey_title') }}"
    message="{{ __('components/security/two-fa-management.confirm_delete_passkey_message') }}"
    confirm_label="{{ __('common.delete') }}"
    cancel_label="{{ __('common.cancel') }}"
    icon_type="danger"
    confirm_color="red"
    form="deletePasskeyForm"
/>

<x-ui-modal 
    id="deleteAllPasskeysModal"
    title="{{ __('components/security/two-fa-management.confirm_delete_all_passkeys_title') }}"
    message="{{ __('components/security/two-fa-management.confirm_delete_all_passkeys_message') }}"
    confirm_label="{{ __('common.delete') }}"
    cancel_label="{{ __('common.cancel') }}"
    icon_type="danger"
    confirm_color="red"
    form="deleteAllPasskeysForm"
/>

<!-- 回復コード生成/再生成確認モーダル -->
<x-ui-modal 
    id="recoveryCodesConfirmModal" 
    title="{{ __('components/security/two-fa-management.recovery_codes_confirm_title') }}"
    message="{{ __('components/security/two-fa-management.recovery_codes_confirm_message') }}"
    confirm_label="{{ __('common.ok') }}"
    cancel_label="{{ __('common.cancel') }}"
    icon_type="warning"
    confirm_color="yellow"
    form="confirmGenerateRecoveryCodesForm"
/>

<!-- 回復コード削除確認モーダル（管理画面用） -->
@if($adminContext)
<x-ui-modal 
    id="deleteRecoveryCodesModal"
    title="{{ __('components/security/two-fa-management.confirm_delete_recovery_codes_title') }}"
    message="{{ __('components/security/two-fa-management.confirm_delete_recovery_codes_message') }}"
    confirm_label="{{ __('common.delete') }}"
    cancel_label="{{ __('common.cancel') }}"
    icon_type="danger"
    confirm_color="red"
    form="deleteRecoveryCodesForm"
/>
@endif

<!-- 回復コード表示モーダル（手動生成用・エラー表示兼用） -->
@include('two-fa.partials.recovery-codes-modal', [
    'modalId' => 'manualRecoveryCodesModal',
    'title' => __('two-fa/recovery-code.title'),
    'codes' => [],
    'isDynamic' => true
])

<!-- Passkeyデバイス名入力モーダル -->
@include('two-fa.partials.passkey-device-name-modal', [
    'modalId' => 'passkeyDeviceNameModal'
])

<!-- Passkey登録結果モーダル -->
@include('two-fa.partials.passkey-result-modal', [
    'modalId' => 'passkeyResultModal'
])
