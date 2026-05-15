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
      (see LICENSE.commercial, or contact info@dixlase.org).

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

@extends('layouts.admin')

@section('content')
<div class="mx-auto" x-data="apiSettings()">

    {{-- Display generated key (one time only) --}}
    @if(session('generated_key'))
    <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-600 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-3"></i>
            <div class="flex-1">
                <h3 class="font-bold text-yellow-800 dark:text-yellow-200">{{ __('admin/settings/systems/api.key_generated_warning') }}</h3>
                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">{{ __('admin/settings/systems/api.key_generated_warning_detail') }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <code id="generated-key" class="flex-1 p-3 bg-white dark:bg-gray-800 border border-yellow-400 rounded font-mono text-sm break-all">{{ session('generated_key') }}</code>
                    <button type="button" @click="copyToClipboard('generated-key')" class="btn btn-secondary">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- API settings form --}}
    <form id="api-settings-form" action="{{ route('admin.settings.systems.api.update') }}" method="POST">
        @csrf

        <section>
            <h2>{{ __('admin/settings/systems/api.general_settings') }}</h2>

            <fieldset>
                <legend>{{ __('admin/settings/systems/api.api_enabled') }}</legend>
                <x-form-toggle
                    name="api_enabled"
                    :checked="old('api_enabled', $settings['api_enabled']) == '1'"
                    x-model="apiEnabled"
                />
                <p>{{ __('admin/settings/systems/api.api_enabled_help') }}</p>
            </fieldset>

            <fieldset class="transition-opacity" :class="{ 'opacity-50 pointer-events-none': apiEnabled !== '1' }">
                <legend>{{ __('admin/settings/systems/api.signature_required') }}</legend>
                <x-form-toggle
                    name="api_signature_required"
                    :checked="old('api_signature_required', $settings['api_signature_required']) == '1'"
                />
                <p>{{ __('admin/settings/systems/api.signature_required_help') }}</p>
            </fieldset>

            <fieldset class="transition-opacity" :class="{ 'opacity-50 pointer-events-none': apiEnabled !== '1' }">
                <legend>{{ __('admin/settings/systems/api.default_rate_limit') }}</legend>
                <div class="flex items-center gap-2">
                    <x-form-text
                        name="api_rate_limit"
                        type="number"
                        :value="old('api_rate_limit', $settings['api_rate_limit'])"
                        class="input-sm"
                        min="1"
                        max="10000"
                    />
                    <span class="text-gray-600 dark:text-gray-400">{{ __('admin/settings/systems/api.requests_per_minute') }}</span>
                </div>
                <p>{{ __('admin/settings/systems/api.rate_limit_help') }}</p>
            </fieldset>

        </section>
    </form>

    {{-- API key management --}}
    <section class="mt-8 transition-opacity" :class="{ 'opacity-50 pointer-events-none': apiEnabled !== '1' }">
        <div class="flex items-center justify-between mb-4">
            <h2 class="mb-0">{{ __('admin/settings/systems/api.api_keys') }}</h2>
            <x-form-button
                type="button"
                variant="primary"
                icon="fas fa-plus"
                :label="__('admin/settings/systems/api.create_key')"
                @click="openModal('createKeyModal')"
                xDisabled="apiEnabled !== '1'"
            />
        </div>

        @if($apiKeys->isEmpty())
        <div class="p-8 text-center bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <i class="fas fa-key text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">{{ __('admin/settings/systems/api.no_keys') }}</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left">{{ __('admin/settings/systems/api.key_name') }}</th>
                        <th class="text-left">{{ __('admin/settings/systems/api.key_prefix') }}</th>
                        <th class="text-left">{{ __('admin/settings/systems/api.environment') }}</th>
                        <th class="text-left">{{ __('admin/settings/systems/api.status') }}</th>
                        <th class="text-left">{{ __('admin/settings/systems/api.last_used') }}</th>
                        <th class="text-left">{{ __('admin/settings/systems/api.usage_count') }}</th>
                        <th class="text-right">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($apiKeys as $key)
                    <tr class="{{ session('generated_key_id') == $key->id ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}">
                        <td>
                            <div class="font-medium">{{ $key->name }}</div>
                            @if($key->description)
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($key->description, 50) }}</div>
                            @endif
                        </td>
                        <td>
                            <code class="text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $key->getMaskedKey() }}</code>
                        </td>
                        <td>
                            @if($key->environment === 'live')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <i class="fas fa-circle text-[6px] mr-1"></i>Live
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                <i class="fas fa-circle text-[6px] mr-1"></i>Test
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($key->isExpired())
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                {{ __('admin/settings/systems/api.expired') }}
                            </span>
                            @elseif($key->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                {{ __('common.active') }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                {{ __('common.inactive') }}
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($key->last_used_at)
                            <span class="text-sm">{{ $key->last_used_at->diffForHumans() }}</span>
                            @else
                            <span class="text-sm text-gray-400">{{ __('admin/settings/systems/api.never_used') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-sm">{{ number_format($key->usage_count) }}</span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="viewKey({{ $key->id }})" class="btn btn-sm btn-secondary" title="{{ __('admin/settings/systems/api.key_details') }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <form id="regenerate-form-{{ $key->id }}" action="{{ route('admin.settings.systems.api.regenerate-key', $key->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="button" @click="openModal('regenerateModal{{ $key->id }}')" class="btn btn-sm btn-warning" title="{{ __('admin/settings/systems/api.regenerate') }}">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                                <form id="revoke-form-{{ $key->id }}" action="{{ route('admin.settings.systems.api.revoke-key', $key->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" @click="openModal('revokeModal{{ $key->id }}')" class="btn btn-sm btn-danger" title="{{ __('common.delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>

    {{-- API key details modal --}}
    <x-ui-modal
        id="viewKeyModal"
        :title="__('admin/settings/systems/api.key_details')"
        icon-type="info"
        :close-only="true"
    >
        <div class="px-4 pt-5 pb-4 sm:p-6">
            <div x-html="keyDetails" class="space-y-3"></div>
        </div>

        <x-slot name="footer">
            <x-form-button
                type="button"
                variant="secondary"
                :label="__('common.close')"
                @click="closeModal('viewKeyModal')"
                class="mx-2"
            />
        </x-slot>
    </x-ui-modal>

    {{-- Update/delete confirmation modal for each API key --}}
    @foreach($apiKeys as $key)
        {{-- Regeneration confirmation modal --}}
        <x-ui-modal
            id="regenerateModal{{ $key->id }}"
            :title="__('admin/settings/systems/api.regenerate')"
            :message="__('admin/settings/systems/api.regenerate_confirm')"
            icon-type="warning"
            :confirm_label="__('admin/settings/systems/api.regenerate')"
            :cancel_label="__('common.cancel')"
            form="regenerate-form-{{ $key->id }}"
        />

        {{-- Delete confirmation modal --}}
        <x-ui-modal
            id="revokeModal{{ $key->id }}"
            :title="__('common.delete')"
            :message="__('admin/settings/systems/api.revoke_confirm')"
            icon-type="danger"
            :confirm_label="__('common.delete')"
            :cancel_label="__('common.cancel')"
            form="revoke-form-{{ $key->id }}"
        />
    @endforeach

</div>
@endsection

@section('save')
    <x-form-button
        type="button"
        :label="__('common.save')"
        class="button-save"
        @click="openModal('apiSettingsConfirmationModal')"
    />
@endsection

@section('modals')
    {{-- Save confirmation modal --}}
    <x-ui-modal
        id="apiSettingsConfirmationModal"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="api-settings-form"
    />

    {{-- API key creation modal --}}
    <x-ui-modal
        id="createKeyModal"
        :title="__('admin/settings/systems/api.create_key')"
        icon-type="info"
    >
        <form id="create-key-form" action="{{ route('admin.settings.systems.api.generate-key') }}" method="POST" class="px-4 pt-5 pb-4 sm:p-6">
            @csrf
            <div class="space-y-3">
                <fieldset>
                    <legend>{{ __('admin/settings/systems/api.key_name') }}</legend>
                    <x-form-text
                        name="name"
                        :placeholder="__('admin/settings/systems/api.key_name_placeholder')"
                        required
                    />
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/settings/systems/api.environment') }}</legend>
                    <x-form-select
                        name="environment"
                        :options="[
                            'live' => 'Live (' . __('admin/settings/systems/api.env_live_desc') . ')',
                            'test' => 'Test (' . __('admin/settings/systems/api.env_test_desc') . ')'
                        ]"
                        value="live"
                    />
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/settings/systems/api.scopes') }}</legend>
                    <div class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded p-3">
                        <x-form-checkbox-group
                            name="scopes"
                            :options="$availableScopes"
                            flexDirection="col"
                        />
                    </div>
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/settings/systems/api.rate_limit') }}</legend>
                    <div class="flex items-center gap-2">
                        <x-form-text
                            name="rate_limit"
                            type="number"
                            :placeholder="__('admin/settings/systems/api.unlimited')"
                            min="0"
                            max="10000"
                            class="input-sm"
                        />
                        <span class="text-sm text-gray-500">{{ __('admin/settings/systems/api.requests_per_minute') }}</span>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/settings/systems/api.allowed_ips') }}</legend>
                    <x-form-text
                        name="allowed_ips"
                        :placeholder="__('admin/settings/systems/api.allowed_ips_placeholder')"
                    />
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin/settings/systems/api.allowed_ips_help') }}</p>
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/settings/systems/api.expires_at') }}</legend>
                    <x-form-text
                        name="expires_at"
                        type="date"
                        min="{{ now()->addDay()->format('Y-m-d') }}"
                    />
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin/settings/systems/api.expires_at_help') }}</p>
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/settings/systems/api.key_description') }}</legend>
                    <x-form-textarea
                        name="description"
                        rows="2"
                        :placeholder="__('admin/settings/systems/api.key_description_placeholder')"
                    />
                </fieldset>
            </div>
        </form>

        <x-slot name="footer">
            <x-form-button
                type="button"
                variant="secondary"
                :label="__('common.cancel')"
                @click="closeModal('createKeyModal')"
                class="mx-2"
            />
            <x-form-button
                type="submit"
                variant="primary"
                icon="fas fa-key"
                :label="__('admin/settings/systems/api.generate')"
                form="create-key-form"
                class="mx-2"
            />
        </x-slot>
    </x-ui-modal>
@endsection

@push('scripts')
<script id="api-settings-config" type="application/json">
    @json($apiConfig)
</script>
@endpush
