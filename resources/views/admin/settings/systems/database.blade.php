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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
<section>
    <h2>{{ __('admin/settings/systems/database.heading') }}</h2>
    
    @foreach($cleanupInfo as $type => $info)
        <section class="flex flex-col md:flex-row md:items-center justify-center md:justify-between">
            <div class="flex-1 mb-4 md:mb-0 md:mr-6">
                <h3 class="text-center md:text-left">{{ $info['name'] }}</h3>
                <p class="mb-4">{{ $info['description'] }}</p>
                <code class="text-sm rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 inline-block max-w-full overflow-x-auto">php artisan {{ $info['command'] }}</code>
                
                @if($info['default_days'])
                <div class="mt-2">
                    <span class="text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-1 rounded-full inline-block">
                        {{ __('admin/settings/systems/database.' . $type . '.default_days') }}
                    </span>
                </div>
                @else
                <div class="mt-2">
                    <span class="text-xs text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-full inline-block">
                        {{ __('admin/settings/systems/database.' . $type . '.default_days') }}
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
                        @if($info['default_days'])
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            {{ __('admin/settings/systems/database.days_zero_info') }}
                        </p>
                        @endif
                    </form>
                </div>
                @else
                <form id="cleanupForm{{ ucfirst($type) }}" action="{{ route('admin.settings.systems.database.cleanup') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                </form>
                @endif
            </div>
            
            <div class="flex justify-center md:justify-end flex-shrink-0">
                <x-form.button
                    type="button"
                    variant="danger"
                    :label="__('admin/settings/systems/database.cleanup_button')"
                    icon="fas fa-database"
                    onclick="openModal('cleanupModal{{ ucfirst($type) }}')"
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
            <x-form.button
                type="button"
                variant="danger"
                :label="__('admin/settings/systems/database.all_cleanup_button')"
                icon="fas fa-trash-alt"
                onclick="openModal('cleanupAllModal')"
            />
        </div>
    </section>

    {{-- プラグインのクリーンアップセクション --}}
    @if(!empty($pluginCleanupInfo))
    <section>
        <h2>{{ __('admin/settings/systems/database.plugin_cleanup_heading') }}</h2>
        <p class="mb-6">{{ __('admin/settings/systems/database.plugin_cleanup_description') }}</p>
        
        @php
            $groupedByPlugin = collect($pluginCleanupInfo)->groupBy('plugin_slug');
        @endphp
        
        @foreach($groupedByPlugin as $pluginSlug => $tables)
            @php $firstTable = $tables->first(); @endphp
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-puzzle-piece mr-2 text-purple-500"></i>
                    {{ $firstTable['plugin_name'] }}
                </h3>
                
                @foreach($tables as $key => $info)
                <div class="flex flex-col md:flex-row md:items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                    <div class="flex-1 mb-3 md:mb-0 md:mr-6">
                        <h4 class="font-medium">{{ $info['name'] }}</h4>
                        <code class="text-xs text-gray-500 dark:text-gray-400">{{ $info['table'] }}</code>
                        
                        <form id="cleanupForm{{ Str::camel($key) }}" action="{{ route('admin.settings.systems.database.cleanup') }}" method="POST" class="mt-2">
                            @csrf
                            <input type="hidden" name="type" value="{{ $key }}">
                            <div class="flex items-center gap-2">
                                <label for="days_{{ Str::camel($key) }}" class="text-sm">
                                    {{ __('admin/settings/systems/database.days_label') }}
                                </label>
                                <input type="number" 
                                    id="days_{{ Str::camel($key) }}" 
                                    name="days" 
                                    value="{{ $info['default_days'] }}" 
                                    min="0" 
                                    max="365"
                                    class="input-common input-sm w-20">
                            </div>
                        </form>
                    </div>
                    
                    <div class="flex justify-end flex-shrink-0">
                        <x-form.button
                            type="button"
                            variant="danger"
                            size="sm"
                            :label="__('admin/settings/systems/database.cleanup_button')"
                            icon="fas fa-trash"
                            onclick="openModal('cleanupModal{{ Str::camel($key) }}')"
                        />
                    </div>
                </div>
                @endforeach
            </div>
        @endforeach
    </section>
    @endif

    <section class="info-section">
        <div>
            <h2>{{ __('admin/settings/systems/database.info_title') }}</h2>
            <dl class="text-sm">
                <dt class="font-semibold">{{ __('admin/settings/systems/database.login_attempts.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin/settings/systems/database.info_login_attempts') }}</dd>
                
                <dt class="font-semibold">{{ __('admin/settings/systems/database.password_reset_tokens.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin/settings/systems/database.info_password_reset') }}</dd>
                
                <dt class="font-semibold">{{ __('admin/settings/systems/database.two_fa_attempts.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin/settings/systems/database.info_two_factor_attempts') }}</dd>
                
                <dt class="font-semibold">{{ __('admin/settings/systems/database.two_fa_tokens.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin/settings/systems/database.info_two_factor_tokens') }}</dd>
                
                <dt class="font-semibold">{{ __('admin/settings/systems/database.recovery_codes.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin/settings/systems/database.info_recovery_codes') }}</dd>
                
                <dt class="font-semibold">{{ __('admin/settings/systems/database.passkeys.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin/settings/systems/database.info_passkeys') }}</dd>
                
                <dt class="font-semibold">{{ __('admin/settings/systems/database.cache_data.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin/settings/systems/database.info_cache') }}</dd>
                
                <dt class="font-semibold">{{ __('admin/settings/systems/database.sessions.name') }}</dt>
                <dd class="font-normal">{{ __('admin/settings/systems/database.info_sessions') }}</dd>
            </dl>
        </div>
    </section>
</section>
</div>

<x-message type="info">
    <x-slot name="message">
        <h3 class="text-lg font-semibold mb-3">{{ __('admin/settings/systems/database.info_panel.title') }}</h3>
        <ul class="text-sm space-y-2">
            <li>• {{ __('admin/settings/systems/database.info_panel.notes.irreversible') }}</li>
            <li>• {{ __('admin/settings/systems/database.info_panel.notes.performance') }}</li>
            <li>• {{ __('admin/settings/systems/database.info_panel.notes.production') }}</li>
            <li>• {{ __('admin/settings/systems/database.info_panel.notes.defaults') }}</li>
        </ul>
    </x-slot>
</x-message>

@endsection

@foreach($cleanupInfo as $type => $info)
<x-modal
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

<x-modal
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
<x-modal
    id="cleanupModal{{ Str::camel($key) }}"
    title="{{ __('admin/settings/systems/database.modal.title') }}"
    message="{{ __('admin/settings/systems/database.modal.message_plugin', ['name' => $info['name'], 'plugin' => $info['plugin_name']]) }}"
    confirm-label="{{ __('common.execute') }}"
    cancel-label="{{ __('common.cancel') }}"
    icon-type="danger"
    confirm-color="red"
    form="cleanupForm{{ Str::camel($key) }}"
/>
@endforeach
@endif
