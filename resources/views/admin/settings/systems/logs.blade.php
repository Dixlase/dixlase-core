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
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded dark:bg-green-800 dark:border-green-600 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded dark:bg-red-800 dark:border-red-600 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <!-- Log Type Selection -->
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">{{ __('admin.settings.systems.logs.log_type_label') }}</label>
    <div class="mb-6">
        <!-- Admin Logs Row -->
        <div class="mb-3">
            <span class="text-sm text-gray-600 dark:text-gray-400 mr-3">{{ __('admin.logs.admin_logs_label') }}</span>
            @foreach (['activity', 'error', 'login', 'dixlase'] as $type)
                <a href="{{ route('admin.settings.systems.logs', ['type' => $type]) }}"
                    class="inline-block px-3 py-2 rounded mr-2 text-white text-sm font-medium transition-colors duration-200 {{ $logType === $type ? 'bg-blue-500 hover:bg-blue-600' : 'bg-gray-500 hover:bg-gray-600' }}
                    dark:{{ $logType === $type ? 'bg-blue-700 hover:bg-blue-800' : 'bg-gray-700 hover:bg-gray-600' }}">
                    {{ __('admin.settings.systems.logs.' . $type) }}
                </a>
            @endforeach
        </div>
        
        <!-- Front Logs Row -->
        <div class="mb-3">
            <span class="text-sm text-gray-600 dark:text-gray-400 mr-3">{{ __('admin.logs.front_logs_label') }}</span>
            @foreach (['front_activity', 'front_error'] as $type)
                <a href="{{ route('admin.settings.systems.logs', ['type' => $type]) }}"
                    class="inline-block px-3 py-2 rounded mr-2 text-white text-sm font-medium transition-colors duration-200 {{ $logType === $type ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-500 hover:bg-gray-600' }}
                    dark:{{ $logType === $type ? 'bg-green-700 hover:bg-green-800' : 'bg-gray-700 hover:bg-gray-600' }}">
                    {{ __('admin.settings.systems.logs.' . $type) }}
                </a>
            @endforeach
        </div>
    </div>

    

    <!-- Pagination Info -->
    <div class="flex justify-between items-center">
        <div>
            @if(isset($pagination) && $pagination['total'] > 0)
                <div class="text-sm">
                    {{ __('admin.settings.systems.logs.pagination.showing', ['from' => $pagination['from'], 'to' => $pagination['to'], 'total' => $pagination['total']]) }}
                </div>
            @endif
        </div>
        <div class="flex justify-end">
            <!-- Action Buttons -->
            <div class="flex items-center space-x-2">
                <!-- Download Button -->
                <a href="{{ route('admin.settings.systems.logs.download', ['type' => $logType]) }}"
                class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ __('admin.settings.systems.logs.download') }}
                </a>

                <!-- Clear Button -->
                <form method="POST" action="{{ route('admin.settings.systems.logs.clear', ['type' => $logType]) }}" 
                    onsubmit="return confirm('{{ __('admin.settings.systems.logs.clear_confirm') }}')" class="inline">
                    @csrf
                    <button type="submit" 
                            class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{ __('admin.settings.systems.logs.clear') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Pagination Controls -->
    @if(isset($pagination) && $pagination['last_page'] > 1)
        <div class="my-6 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                @if($pagination['prev_page'])
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $pagination['prev_page']]) }}" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                        {{ __('admin.pagination.previous') }}
                    </a>
                @else
                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-500">
                        {{ __('admin.pagination.previous') }}
                    </span>
                @endif

                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin.pagination.page', ['current' => $pagination['current_page'], 'total' => $pagination['last_page']]) }}
                </span>

                @if($pagination['next_page'])
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $pagination['next_page']]) }}" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                        {{ __('admin.pagination.next') }}
                    </a>
                @else
                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-500">
                        {{ __('admin.pagination.next') }}
                    </span>
                @endif
            </div>

            <!-- Page Numbers -->
            <div class="flex items-center space-x-1">
                @php
                    $start = max(1, $pagination['current_page'] - 2);
                    $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                @endphp

                @if($start > 1)
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => 1]) }}" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                        1
                    </a>
                    @if($start > 2)
                        <span class="px-2 text-gray-500">...</span>
                    @endif
                @endif

                @for($i = $start; $i <= $end; $i++)
                    @if($i == $pagination['current_page'])
                        <span class="px-3 py-2 text-sm bg-blue-500 text-white border border-blue-500 rounded-md">
                            {{ $i }}
                        </span>
                    @else
                        <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $i]) }}" 
                           class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                            {{ $i }}
                        </a>
                    @endif
                @endfor

                @if($end < $pagination['last_page'])
                    @if($end < $pagination['last_page'] - 1)
                        <span class="px-2 text-gray-500">...</span>
                    @endif
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $pagination['last_page']]) }}" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                        {{ $pagination['last_page'] }}
                    </a>
                @endif
            </div>
        </div>
    @endif


    

    <div class="w-full">
        <div class="bg-white border rounded dark:bg-gray-800 dark:border-gray-600">

        @forelse ($logs as $log)
            @if ($log['parsed'])
                <div class="border-b p-4 dark:border-gray-700 dark:hover:bg-gray-750">
                    <div class="space-y-2 text-sm break-all">
                        <div><span class="font-semibold text-gray-700 dark:text-gray-300">操作:</span>{{ $log['message'] }}</div>
                        
                        @if (!empty($log['context']))
                            @if (isset($log['context']['id']))
                                <div><span class="font-semibold text-gray-700 dark:text-gray-300">ID:</span>{{ $log['context']['id'] }}@if(isset($log['context']['name'])), name:{{ $log['context']['name'] }}@endif</div>
                            @endif
                            
                            @if (isset($log['context']['method']))
                                <div><span class="font-semibold text-gray-700 dark:text-gray-300">method:</span>{{ $log['context']['method'] }}</div>
                            @endif
                            
                            @if (isset($log['context']['uri']))
                                <div><span class="font-semibold text-gray-700 dark:text-gray-300">uri:</span>{{ $log['context']['uri'] }}</div>
                            @endif
                            
                            @if (isset($log['context']['route']))
                                <div><span class="font-semibold text-gray-700 dark:text-gray-300">route:</span>{{ $log['context']['route'] }}</div>
                            @endif
                            
                            @if (isset($log['context']['controller']))
                                <div><span class="font-semibold text-gray-700 dark:text-gray-300">controller:</span>{{ $log['context']['controller'] }}</div>
                            @endif
                            
                            @if (isset($log['context']['ip']))
                                <div><span class="font-semibold text-gray-700 dark:text-gray-300">ip:</span>{{ $log['context']['ip'] }}</div>
                            @endif
                            
                            @if (isset($log['context']['user_agent']))
                                <div><span class="font-semibold text-gray-700 dark:text-gray-300">user_agent:</span>{{ $log['context']['user_agent'] }}</div>
                            @endif
                        @endif
                        
                        <div><span class="font-semibold text-gray-700 dark:text-gray-300">time:</span>{{ $log['timestamp'] }}</div>
                    </div>
                </div>
            @else
                <!-- Fallback for unparsed lines -->
                <div class="border-b py-2 px-4 break-all whitespace-normal dark:border-gray-700">
                    <span class="font-mono text-sm">{{ $log['message'] }}</span>
                </div>
            @endif
        @empty
            <p class="p-4 dark:text-gray-300">{{ __('admin.settings.systems.logs.no_logs_found') }}</p>
        @endforelse

        </div>
    </div>

    <!-- Pagination Controls -->
    @if(isset($pagination) && $pagination['last_page'] > 1)
        <div class="my-6 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                @if($pagination['prev_page'])
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $pagination['prev_page']]) }}" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                        {{ __('admin.pagination.previous') }}
                    </a>
                @else
                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-500">
                        {{ __('admin.pagination.previous') }}
                    </span>
                @endif

                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin.pagination.page', ['current' => $pagination['current_page'], 'total' => $pagination['last_page']]) }}
                </span>

                @if($pagination['next_page'])
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $pagination['next_page']]) }}" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                        {{ __('admin.pagination.next') }}
                    </a>
                @else
                    <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-500">
                        {{ __('admin.pagination.next') }}
                    </span>
                @endif
            </div>

            <!-- Page Numbers -->
            <div class="flex items-center space-x-1">
                @php
                    $start = max(1, $pagination['current_page'] - 2);
                    $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                @endphp

                @if($start > 1)
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => 1]) }}" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                        1
                    </a>
                    @if($start > 2)
                        <span class="px-2 text-gray-500">...</span>
                    @endif
                @endif

                @for($i = $start; $i <= $end; $i++)
                    @if($i == $pagination['current_page'])
                        <span class="px-3 py-2 text-sm bg-blue-500 text-white border border-blue-500 rounded-md">
                            {{ $i }}
                        </span>
                    @else
                        <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $i]) }}" 
                           class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                            {{ $i }}
                        </a>
                    @endif
                @endfor

                @if($end < $pagination['last_page'])
                    @if($end < $pagination['last_page'] - 1)
                        <span class="px-2 text-gray-500">...</span>
                    @endif
                    <a href="{{ route('admin.settings.systems.logs', ['type' => $logType, 'page' => $pagination['last_page']]) }}" 
                       class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:hover:bg-gray-600 dark:text-gray-200">
                        {{ $pagination['last_page'] }}
                    </a>
                @endif
            </div>
        </div>
    @endif
@endsection
