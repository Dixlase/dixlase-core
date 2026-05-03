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
      (see LICENSE.commercial, or contact office@exc-d.com).

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
<div class="mx-auto">
<section>
    <h2>{{ __('admin/settings/systems/database.core_cleanup_heading') }}</h2>
    <p class="mb-6">{{ __('admin/settings/systems/database.core_cleanup_description') }}</p>
    
    @foreach($cleanupInfo as $type => $info)
        <section class="flex flex-col md:flex-row md:items-center justify-center md:justify-between">
            <div class="flex-1 mb-4 md:mb-0 md:mr-6">
                <h3 class="text-center md:text-left">{{ $info['name'] }}</h3>
                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">{{ $info['description'] }}</p>
                <code class="text-xs text-gray-500 dark:text-gray-400">{{ $info['table'] }}</code>
                
                @if($info['default_days'])
                <div class="mt-2">
                    <span class="text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-1 rounded-full inline-block">
                        {{ __('admin/settings/systems/database.default_retention', ['days' => $info['default_days']]) }}
                    </span>
                </div>
                @else
                <div class="mt-2">
                    <span class="text-xs text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-full inline-block">
                        {{ __('admin/settings/systems/database.expired_only') }}
                    </span>
                </div>
                @endif
                
                @if($info['default_days'])
                <div class="mt-4">
                    <form id="cleanupForm{{ ucfirst($type) }}" action="{{ route('admin.settings.systems.database.cleanup') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <label for="days_{{ $type }}" class="block text-sm font-medium mb-1">
                            {{ __('admin/settings/systems/database.days_label') }}
                        </label>
                        <input type="number" 
                            id="days_{{ $type }}" 
                            name="days" 
                            value="{{ $info['default_days'] }}" 
                            min="0" 
                            max="365"
                            class="input-common input-sm">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            {{ __('admin/settings/systems/database.days_zero_info') }}
                        </p>
                    </form>
                </div>
                @else
                <form id="cleanupForm{{ ucfirst($type) }}" action="{{ route('admin.settings.systems.database.cleanup') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="days" value="0">
                </form>
                @endif
            </div>
            
            <div class="flex justify-center md:justify-end flex-shrink-0">
                <x-form-button
                    type="button"
                    variant="danger"
                    :label="__('admin/settings/systems/database.cleanup_button')"
                    icon="fas fa-database"
                    @click="openModal('cleanupModal{{ ucfirst($type) }}')"
                />
            </div>
        </section>
    @endforeach

    <section class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex-1 mb-4 md:mb-0 md:mr-6">
            <h2>{{ __('admin/settings/systems/database.all_cleanup_button') }}</h2>
            <p>{{ __('admin/settings/systems/database.all_cleanup_description') }}</p>
            <p><strong>{{ __('common.warning') }}:</strong> {{ __('admin/settings/systems/database.all_cleanup_warning') }}</p>
            
            <form id="cleanupAllForm" action="{{ route('admin.settings.systems.database.cleanup') }}" method="POST" class="mt-4">
                @csrf
                <input type="hidden" name="type" value="all">
                
                <div>
                    <label for="all_days" class="block text-sm font-medium mb-1">
                        {{ __('admin/settings/systems/database.all_days_label') }}
                    </label>
                    <input type="number" 
                        id="all_days" 
                        name="all_days" 
                        value="30" 
                        min="0" 
                        max="365"
                        class="input-common input-sm">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ __('admin/settings/systems/database.all_days_help') }}
                    </p>
                </div>
            </form>
        </div>
        
        <div class="flex-shrink-0">
            <x-form-button
                type="button"
                variant="danger"
                :label="__('admin/settings/systems/database.all_cleanup_button')"
                icon="fas fa-trash-alt"
                @click="openModal('cleanupAllModal')"
            />
        </div>
    </section>

    {{-- プラグインのクリーンアップセクション --}}
    @if(!empty($pluginCleanupInfo))
    <section>
        <h2>{{ __('admin/settings/systems/database.plugin_cleanup_heading') }}</h2>
        <p class="mb-6">{{ __('admin/settings/systems/database.plugin_cleanup_description_config') }}</p>
        
        @foreach($pluginCleanupInfo as $key => $info)
                <div class="flex flex-col md:flex-row md:items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                    <div class="flex-1 mb-3 md:mb-0 md:mr-6">
                        <h4 class="font-medium">{{ $info['name'] }}</h4>
                        <code class="text-xs text-gray-500 dark:text-gray-400">{{ $info['table'] }}</code>
                        
                        @php
                            $modalId = 'cleanupModal' . Str::camel(str_replace(':', '', $key));
                        @endphp
                        <form id="cleanupForm{{ Str::camel(str_replace(':', '', $key)) }}" action="{{ route('admin.settings.systems.database.cleanup') }}" method="POST" class="mt-2">
                            @csrf
                            <input type="hidden" name="type" value="{{ $key }}">
                            <div class="flex items-center gap-2">
                                <label for="days_{{ Str::camel(str_replace(':', '', $key)) }}" class="text-sm">
                                    {{ __('admin/settings/systems/database.days_label') }}
                                </label>
                                <input type="number" 
                                    id="days_{{ Str::camel(str_replace(':', '', $key)) }}" 
                                    name="days" 
                                    value="{{ $info['default_days'] }}" 
                                    min="0" 
                                    max="365"
                                    class="input-common input-sm w-20">
                            </div>
                        </form>
                    </div>
                    
                    <div class="flex justify-end flex-shrink-0">
                        <x-form-button
                            type="button"
                            variant="danger"
                            size="sm"
                            :label="__('admin/settings/systems/database.cleanup_button')"
                            icon="fas fa-trash"
                            @click="openModal('{{ $modalId }}')"
                        />
                    </div>
                </div>
        @endforeach
    </section>
    @endif

   
