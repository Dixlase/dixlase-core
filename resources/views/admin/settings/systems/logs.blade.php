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
        <div class="flex items-center mb-4">
            <h3 class="mr-2">{{ __('admin.logs.admin_logs_label') }}</h3>
            <nav>
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
        <div class="flex items-center">
            <h3 class="mr-2">{{ __('admin.logs.front_logs_label') }}</h3>
            <nav>
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
    <nav class="flex items-center justify-end mb-4">
        <!-- Download Button -->
        <a href="{{ route('admin.settings.systems.logs.download', ['type' => $logType]) }}" class="action-button action-button--success mr-2">
            <i class="fas fa-download mr-2"></i>
            {{ __('admin.settings.systems.logs.download') }}
        </a>

        <!-- Clear Button -->
        <form method="POST" action="{{ route('admin.settings.systems.logs.clear', ['type' => $logType]) }}" 
            onsubmit="return confirm('{{ __('admin.settings.systems.logs.clear_confirm') }}')">
            @csrf
            <button type="submit" class="action-button action-button--danger">
                <i class="fas fa-trash mr-2"></i>
                {{ __('admin.settings.systems.logs.clear') }}
            </button>
        </form>
    </nav>

    <!-- Pagination Controls -->
    @if(isset($pagination) && $pagination['last_page'] > 1)
        <nav aria-label="{{ __('admin.pagination.navigation') }}" class="flex justify-between items-center mb-4">
            <!-- Left Side: Previous/Next Navigation -->
            <div class="flex items-center space-x-2">
                @if($pagination['prev_page'])
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $pagination['prev_page']]) }}" 
                       class="pagination-button pagination-button--prev">
                        {{ __('admin.pagination.previous') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--prev pagination-button--disabled">
                        {{ __('admin.pagination.previous') }}
                    </span>
                @endif

                <span class="pagination-info">
                    {{ __('admin.pagination.page', ['current' => $pagination['current_page'], 'total' => $pagination['last_page']]) }}
                </span>

                @if($pagination['next_page'])
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $pagination['next_page']]) }}" 
                       class="pagination-button pagination-button--next">
                        {{ __('admin.pagination.next') }}
                    </a>
                @else
                    <span class="pagination-button pagination-button--next pagination-button--disabled">
                        {{ __('admin.pagination.next') }}
                    </span>
                @endif
            </div>

            <!-- Right Side: Page Numbers -->
            <div class="flex items-center space-x-1">
                @php
                    $start = max(1, $pagination['current_page'] - 2);
                    $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                @endphp

                @if($start > 1)
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => 1]) }}" 
                       class="pagination-number">1</a>
                    @if($start > 2)
                        <span class="pagination-ellipsis">...</span>
                    @endif
                @endif

                @for($i = $start; $i <= $end; $i++)
                    @if($i == $pagination['current_page'])
                        <span class="pagination-number pagination-number--current" aria-current="page">{{ $i }}</span>
                    @else
                        <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $i]) }}" 
                           class="pagination-number">{{ $i }}</a>
                    @endif
                @endfor

                @if($end < $pagination['last_page'])
                    @if($end < $pagination['last_page'] - 1)
                        <span class="pagination-ellipsis">...</span>
                    @endif
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $pagination['last_page']]) }}" 
                       class="pagination-number">{{ $pagination['last_page'] }}</a>
                @endif
            </div>
        </nav>
    @endif

    <!-- Log Entries -->
    <section class="w-full">
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

@endsection
