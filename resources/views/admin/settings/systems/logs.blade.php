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

    <!-- Success/Error Messages -->
    @if(session('success'))
        @include('components.message', [
            'type' => 'success',
            'message' => session('success')
        ])
    @endif

    @if(session('error'))
        @include('components.message', [
            'type' => 'error',
            'message' => session('error')
        ])
    @endif

    <!-- Log Type Selection -->
    <div class="mb-4">
        <h2>{{ __('admin.settings.systems.logs.log_type_label') }}</h2>
        
        <!-- Admin Logs -->
        <div class="mb-4">
            <h3 class="mb-2 md:mb-0 md:mr-2 md:inline-block text-center md:text-left">{{ __('admin.logs.admin_logs_label') }}</h3>
            <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
                @foreach (['activity', 'error', 'login', 'dixlase'] as $type)
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $type]) }}"
                        @class([
                            'nav-button',
                            'nav-button--blue',
                            'nav-button--active' => $logType === $type
                        ])>
                        {{ __('admin.settings.systems.logs.' . $type) }}
                    </a>
                @endforeach
            </nav>
        </div>
        
        <!-- Front Logs -->
        <div class="mb-4">
            <h3 class="mb-2 md:mb-0 md:mr-2 md:inline-block text-center md:text-left">{{ __('admin.logs.front_logs_label') }}</h3>
            <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
                @foreach (['front_activity', 'front_error'] as $type)
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $type]) }}"
                        @class([
                            'nav-button',
                            'nav-button--blue',
                            'nav-button--active' => $logType === $type
                        ])>
                        {{ __('admin.settings.systems.logs.' . $type) }}
                    </a>
                @endforeach
            </nav>
        </div>
    </div>

    
    <!-- Action Buttons -->
    <nav class="flex flex-row items-center justify-center md:justify-end gap-2 mb-4">
        <!-- Download Button -->
        <a href="{{ route('admin.settings.systems.logs.download', ['type' => $logType]) }}" class="action-button action-button--success flex-shrink-0">
            <i class="fas fa-download mr-2"></i>
            {{ __('admin.settings.systems.logs.download') }}
        </a>

        <!-- Clear Button -->
        <form method="POST" action="{{ route('admin.settings.systems.logs.clear', ['type' => $logType]) }}" 
            onsubmit="return confirm('{{ __('admin.settings.systems.logs.clear_confirm') }}')">
            @csrf
            <button type="submit" class="action-button action-button--danger flex-shrink-0">
                <i class="fas fa-trash mr-2"></i>
                {{ __('admin.settings.systems.logs.clear') }}
            </button>
        </form>
    </nav>

    <!-- Pagination Controls -->
    @include('components.pagination', [
        'pagination' => $pagination ?? null,
        'route' => 'admin.settings.systems.logs',
        'routeParams' => ['type' => $logType],
        'mobilePageRange' => 1,
        'desktopPageRange' => 2
    ])

    <!-- Log Entries -->
    <section>
        @forelse ($logs as $log)
            @if ($log['parsed'])
                <div class="border-b border-gray-200 dark:border-gray-700 p-2">
                    <div class="space-y-2 text-sm break-all">
                        <div>
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">操作:</strong>{{ $log['message'] }}
                        </div>
                        
                        @if (!empty($log['context']))
                            <dl class="mt-2">
                                @if (isset($log['context']['id']))
                                    <div class="flex flex-wrap">
                                        <dt class="font-semibold text-gray-700 dark:text-gray-300 mr-1">ID:</dt>
                                        <dd class="text-gray-600 dark:text-gray-400">{{ $log['context']['id'] }}@if(isset($log['context']['name'])), name:{{ $log['context']['name'] }}@endif</dd>
                                    </div>
                                @endif
                                
                                @if (isset($log['context']['method']))
                                    <div class="flex flex-wrap">
                                        <dt class="font-semibold text-gray-700 dark:text-gray-300 mr-1">method:</dt>
                                        <dd class="text-gray-600 dark:text-gray-400">{{ $log['context']['method'] }}</dd>
                                    </div>
                                @endif
                                
                                @if (isset($log['context']['uri']))
                                    <div class="flex flex-wrap">
                                        <dt class="font-semibold text-gray-700 dark:text-gray-300 mr-1">uri:</dt>
                                        <dd class="text-gray-600 dark:text-gray-400">{{ $log['context']['uri'] }}</dd>
                                    </div>
                                @endif
                                
                                @if (isset($log['context']['route']))
                                    <div class="flex flex-wrap">
                                        <dt class="font-semibold text-gray-700 dark:text-gray-300 mr-1">route:</dt>
                                        <dd class="text-gray-600 dark:text-gray-400">{{ $log['context']['route'] }}</dd>
                                    </div>
                                @endif
                                
                                @if (isset($log['context']['controller']))
                                    <div class="flex flex-wrap">
                                        <dt class="font-semibold text-gray-700 dark:text-gray-300 mr-1">controller:</dt>
                                        <dd class="text-gray-600 dark:text-gray-400">{{ $log['context']['controller'] }}</dd>
                                    </div>
                                @endif
                                
                                @if (isset($log['context']['ip']))
                                    <div class="flex flex-wrap">
                                        <dt class="font-semibold text-gray-700 dark:text-gray-300 mr-1">ip:</dt>
                                        <dd class="text-gray-600 dark:text-gray-400">{{ $log['context']['ip'] }}</dd>
                                    </div>
                                @endif
                                
                                @if (isset($log['context']['user_agent']))
                                    <div class="flex flex-wrap">
                                        <dt class="font-semibold text-gray-700 dark:text-gray-300 mr-1">user_agent:</dt>
                                        <dd class="text-gray-600 dark:text-gray-400">{{ $log['context']['user_agent'] }}</dd>
                                    </div>
                                @endif
                            </dl>
                        @endif
                        
                        <div>
                            <strong class="font-semibold text-gray-700 dark:text-gray-300">time:</strong>{{ $log['timestamp'] }}
                        </div>
                    </div>
                </div>
            @else
                <!-- Fallback for unparsed lines -->
                <div class="border-b border-gray-200 dark:border-gray-700 py-2 px-4">
                    <code class="font-mono text-sm break-all whitespace-normal">{{ $log['message'] }}</code>
                </div>
            @endif
        @empty
            <div class="p-4 text-gray-600 dark:text-gray-300">
                {{ __('admin.settings.systems.logs.no_logs') }}
            </div>
        @endforelse
    </section>

    <!-- Pagination Controls -->
    @include('components.pagination', [
        'pagination' => $pagination ?? null,
        'route' => 'admin.settings.systems.logs',
        'routeParams' => ['type' => $logType],
        'mobilePageRange' => 1,
        'desktopPageRange' => 2
    ])

@endsection
