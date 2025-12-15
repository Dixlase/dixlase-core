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
        <x-message
            type="success"
            :message="session('success')"
        />
    @endif

    @if(session('error'))
        <x-message
            type="error"
            :message="session('error')"
        />
    @endif

    @include('admin::settings.systems.partials.logs-navigation', ['logType' => $logType, 'pageType' => 'system'])

    
    <!-- Action Buttons -->
    <nav class="flex flex-row items-center justify-center md:justify-end gap-2 mb-4">
        <!-- Download Button -->
        <a href="{{ route('admin.settings.systems.logs.download', ['type' => $logType]) }}" class="action-button action-button--success flex-shrink-0">
            <i class="fas fa-download mr-2"></i>
            {{ __('common.download') }}
        </a>

        <!-- Clear Button -->
        <form method="POST" action="{{ route('admin.settings.systems.logs.clear', ['type' => $logType]) }}" 
            onsubmit="return confirm('{{ __('admin/settings/systems/logs/files.clear_confirm') }}')">
            @csrf
            <button type="submit" class="action-button action-button--danger flex-shrink-0">
                <i class="fas fa-trash mr-2"></i>
                {{ __('common.clear') }}
            </button>
        </form>
    </nav>

    <!-- ページネーション制御 -->
    <x-pagination-controls
        :paginator="(object) [
            'total' => $pagination['total'] ?? 0,
            'currentPage' => $pagination['current_page'] ?? 1,
            'lastPage' => $pagination['last_page'] ?? 1,
            'perPage' => $pagination['per_page'] ?? 50
        ]"
        :perPageOptions="[25, 50, 100, 200]"
        :currentPerPage="request('per_page', 50)"
        totalLabel="components.pagination.total_count"
        perPageLabel="components.pagination.per_page_label"
    />

    <!-- Pagination Controls -->
    <x-pagination
        :pagination="$pagination ?? null"
        route="admin.settings.systems.logs.files"
        :routeParams="array_filter([
            'type' => $logType,
            'per_page' => request('per_page')
        ])"
        :mobilePageRange="0"
        :desktopPageRange="2"
    />

    <!-- Log Entries -->
    <section>
        @forelse ($logs as $log)
            @if ($log['parsed'])
                @php
                    // ログレベルに応じた色を設定
                    $level = strtolower($log['level'] ?? '');
                    $levelColors = match(true) {
                        str_contains($level, 'error'), str_contains($level, 'critical'), str_contains($level, 'alert'), str_contains($level, 'emergency') => [
                            'bg' => 'bg-red-50 dark:bg-red-900/20',
                            'border' => 'border-l-red-400',
                            'dot' => 'bg-red-500',
                            'label' => 'text-red-700 dark:text-red-300',
                            'text' => 'text-red-600 dark:text-red-200',
                        ],
                        str_contains($level, 'warning'), str_contains($level, 'notice') => [
                            'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
                            'border' => 'border-l-yellow-400',
                            'dot' => 'bg-yellow-500',
                            'label' => 'text-yellow-700 dark:text-yellow-300',
                            'text' => 'text-yellow-600 dark:text-yellow-200',
                        ],
                        str_contains($level, 'debug') => [
                            'bg' => 'bg-gray-50 dark:bg-gray-800/50',
                            'border' => 'border-l-gray-400',
                            'dot' => 'bg-gray-500',
                            'label' => 'text-gray-700 dark:text-gray-300',
                            'text' => 'text-gray-600 dark:text-gray-400',
                        ],
                        default => [
                            'bg' => 'bg-blue-50 dark:bg-blue-900/20',
                            'border' => 'border-l-blue-400',
                            'dot' => 'bg-blue-500',
                            'label' => 'text-blue-700 dark:text-blue-300',
                            'text' => 'text-blue-600 dark:text-blue-200',
                        ],
                    };
                @endphp
                <div class="border-b px-2 py-4 border-b-gray-600 border-l-4 {{ $levelColors['border'] }}">
                    <div class="text-sm space-y-2">
                        <!-- 操作 (Action) - レベルに応じた色 -->
                        <div class="flex items-start">
                            <div class="flex-1 break-all">
                                <span class="font-semibold {{ $levelColors['label'] }}">{{ __('common.operation') }}:</span>
                                <span class="{{ $levelColors['text'] }} ml-2">{{ $log['message'] }}</span>
                            </div>
                        </div>
                        
                        @if (!empty($log['context']))
                            <!-- ID - Green -->
                            @if (isset($log['context']['id']))
                                <div class="flex-1 break-all">
                                    <span class="font-semibold">{{ __('common.id') }}:</span>
                                    <span>{{ $log['context']['id'] }}</span>
                                </div>
                            @endif
                            
                            <!-- Name -->
                            @if (isset($log['context']['name']))
                                <div class="flex-1 break-all">
                                    <span class="font-semibold">{{ __('common.name') }}:</span>
                                    <span class="ml-2">{{ $log['context']['name'] }}</span>
                                </div>
                            @endif
                            
                            <!-- Technical Info - Purple -->
                            @if (isset($log['context']['method']) || isset($log['context']['uri']) || isset($log['context']['route']) || isset($log['context']['controller']))
                                <div class="flex items-start">
                                    <div class="flex-1 break-all space-y-1">
                                        @if (isset($log['context']['method']))
                                            <div>
                                                <span class="font-semibold">{{ __('common.method') }}:</span>
                                                <span class="ml-2">{{ $log['context']['method'] }}</span>
                                            </div>
                                        @endif
                                        @if (isset($log['context']['uri']))
                                            <div>
                                                <span class="font-semibold">{{ __('common.uri') }}:</span>
                                                <span class="ml-2">{{ $log['context']['uri'] }}</span>
                                            </div>
                                        @endif
                                        @if (isset($log['context']['route']))
                                            <div>
                                                <span class="font-semibold">{{ __('common.route') }}:</span>
                                                <span class="ml-2">{{ $log['context']['route'] }}</span>
                                            </div>
                                        @endif
                                        @if (isset($log['context']['controller']))
                                            <div>
                                                <span class="font-semibold">{{ __('common.controller') }}:</span>
                                                <span class="ml-2">{{ $log['context']['controller'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            
                            <!-- IP Address -->
                            @if (isset($log['context']['ip']))
                                <div class="flex items-start">
                                    <div class="flex-1 break-all">
                                        <span class="font-semibold">{{ __('common.ip') }}:</span>
                                        <span class="ml-2">{{ $log['context']['ip'] }}</span>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- User Agent - Indigo -->
                            @if (isset($log['context']['user_agent']))
                                
                                <div class="flex items-start">
                                    <div class="flex-1 break-all">
                                        <span class="font-semibold">{{ __('common.user_agent') }}:</span>
                                        <span class="ml-2">{{ $log['context']['user_agent'] }}</span>
                                    </div>
                                </div>

                            @endif
                        @endif
                        
                        <!-- Timestamp - Gray -->
                        <div class="flex items-start">
                            <div class="flex-1 break-all">
                                <span class="font-semibold">{{ __('common.time') }}:</span>
                                <span class="ml-2">{{ $log['timestamp'] }}</span>
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
                {{ __('admin.systems.logs.system.no_logs') }}
            </div>
        @endforelse
    </section>

    <!-- ページネーション制御 -->
    <x-pagination-controls
        :paginator="(object) [
            'total' => $pagination['total'] ?? 0,
            'currentPage' => $pagination['current_page'] ?? 1,
            'lastPage' => $pagination['last_page'] ?? 1,
            'perPage' => $pagination['per_page'] ?? 50
        ]"
        :perPageOptions="[25, 50, 100, 200]"
        :currentPerPage="request('per_page', 50)"
        totalLabel="components.pagination.total_count"
        perPageLabel="components.pagination.per_page_label"
    />

    <!-- Pagination Controls -->
    <x-pagination
        :pagination="$pagination ?? null"
        route="admin.settings.systems.logs.files"
        :routeParams="array_filter([
            'type' => $logType,
            'per_page' => request('per_page')
        ])"
        :mobilePageRange="0"
        :desktopPageRange="2"
    />

@endsection
