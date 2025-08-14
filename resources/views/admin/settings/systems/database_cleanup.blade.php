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
    <div class="mb-8">
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ __('admin.settings.systems.database_cleanup.description') }}</p>
    </div>

    <!-- Individual Database Cleanup Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        @foreach($cleanupInfo as $type => $info)
        <div class="bg-white dark:bg-gray-800 shadow rounded-2xl">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">{{ $info['name'] }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ $info['description'] }}</p>
                        <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full inline-block mb-3">
                            <code>php artisan {{ $info['command'] }}</code>
                        </div>
                        @if($info['default_days'])
                        <div class="text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-1 rounded-full inline-block">
                            {{ __('admin.settings.systems.database_cleanup.' . $type . '.default_days') }}
                        </div>
                        @else
                        <div class="text-xs text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-3 py-1 rounded-full inline-block">
                            {{ __('admin.settings.systems.database_cleanup.' . $type . '.default_days') }}
                        </div>
                        @endif
                    </div>
                </div>
                <div class="mt-6">
                    <form id="cleanupForm{{ ucfirst($type) }}" action="{{ route('admin.settings.systems.database_cleanup.clean') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        @if($info['default_days'])
                        <div>
                            <label for="days_{{ $type }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('admin.settings.systems.database_cleanup.days_label') }}
                            </label>
                            <input type="number" 
                                   id="days_{{ $type }}" 
                                   name="days" 
                                   value="{{ $info['default_days'] }}" 
                                   min="1" 
                                   max="365"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        @endif
                    </form>
                    <button type="button" 
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 mt-4"
                            onclick="confirmCleanup('{{ $type }}', '{{ $info['name'] }}')">
                        {{ __('admin.settings.systems.database_cleanup.cleanup_button') }}
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Clean All Button -->
    <div class="bg-gradient-to-r from-red-500 to-red-600 shadow rounded-2xl">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-2">{{ __('admin.settings.systems.database_cleanup.all_cleanup_button') }}</h3>
                    <p class="text-red-100 text-sm">{{ __('admin.settings.systems.database_cleanup.all_cleanup_description') }}</p>
                </div>
                <form id="cleanupAllForm" action="{{ route('admin.settings.systems.database_cleanup.clean') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="type" value="all">
                </form>
                <button type="button" 
                        class="bg-white text-red-600 hover:bg-red-50 font-medium py-2 px-6 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-red-600"
                        onclick="confirmCleanup('all', translations.allTables)">
                    {{ __('admin.settings.systems.database_cleanup.all_cleanup_button') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Information Panel -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl mt-6">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-3">
                <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                {{ __('admin.settings.systems.database_cleanup.info_panel.title') }}
            </h3>
            <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                <li>• {{ __('admin.settings.systems.database_cleanup.info_panel.notes.irreversible') }}</li>
                <li>• {{ __('admin.settings.systems.database_cleanup.info_panel.notes.performance') }}</li>
                <li>• {{ __('admin.settings.systems.database_cleanup.info_panel.notes.production') }}</li>
                <li>• {{ __('admin.settings.systems.database_cleanup.info_panel.notes.defaults') }}</li>
            </ul>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mt-4">{{ __('admin.settings.systems.database_cleanup.modal.title') }}</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 dark:text-gray-300" id="confirmationMessage">
                    {{ __('admin.settings.systems.database_cleanup.modal.message') }}
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmButton" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                    {{ __('admin.settings.systems.database_cleanup.modal.execute') }}
                </button>
                <button id="cancelButton" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white text-base font-medium rounded-md w-24 hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    {{ __('admin.settings.systems.database_cleanup.modal.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentFormId = '';

// Translation strings for JavaScript
const translations = {
    confirmMessage: @json(__('admin.settings.systems.database_cleanup.modal.confirm_message', ['name' => ':name'])),
    allTables: @json(__('admin.settings.systems.database_cleanup.all_tables'))
};

function confirmCleanup(type, name) {
    const modal = document.getElementById('confirmationModal');
    const message = document.getElementById('confirmationMessage');
    const confirmButton = document.getElementById('confirmButton');
    
    message.textContent = translations.confirmMessage.replace(':name', name);
    currentFormId = type === 'all' ? 'cleanupAllForm' : `cleanupForm${type.charAt(0).toUpperCase() + type.slice(1)}`;
    
    modal.classList.remove('hidden');
    
    confirmButton.onclick = function() {
        document.getElementById(currentFormId).submit();
    };
}

document.getElementById('cancelButton').onclick = function() {
    document.getElementById('confirmationModal').classList.add('hidden');
};

// Close modal when clicking outside
document.getElementById('confirmationModal').onclick = function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
};
</script>
@endsection
