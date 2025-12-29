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

    @include('admin::settings.systems.logs.partials.navigation', ['logType' => 'audit', 'currentView' => 'db', 'pageType' => 'audit'])

    @if(!$tableExists)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
        <div class="flex">
            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    {{ __('admin/settings/systems/logs/index.table_not_exists') }}
                </p>
            </div>
        </div>
    </div>
    @else

    <!-- Action Buttons -->
    <nav class="flex flex-row items-center justify-center md:justify-end gap-2 mb-4">
        @if($auditLogs->count() > 0)
        <!-- Export Button -->
        <a href="{{ route('admin.settings.systems.logs.audit.export', request()->query()) }}" 
           class="action-button action-button--success flex-shrink-0">
            <i class="fas fa-download mr-2"></i>
            {{ __('admin/settings/systems/logs/index.export_csv') }}
        </a>
        @endif
    </nav>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6" x-data="{ open: {{ request()->hasAny(['category', 'action', 'severity', 'outcome', 'ip_address', 'date_from', 'date_to', 'search']) ? 'true' : 'false' }} }">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <button type="button" 
                    @click="open = !open"
                    class="flex items-center justify-between w-full text-left">
                <span class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ __('admin/settings/systems/logs/index.filters') }}
                </span>
                <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>
        <div x-show="open" x-collapse>
            <form method="GET" action="{{ route('admin.settings.systems.logs.index') }}" class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- 検索 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('admin/settings/systems/logs/index.search') }}
                        </label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                               placeholder="{{ __('admin/settings/systems/logs/index.search_placeholder') }}">
                    </div>

                    {{-- カテゴリ --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('admin/settings/systems/logs/index.category') }}
                        </label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                {{ __('admin/settings/systems/logs/index.categories.' . $category, [], $category) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- アクション --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('admin/settings/systems/logs/index.action') }}
                        </label>
                        <select name="action" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ $action }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 重要度 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('admin/settings/systems/logs/index.severity') }}
                        </label>
                        <select name="severity" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach($severities as $severity)
                            <option value="{{ $severity }}" {{ request('severity') == $severity ? 'selected' : '' }}>
                                {{ __('admin/settings/systems/logs/index.severities.' . $severity) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 結果 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('admin/settings/systems/logs/index.outcome') }}
                        </label>
                        <select name="outcome" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach($outcomes as $outcome)
                            <option value="{{ $outcome }}" {{ request('outcome') == $outcome ? 'selected' : '' }}>
                                {{ __('admin/settings/systems/logs/index.outcomes.' . $outcome) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- IPアドレス --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('admin/settings/systems/logs/index.ip_address') }}
                        </label>
                        <input type="text" name="ip_address" value="{{ request('ip_address') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                               placeholder="192.168.1.1">
                    </div>

                    {{-- 開始日 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('admin/settings/systems/logs/index.date_from') }}
                        </label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    {{-- 終了日 --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('admin/settings/systems/logs/index.date_to') }}
                        </label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="mt-4 flex justify-end space-x-2">
                    <a href="{{ route('admin.settings.systems.logs.index') }}" 
                       class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        {{ __('common.reset') }}
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                        {{ __('common.search') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pagination Controls -->
    <x-pagination-controls
        :paginator="$auditLogs"
        :perPageOptions="[25, 50, 100, 200]"
        :currentPerPage="request('per_page', 50)"
        totalLabel="components.pagination.total_count"
        perPageLabel="components.pagination.per_page_label"
    />

    <x-pagination
        :pagination="[
            'current_page' => $auditLogs->currentPage(),
            'last_page' => $auditLogs->lastPage(),
            'per_page' => $auditLogs->perPage(),
            'total' => $auditLogs->total(),
            'from' => $auditLogs->firstItem(),
            'to' => $auditLogs->lastItem(),
            'has_more_pages' => $auditLogs->hasMorePages(),
            'prev_page' => $auditLogs->currentPage() > 1 ? $auditLogs->currentPage() - 1 : null,
            'next_page' => $auditLogs->hasMorePages() ? $auditLogs->currentPage() + 1 : null,
        ]"
        route="admin.settings.systems.logs"
        :routeParams="request()->except(['page'])"
        :mobilePageRange="0"
        :desktopPageRange="2"
    />

    <!-- Log Entries -->
    <section class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        @forelse ($auditLogs as $log)
            <a href="{{ route('admin.settings.systems.logs.audit.show', $log->id) }}" 
               class="block border-b border-gray-200 dark:border-gray-700 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            {{-- カテゴリバッジ --}}
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ __('admin/settings/systems/logs/index.categories.' . $log->category, [], $log->category) }}
                            </span>
                            {{-- アクション --}}
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $log->action }}
                            </span>
                            {{-- 重要度バッジ --}}
                            @php
                                $severityColors = [
                                    'debug' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'notice' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
                                    'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'error' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'critical' => 'bg-red-200 text-red-900 dark:bg-red-800 dark:text-red-100',
                                    'alert' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                    'emergency' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $severityColors[$log->severity] ?? $severityColors['info'] }}">
                                {{ __('admin/settings/systems/logs/index.severities.' . $log->severity) }}
                            </span>
                            {{-- 結果バッジ --}}
                            @php
                                $outcomeColors = [
                                    'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'failure' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'denied' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'unknown' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $outcomeColors[$log->outcome] ?? $outcomeColors['unknown'] }}">
                                {{ __('admin/settings/systems/logs/index.outcomes.' . $log->outcome) }}
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            @if($log->actor_name)
                                <span class="font-medium">{{ $log->actor_name }}</span>
                            @else
                                <span class="italic">{{ __('admin/settings/systems/logs/index.system') }}</span>
                            @endif
                            @if($log->target_label)
                                → {{ $log->target_label }}
                            @endif
                        </div>
                        @if($log->message)
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-500 truncate">
                            {{ Str::limit($log->message, 100) }}
                        </div>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $log->occurred_at?->format('Y-m-d H:i:s') }}</span>
                        @if($log->ip_address)
                        <span class="font-mono">{{ $log->ip_address }}</span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                {{ __('admin/settings/systems/logs/index.no_logs') }}
            </div>
        @endforelse
    </section>

    <!-- Pagination Controls -->
    <x-pagination-controls
        :paginator="$auditLogs"
        :perPageOptions="[25, 50, 100, 200]"
        :currentPerPage="request('per_page', 50)"
        totalLabel="components.pagination.total_count"
        perPageLabel="components.pagination.per_page_label"
    />

    <x-pagination
        :pagination="[
            'current_page' => $auditLogs->currentPage(),
            'last_page' => $auditLogs->lastPage(),
            'per_page' => $auditLogs->perPage(),
            'total' => $auditLogs->total(),
            'from' => $auditLogs->firstItem(),
            'to' => $auditLogs->lastItem(),
            'has_more_pages' => $auditLogs->hasMorePages(),
            'prev_page' => $auditLogs->currentPage() > 1 ? $auditLogs->currentPage() - 1 : null,
            'next_page' => $auditLogs->hasMorePages() ? $auditLogs->currentPage() + 1 : null,
        ]"
        route="admin.settings.systems.logs"
        :routeParams="request()->except(['page'])"
        :mobilePageRange="0"
        :desktopPageRange="2"
    />

    <!-- Cleanup Section -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ __('admin/settings/systems/logs/index.cleanup_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ __('admin/settings/systems/logs/index.cleanup_description') }}
        </p>
        <form method="POST" action="{{ route('admin.settings.systems.logs.audit.cleanup') }}" 
              onsubmit="return confirm('{{ __('admin/settings/systems/logs/index.cleanup_confirm') }}')">
            @csrf
            <div class="flex items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('admin/settings/systems/logs/index.cleanup_days') }}
                    </label>
                    <input type="number" name="days" value="90" min="0" 
                           class="w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="pt-6">
                    <button type="submit" class="action-button action-button--danger">
                        <i class="fas fa-trash mr-2"></i>
                        {{ __('admin/settings/systems/logs/index.cleanup_button') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    @endif

@endsection
