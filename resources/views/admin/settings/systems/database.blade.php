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

<section>
    <h2>{{ __('admin.settings.systems.database.heading') }}</h2>
    
    @foreach($cleanupInfo as $type => $info)
        <section class="flex flex-col md:flex-row md:items-center justify-center md:justify-between">
            <div class="flex-1 mb-4 md:mb-0 md:mr-6">
                <h3 class="text-center md:text-left">{{ $info['name'] }}</h3>
                <p class="mb-4">{{ $info['description'] }}</p>
                <code class="text-sm rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1">php artisan {{ $info['command'] }}</code>
                
                @if($info['default_days'])
                <div class="mt-2">
                    <span class="text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-1 rounded-full inline-block">
                        {{ __('admin.settings.systems.database.' . $type . '.default_days') }}
                    </span>
                </div>
                @else
                <div class="mt-2">
                    <span class="text-xs text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-full inline-block">
                        {{ __('admin.settings.systems.database.' . $type . '.default_days') }}
                    </span>
                </div>
                @endif
                
                @if($info['default_days'])
                <div class="mt-4">
                    <form id="cleanupForm{{ ucfirst($type) }}" action="{{ route('admin.settings.systems.database.clean') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <label for="days_{{ $type }}" class="block text-sm font-medium mb-1">
                            {{ __('admin.settings.systems.database.days_label') }}
                        </label>
                        <input type="number" 
                            id="days_{{ $type }}" 
                            name="days" 
                            value="{{ $info['default_days'] }}" 
                            min="1" 
                            max="365"
                            class="number-input-small">
                    </form>
                </div>
                @else
                <form id="cleanupForm{{ ucfirst($type) }}" action="{{ route('admin.settings.systems.database.clean') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                </form>
                @endif
            </div>
            
            <div class="flex justify-center md:justify-end flex-shrink-0">
                @include('components::form.button', [
                    'type' => 'button',
                    'label' => __('admin.settings.systems.database.cleanup_button'),
                    'variant' => 'danger',
                    'icon' => 'fas fa-database',
                    'onclick' => "openModal('cleanupModal" . ucfirst($type) . "')"
                ])
            </div>
        </section>
    @endforeach

    <section class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div class="flex-1 mb-4 md:mb-0 md:mr-6">
            <h2>{{ __('admin.settings.systems.database.all_cleanup_button') }}</h2>
            <p>{{ __('admin.settings.systems.database.all_cleanup_description') }}</p>
            <p>{{ __('admin.settings.systems.database.warning') }} {{ __('admin.settings.systems.database.all_cleanup_warning') }}</p>
        </div>
        
        <div class="flex-shrink-0">
            <form id="cleanupAllForm" action="{{ route('admin.settings.systems.database.clean') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="all">
            </form>
            
            @include('components::form.button', [
                'type' => 'button',
                'label' => __('admin.settings.systems.database.all_cleanup_button'),
                'variant' => 'danger',
                'icon' => 'fas fa-trash-alt',
                'onclick' => "openModal('cleanupAllModal')"
            ])
        </div>
    </section>

    <section class="info-section">
        <div>
            <h2>{{ __('admin.settings.systems.database.info_title') }}</h2>
            <dl class="text-sm">
                <dt class="font-semibold">{{ __('admin.settings.systems.database.login_attempts.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin.settings.systems.database.info_login_attempts') }}</dd>
                
                <dt class="font-semibold">{{ __('admin.settings.systems.database.password_reset_tokens.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin.settings.systems.database.info_password_reset') }}</dd>
                
                <dt class="font-semibold">{{ __('admin.settings.systems.database.trusted_devices.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin.settings.systems.database.info_trusted_devices') }}</dd>
                
                <dt class="font-semibold">{{ __('admin.settings.systems.database.two_factor_tokens.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin.settings.systems.database.info_two_factor') }}</dd>
                
                <dt class="font-semibold">{{ __('admin.settings.systems.database.cache_data.name') }}</dt>
                <dd class="font-normal mb-2">{{ __('admin.settings.systems.database.info_cache') }}</dd>
                
                <dt class="font-semibold">{{ __('admin.settings.systems.database.sessions.name') }}</dt>
                <dd class="font-normal">{{ __('admin.settings.systems.database.info_sessions') }}</dd>
            </dl>
        </div>
    </section>
</section>

@include('components::message', [
    'type' => 'info',
    'message' => '
        <h3 class="text-lg font-semibold mb-3">' . __('admin.settings.systems.database.info_panel.title') . '</h3>
        <ul class="text-sm space-y-2">
            <li>• ' . __('admin.settings.systems.database.info_panel.notes.irreversible') . '</li>
            <li>• ' . __('admin.settings.systems.database.info_panel.notes.performance') . '</li>
            <li>• ' . __('admin.settings.systems.database.info_panel.notes.production') . '</li>
            <li>• ' . __('admin.settings.systems.database.info_panel.notes.defaults') . '</li>
        </ul>
    '
])

@endsection

@foreach($cleanupInfo as $type => $info)
@include('components.modal', [
    'id' => 'cleanupModal' . ucfirst($type),
    'title' => __('admin.settings.systems.database.modal.title'),
    'message' => __('admin.settings.systems.database.modal.message_single', ['name' => $info['name']]),
    'confirm_label' => __('common.execute'),
    'cancel_label' => __('common.cancel'),
    'icon_type' => 'danger',
    'confirm_color' => 'red',
    'form' => 'cleanupForm' . ucfirst($type)
])
@endforeach

@include('components.modal', [
    'id' => 'cleanupAllModal',
    'title' => __('admin.settings.systems.database.modal.title'),
    'message' => __('admin.settings.systems.database.modal.message_all'),
    'confirm_label' => __('common.execute'),
    'cancel_label' => __('common.cancel'),
    'icon_type' => 'danger',
    'confirm_color' => 'red',
    'form' => 'cleanupAllForm'
])
