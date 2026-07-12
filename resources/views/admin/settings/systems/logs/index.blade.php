{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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

@php
    // See cache.blade.php for the CheckMenuAccess contract.
    $viewOnly = ! ($menuEditable ?? true);
    $tooltipText = $viewOnly ? __('common.view_only_action_disabled') : '';
@endphp

@section('content')

    <!-- Success/Error Messages -->
    @if(session('success'))
        <x-ui-message
            type="success"
            :message="session('success')"
        />
    @endif

    @if(session('error'))
        <x-ui-message
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
            <x-form-button
                type="link"
                variant="success"
                :href="route('admin.settings.systems.logs.audit.export', request()->query())"
                :label="__('admin/settings/systems/logs/index.export_csv')"
                icon="fas fa-download"
                class="flex-shrink-0"
            />
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
                    {{-- Search --}}
                    <div>
                        <x-form-label
                            for="search"
                            :text="__('admin/settings/systems/logs/index.search')"
                            class="mb-1"
                        />
                        <x-form-text
                            type="text"
                            name="search"
                            id="search"
                            :value="request('search')"
                            :placeholder="__('admin/settings/systems/logs/index.search_placeholder')"
                            class="!w-full"
                        />
                    </div>

                    {{-- Category --}}
                    <div>
                        <x-form-label
                            for="category"
                            :text="__('admin/settings/systems/logs/index.category')"
                            class="mb-1"
                        />
                        <x-form-select
                            name="category"
                            id="category"
                            :options="array_merge(['' => 'common.all'], array_combine($categories, array_map(fn($cat) => 'admin/settings/systems/logs/index.categories.' . $cat, $categories)))"
                            :value="request('category')"
                            class="!w-full"
                        />
                    </div>

                    {{-- Action --}}
                    <div>
                        <x-form-label
                            for="action"
                            :text="__('admin/settings/systems/logs/index.action')"
                            class="mb-1"
                        />
                        <select name="action" id="action" class="input-common block w-full max-w-full p-2 pr-10 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:text-white">
                            <option value="">{{ __('common.all') }}</option>
                            @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ $action }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Severity --}}
                    <div>
                        <x-form-label
                            for="severity"
                            :text="__('admin/settings/systems/logs/index.severity')"
                            class="mb-1"
                        />
                        <x-form-select
                            name="severity"
                            id="severity"
                            :options="array_merge(['' => 'common.all'], array_combine($severities, array_map(fn($sev) => 'admin/settings/systems/logs/index.severities.' . $sev, $severities)))"
                            :value="request('severity')"
                            class="!w-full"
                        />
                    </div>

                    {{-- Result --}}
                    <div>
                        <x-form-label
                            for="outcome"
                            :text="__('admin/settings/systems/logs/index.outcome')"
                            class="mb-1"
                        />
                        <x-form-select
                            name="outcome"
                            id="outcome"
                            :options="array_merge(['' => 'common.all'], array_combine($outcomes, array_map(fn($out) => 'admin/settings/systems/logs/index.outcomes.' . $out, $outcomes)))"
                            :value="request('outcome')"
                            class="!w-full"
                        />
                    </div>

                    {{-- IP Address --}}
                    <div>
                        <x-form-label
                            for="ip_address"
                            :text="__('admin/settings/systems/logs/index.ip_address')"
                            class="mb-1"
                        />
                        <x-form-text
                            type="text"
                            name="ip_address"
                            id="ip_address"
                            :value="request('ip_address')"
                            placeholder="192.168.1.1"
                            class="!w-full"
                        />
                    </div>

                    {{-- Start Date --}}
                    <div>
                        <x-form-label
                            for="date_from"
                            :text="__('admin/settings/systems/logs/index.date_from')"
                            class="mb-1"
                        />
                        <x-form-text
                            type="date"
                            name="date_from"
                            id="date_from"
                            :value="request('date_from')"
                            class="!w-full"
                        />
                    </div>

                    {{-- End Date --}}
                    <div>
                        <x-form-label
                            for="date_to"
                            :text="__('admin/settings/systems/logs/index.date_to')"
                            class="mb-1"
                        />
                        <x-form-text
                            type="date"
                            name="date_to"
                            id="date_to"
                            :value="request('date_to')"
                            class="!w-full"
                        />
                    </div>
                </div>

                <div class="mt-4 flex justify-end space-x-2">
                    <x-form-button
                        type="link"
                        variant="secondary"
                        :href="route('admin.settings.systems.logs.index')"
                        :label="__('common.reset')"
                    />
                    <x-form-button
                        type="submit"
                        variant="primary"
                        :label="__('common.search')"
                    />
                </div>
            </form>
        </div>
    </div>

    

    <!-- Pagination Controls -->
    <x-ui-pagination-controls
        :paginator="$auditLogs"
        :perPageOptions="[25, 50, 100, 200]"
        :currentPerPage="request('per_page', 50)"
        totalLabel="components/ui-pagination.total_count"
        perPageLabel="components/ui-pagination.per_page_label"
    />

    <x-ui-pagination
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
        route="admin.settings.systems.logs.index"
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
                            {{-- Category Badge --}}
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ __('admin/settings/systems/logs/index.categories.' . $log->category, [], $log->category) }}
                            </span>
                            {{-- Action --}}
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $log->action }}
                            </span>
                            {{-- Severity Badge --}}
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $severityColors[$log->severity] ?? $severityColors['info'] }}">
                                {{ __('admin/settings/systems/logs/index.severities.' . $log->severity) }}
                            </span>
                            {{-- Result Badge --}}
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
    <x-ui-pagination-controls
        :paginator="$auditLogs"
        :perPageOptions="[25, 50, 100, 200]"
        :currentPerPage="request('per_page', 50)"
        totalLabel="components/ui-pagination.total_count"
        perPageLabel="components/ui-pagination.per_page_label"
    />

    <x-ui-pagination
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
        route="admin.settings.systems.logs.index"
        :routeParams="request()->except(['page'])"
        :mobilePageRange="0"
        :desktopPageRange="2"
    />

    <!-- Cleanup Section -->
    <div class="flex justify-between items-center my-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('admin/settings/systems/logs/index.cleanup_title') }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('admin/settings/systems/logs/index.cleanup_description') }}
            </p>
        </div>
        <div>
            <form method="POST" action="{{ route('admin.settings.systems.logs.audit.cleanup') }}" id="cleanupAuditLogForm">
                @csrf
                <div class="flex items-center gap-4">
                    <div>
                        <x-form-label
                            for="cleanup_days"
                            :text="__('admin/settings/systems/logs/index.cleanup_days')"
                            class="mb-1"
                        />
                        <x-form-text
                            type="number"
                            name="days"
                            id="cleanup_days"
                            :value="90"
                            :min="0"
                            class="!w-24"
                        />
                    </div>
                    <div class="pt-6">
                        <x-form-button
                            type="button"
                            variant="danger"
                            :label="__('admin/settings/systems/logs/index.cleanup_button')"
                            icon="fas fa-trash"
                            :disabled="$viewOnly"
                            :title="$tooltipText"
                            @click="openModal('cleanupConfirmModal')"
                        />
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cleanup Confirmation Modal -->
    <x-ui-modal
        id="cleanupConfirmModal"
        :title="__('admin/settings/systems/logs/index.cleanup_modal.title')"
        :message="__('admin/settings/systems/logs/index.cleanup_modal.confirm_message')"
        :confirm_label="__('admin/settings/systems/logs/index.cleanup_button')"
        :cancel_label="__('common.cancel')"
        icon_type="warning"
        confirm_color="red"
        form="cleanupAuditLogForm"
    />

    @endif

@endsection
