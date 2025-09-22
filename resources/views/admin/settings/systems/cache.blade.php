{{--
This file is part of MySoftware.

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

@extends('admin::partials.layout')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    
    <!-- Page Header -->
    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 mb-2">{{ __('admin.settings.systems.cache.description') }}</p>


    <!-- Individual Cache Clear Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        @foreach($cacheInfo as $type => $info)
        <div class="bg-white dark:bg-gray-800 shadow rounded-2xl">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">{{ $info['name'] }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ $info['description'] }}</p>
                        <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full inline-block">
                            <code>php artisan {{ $info['command'] }}</code>
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <form id="clearCacheForm{{ ucfirst($type) }}" action="{{ route('admin.settings.systems.cache.clear') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                    </form>
                    <button type="button" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            onclick="openModal('clearCacheModal{{ ucfirst($type) }}')">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{ __('admin.settings.systems.cache.clear_button') }}
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Clear All Cache -->
    <div class="bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border border-red-200 dark:border-red-800 rounded-2xl">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
                        <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        {{ __('admin.settings.systems.cache.clear_all_title') }}
                    </h3>
                    <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                        {{ __('admin.settings.systems.cache.clear_all_description') }}<br>
                        <strong>{{ __('admin.settings.systems.cache.warning') }}</strong> {{ __('admin.settings.systems.cache.clear_all_warning') }}
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <form id="clearAllCacheForm" action="{{ route('admin.settings.systems.cache.clear') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="type" value="all">
                </form>
                <button type="button" 
                        class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                        onclick="openModal('clearAllCacheModal')">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ __('admin.settings.systems.cache.clear_all_button') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Cache Information -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl mt-6">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-3">
                <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                {{ __('admin.settings.systems.cache.info_title') }}
            </h3>
            <div class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                <p><strong>{{ __('admin.settings.systems.cache.config_cache.name') }}：</strong> {{ __('admin.settings.systems.cache.info_config') }}</p>
                <p><strong>{{ __('admin.settings.systems.cache.route_cache.name') }}：</strong> {{ __('admin.settings.systems.cache.info_route') }}</p>
                <p><strong>{{ __('admin.settings.systems.cache.view_cache.name') }}：</strong> {{ __('admin.settings.systems.cache.info_view') }}</p>
                <p><strong>{{ __('admin.settings.systems.cache.application_cache.name') }}：</strong> {{ __('admin.settings.systems.cache.info_application') }}</p>
            </div>
        </div>
    </div>

</div>

<!-- Individual Cache Clear Modals -->
@foreach($cacheInfo as $type => $info)
@include('components::form.modal', [
    'id' => 'clearCacheModal' . ucfirst($type),
    'title' => __('admin.settings.systems.cache.clear_confirm', ['name' => $info['name']]),
    'message' => $info['description'],
    'confirm_label' => __('admin.settings.systems.cache.clear_button'),
    'cancel_label' => __('admin.common.cancel'),
    'form' => 'clearCacheForm' . ucfirst($type),
    'icon_type' => 'info',
    'confirm_color' => 'blue'
])
@endforeach

<!-- Clear All Cache Modal -->
@include('components::form.modal', [
    'id' => 'clearAllCacheModal',
    'title' => __('admin.settings.systems.cache.clear_all_title'),
    'message' => __('admin.settings.systems.cache.clear_all_description') . ' ' . __('admin.settings.systems.cache.clear_all_warning'),
    'confirm_label' => __('admin.settings.systems.cache.clear_all_button'),
    'cancel_label' => __('admin.common.cancel'),
    'form' => 'clearAllCacheForm',
    'icon_type' => 'warning',
    'confirm_color' => 'yellow'
])

@endsection
