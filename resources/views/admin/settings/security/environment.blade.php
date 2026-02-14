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
<div x-data="{ 
    appEnv: '{{ old('app_env', $settings['app_env']) }}',
    appDebug: {{ old('app_debug', $settings['app_debug']) ? 'true' : 'false' }}
}">
    <form id="security-environment-form" method="POST" action="{{ route('admin.settings.security.environment.update') }}">
        @csrf
        
        <!-- 環境設定 -->
        <section>
            <h2>{{ __('admin/settings/security/environment.title') }}</h2>
            <p>{{ __('admin/settings/security/environment.description') }}</p>

            <!-- 動作環境 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/environment.app_env') }}</legend>
                
                <div class="my-3">
                    <x-form-radio-card-group
                        name="app_env"
                        :options="$environmentOptions"
                        :value="old('app_env', $settings['app_env'])"
                        xModel="appEnv"
                        columns="3"
                    />
                </div>
            </fieldset>

            <!-- 本番環境でデバッグモードONの警告 -->
            <template x-if="appEnv === 'production' && appDebug">
                <div class="mt-4">
                    <x-ui-message
                        type="error"
                        :message="__('admin/settings/security/environment.production_debug_warning')"
                    />
                </div>
            </template>

            <!-- デバッグモード -->
            <fieldset>
                <legend>{{ __('admin/settings/security/environment.app_debug') }}</legend>
                
                <x-form-toggle
                    :label="__('admin/settings/security/environment.app_debug')"
                    id="app_debug"
                    name="app_debug"
                    :checked="old('app_debug', $settings['app_debug'])"
                    xModel="appDebug"
                />
                
                <p>{!! __('admin/settings/security/environment.app_debug_help') !!}</p>

                <!-- デバッグモードの警告 -->
                <template x-if="appDebug">
                    <div class="mt-4">
                        <x-ui-message
                            type="warning"
                            :message="__('admin/settings/security/environment.debug_enabled_warning')"
                        />
                    </div>
                </template>
            </fieldset>

            <!-- 現在の状態 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/environment.current_status') }}</legend>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/security/environment.current_env') }}</span>
                            @php
                                $envColors = [
                                    'local' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'staging' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'production' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $envColors[$settings['app_env']] ?? $envColors['local'] }}">
                                {{ __('admin/settings/security/environment.env_options.' . $settings['app_env']) }}
                            </span>
                        </div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/security/environment.current_debug') }}</span>
                            @if($settings['app_debug'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    <i class="fas fa-bug mr-1"></i>{{ __('common.enabled') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ __('common.disabled') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </fieldset>
        </section>

    </form>
</div>
</div>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('admin/settings/security/environment.save_confirmation_title')"
        :message="__('admin/settings/security/environment.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="security-environment-form"
    />
@endsection