</section>
</div>

<x-ui-message type="info">
    <x-slot name="message">
        <h3 class="text-lg font-semibold mb-3">{{ __('admin/settings/systems/database.info_panel.title') }}</h3>
        <ul class="text-sm space-y-2">
            <li>• {{ __('admin/settings/systems/database.info_panel.notes.irreversible') }}</li>
            <li>• {{ __('admin/settings/systems/database.info_panel.notes.performance') }}</li>
            <li>• {{ __('admin/settings/systems/database.info_panel.notes.production') }}</li>
            <li>• {{ __('admin/settings/systems/database.info_panel.notes.defaults') }}</li>
        </ul>
    </x-slot>
</x-ui-message>

@endsection

@section('modals')
    @foreach($cleanupInfo as $type => $info)
    <x-ui-modal
        id="cleanupModal{{ ucfirst($type) }}"
        title="{{ __('admin/settings/systems/database.modal.title') }}"
        message="{{ __('admin/settings/systems/database.modal.message_single', ['name' => $info['name']]) }}"
        confirm-label="{{ __('common.execute') }}"
        cancel-label="{{ __('common.cancel') }}"
        icon-type="danger"
        confirm-color="red"
        form="cleanupForm{{ ucfirst($type) }}"
    />
    @endforeach

    <x-ui-modal
        id="cleanupAllModal"
        title="{{ __('admin/settings/systems/database.modal.title') }}"
        message="{{ __('admin/settings/systems/database.modal.message_all') }}"
        confirm-label="{{ __('common.execute') }}"
        cancel-label="{{ __('common.cancel') }}"
        icon-type="danger"
        confirm-color="red"
        form="cleanupAllForm"
    />

    @if(!empty($pluginCleanupInfo))
    @foreach($pluginCleanupInfo as $key => $info)
    <x-ui-modal
        id="cleanupModal{{ Str::camel(str_replace(':', '', $key)) }}"
        title="{{ __('admin/settings/systems/database.modal.title') }}"
        message="{{ __('admin/settings/systems/database.modal.message_plugin', ['name' => $info['name'], 'plugin' => $info['plugin_name']]) }}"
        confirm-label="{{ __('common.execute') }}"
        cancel-label="{{ __('common.cancel') }}"
        icon-type="danger"
        confirm-color="red"
        form="cleanupForm{{ Str::camel(str_replace(':', '', $key)) }}"
    />
    @endforeach
    @endif
@endsection
