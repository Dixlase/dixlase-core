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
            <h3 class="mb-2 md:mb-0 md:mr-2 md:inline-block text-center md:text-left">{{ __('common.admin_logs') }}</h3>
            <nav class="flex flex-wrap gap-2 justify-center md:justify-start">
                @foreach (['activity', 'error', 'login', 'dixlase'] as $type)
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $type]) }}"
                        @class([
                            'nav-button',
                            'nav-button--blue',
                            'nav-button--active' => $logType === $type
                        ])>
                        @if($type === 'error')
                            {{ __('common.error_log') }}
                        @elseif($type === 'login')
                            {{ __('common.login_log') }}
                        @else
                            {{ __('admin.settings.systems.logs.' . $type) }}
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>
        
        <!-- Front Logs -->
        <div class="mb-4">
            <h3 class="mb-2 md:mb-0 md:mr-2 md:inline-block text-center md:text-left">{{ __('common.front_logs') }}</h3>
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
            {{ __('common.download') }}
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

    <!-- ページネーション制御 -->
    @include('components::pagination-controls', [
        'paginator' => (object) [
            'total' => $pagination['total'] ?? 0,
            'currentPage' => $pagination['current_page'] ?? 1,
            'lastPage' => $pagination['last_page'] ?? 1,
            'perPage' => $pagination['per_page'] ?? 50
        ],
        'perPageOptions' => [25, 50, 100, 200],
        'currentPerPage' => request('per_page', 50),
        'totalLabel' => 'components.pagination.total_count',
        'perPageLabel' => 'components.pagination.per_page_label'
    ])

    <!-- Pagination Controls -->
    @include('components.pagination', [
        'pagination' => $pagination ?? null,
        'route' => 'admin.settings.systems.logs',
        'routeParams' => array_filter([
            'type' => $logType,
            'per_page' => request('per_page')
        ]),
        'mobilePageRange' => 0,
        'desktopPageRange' => 2
    ])

    <!-- Log Entries -->
    <section>
        @forelse ($logs as $log)
            @if ($log['parsed'])
                <div class="border-b border-gray-200 dark:border-gray-700 p-2">
                    <div class="text-sm space-y-2">
                        <!-- 操作 (Action) - Blue -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 border-l-4 border-blue-400">
                            <div class="flex items-start">
                                <span class="inline-block w-2 h-2 bg-blue-500 mt-2 mr-2 flex-shrink-0"></span>
                                <div class="flex-1 break-all">
                                    <span class="font-semibold text-blue-700 dark:text-blue-300">{{ __('common.operation') }}:</span>
                                    <span class="text-blue-600 dark:text-blue-200 ml-2">{{ $log['message'] }}</span>
                                </div>
                            </div>
                        </div>
                        
                        @if (!empty($log['context']))
                            <!-- ID - Green -->
                            @if (isset($log['context']['id']))
                                <div class="bg-green-50 dark:bg-green-900/20 p-3 border-l-4 border-green-400">
                                    <div class="flex items-start">
                                        <span class="inline-block w-2 h-2 bg-green-500 mt-2 mr-2 flex-shrink-0"></span>
                                        <div class="flex-1 break-all">
                                            <span class="font-semibold text-green-700 dark:text-green-300">{{ __('common.id') }}:</span>
                                            <span class="text-green-600 dark:text-green-200 ml-2">{{ $log['context']['id'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Name - Emerald -->
                            @if (isset($log['context']['name']))
                                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-3 border-l-4 border-emerald-400">
                                    <div class="flex items-start">
                                        <span class="inline-block w-2 h-2 bg-emerald-500 mt-2 mr-2 flex-shrink-0"></span>
                                        <div class="flex-1 break-all">
                                            <span class="font-semibold text-emerald-700 dark:text-emerald-300">{{ __('common.name') }}:</span>
                                            <span class="text-emerald-600 dark:text-emerald-200 ml-2">{{ $log['context']['name'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Technical Info - Purple -->
                            @if (isset($log['context']['method']) || isset($log['context']['uri']) || isset($log['context']['route']) || isset($log['context']['controller']))
                                <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg border-l-4 border-purple-400">
                                    <div class="flex items-start">
                                        <span class="inline-block w-2 h-2 bg-purple-500 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                        <div class="flex-1 break-all space-y-1">
                                            @if (isset($log['context']['method']))
                                                <div>
                                                    <span class="font-semibold text-purple-700 dark:text-purple-300">{{ __('common.method') }}:</span>
                                                    <span class="text-purple-600 dark:text-purple-200 ml-2">{{ $log['context']['method'] }}</span>
                                                </div>
                                            @endif
                                            @if (isset($log['context']['uri']))
                                                <div>
                                                    <span class="font-semibold text-purple-700 dark:text-purple-300">{{ __('common.uri') }}:</span>
                                                    <span class="text-purple-600 dark:text-purple-200 ml-2">{{ $log['context']['uri'] }}</span>
                                                </div>
                                            @endif
                                            @if (isset($log['context']['route']))
                                                <div>
                                                    <span class="font-semibold text-purple-700 dark:text-purple-300">{{ __('common.route') }}:</span>
                                                    <span class="text-purple-600 dark:text-purple-200 ml-2">{{ $log['context']['route'] }}</span>
                                                </div>
                                            @endif
                                            @if (isset($log['context']['controller']))
                                                <div>
                                                    <span class="font-semibold text-purple-700 dark:text-purple-300">{{ __('common.controller') }}:</span>
                                                    <span class="text-purple-600 dark:text-purple-200 ml-2">{{ $log['context']['controller'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- IP Address - Orange -->
                            @if (isset($log['context']['ip']))
                                <div class="bg-orange-50 dark:bg-orange-900/20 p-3 border-l-4 border-orange-400">
                                    <div class="flex items-start">
                                        <span class="inline-block w-2 h-2 bg-orange-500 mt-2 mr-2 flex-shrink-0"></span>
                                        <div class="flex-1 break-all">
                                            <span class="font-semibold text-orange-700 dark:text-orange-300">{{ __('common.ip') }}:</span>
                                            <span class="text-orange-600 dark:text-orange-200 ml-2">{{ $log['context']['ip'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- User Agent - Indigo -->
                            @if (isset($log['context']['user_agent']))
                                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 border-l-4 border-indigo-400">
                                    <div class="flex items-start">
                                        <span class="inline-block w-2 h-2 bg-indigo-500 mt-2 mr-2 flex-shrink-0"></span>
                                        <div class="flex-1 break-all">
                                            <span class="font-semibold text-indigo-700 dark:text-indigo-300">{{ __('common.user_agent') }}:</span>
                                            <span class="text-indigo-600 dark:text-indigo-200 ml-2">{{ $log['context']['user_agent'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                        
                        <!-- Timestamp - Gray -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-3 border-l-4 border-gray-400">
                            <div class="flex items-start">
                                <span class="inline-block w-2 h-2 bg-gray-500 mt-2 mr-2 flex-shrink-0"></span>
                                <div class="flex-1 break-all">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ __('common.time') }}:</span>
                                    <span class="text-gray-600 dark:text-gray-400 ml-2">{{ $log['timestamp'] }}</span>
                                </div>
                            </div>
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

    <!-- ページネーション制御 -->
    @include('components::pagination-controls', [
        'paginator' => (object) [
            'total' => $pagination['total'] ?? 0,
            'currentPage' => $pagination['current_page'] ?? 1,
            'lastPage' => $pagination['last_page'] ?? 1,
            'perPage' => $pagination['per_page'] ?? 50
        ],
        'perPageOptions' => [25, 50, 100, 200],
        'currentPerPage' => request('per_page', 50),
        'totalLabel' => 'components.pagination.total_count',
        'perPageLabel' => 'components.pagination.per_page_label'
    ])

    <!-- Pagination Controls -->
    @include('components.pagination', [
        'pagination' => $pagination ?? null,
        'route' => 'admin.settings.systems.logs',
        'routeParams' => array_filter([
            'type' => $logType,
            'per_page' => request('per_page')
        ]),
        'mobilePageRange' => 0,
        'desktopPageRange' => 2
    ])

@endsection
