{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

@section('content')

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.settings.systems.logs.index', ['type' => 'audit', 'view' => 'db']) }}" 
               class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('admin/settings/systems/logs/index.detail_title') }} #{{ $auditLog->id }}
            </h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Information --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin/settings/systems/logs/index.basic_info') }}
                    </h2>
                </div>
                <div class="p-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.occurred_at') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $auditLog->occurred_at?->format('Y-m-d H:i:s') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.category') }}
                            </dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    {{ __('admin/settings/systems/logs/index.categories.' . $auditLog->category, [], $auditLog->category) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.action') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ $auditLog->action }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.severity') }}
                            </dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $severityColors[$auditLog->severity] ?? $severityColors['info'] }}">
                                    {{ __('admin/settings/systems/logs/index.severities.' . $auditLog->severity) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.outcome') }}
                            </dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $outcomeColors[$auditLog->outcome] ?? $outcomeColors['unknown'] }}">
                                    {{ __('admin/settings/systems/logs/index.outcomes.' . $auditLog->outcome) }}
                                </span>
                            </dd>
                        </div>
                        @if($auditLog->plugin_name)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.plugin') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $auditLog->plugin_name }}
                                @if($auditLog->plugin_version)
                                <span class="text-gray-500 dark:text-gray-400">v{{ $auditLog->plugin_version }}</span>
                                @endif
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Actor & Subject --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin/settings/systems/logs/index.actor_target') }}
                    </h2>
                </div>
                <div class="p-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.actor') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                @if($auditLog->actor_name)
                                    {{ $auditLog->actor_name }}
                                    @if($auditLog->actor_type)
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">
                                        ({{ class_basename($auditLog->actor_type) }}:{{ $auditLog->actor_id }})
                                    </span>
                                    @endif
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">{{ __('admin/settings/systems/logs/index.system') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.target') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                @if($auditLog->target_label || $auditLog->target_type)
                                    {{ $auditLog->target_label ?? '-' }}
                                    @if($auditLog->target_type)
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">
                                        ({{ class_basename($auditLog->target_type) }}:{{ $auditLog->target_id }})
                                    </span>
                                    @endif
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </dd>
                        </div>
                        @if($auditLog->impersonated_by_id)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.impersonated_by') }}
                            </dt>
                            <dd class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                                ID: {{ $auditLog->impersonated_by_id }}
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Context --}}
            @if($auditLog->context)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin/settings/systems/logs/index.context') }}
                    </h2>
                </div>
                <div class="p-4">
                    @if(isset($auditLog->context['message']))
                    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-sm text-gray-900 dark:text-white">{{ $auditLog->context['message'] }}</p>
                    </div>
                    @endif

                    @if(isset($auditLog->context['diff']))
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('admin/settings/systems/logs/index.changes') }}
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">{{ __('admin/settings/systems/logs/index.field') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">{{ __('admin/settings/systems/logs/index.before') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">{{ __('admin/settings/systems/logs/index.after') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($auditLog->context['diff'] as $field => $change)
                                    <tr>
                                        <td class="px-3 py-2 text-sm font-medium text-gray-900 dark:text-white">{{ $field }}</td>
                                        <td class="px-3 py-2 text-sm text-red-600 dark:text-red-400 font-mono">
                                            {{ is_array($change['from'] ?? null) ? json_encode($change['from']) : ($change['from'] ?? '-') }}
                                        </td>
                                        <td class="px-3 py-2 text-sm text-green-600 dark:text-green-400 font-mono">
                                            {{ is_array($change['to'] ?? null) ? json_encode($change['to']) : ($change['to'] ?? '-') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Raw JSON --}}
                    <div x-data="{ showRaw: false }">
                        <button @click="showRaw = !showRaw" 
                                class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                            <span x-show="!showRaw">{{ __('admin/settings/systems/logs/index.show_raw_json') }}</span>
                            <span x-show="showRaw">{{ __('admin/settings/systems/logs/index.hide_raw_json') }}</span>
                        </button>
                        <div x-show="showRaw" x-collapse class="mt-2">
                            <pre class="p-3 bg-gray-900 text-gray-100 rounded-lg text-xs overflow-x-auto">{{ json_encode($auditLog->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Request Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin/settings/systems/logs/index.request_info') }}
                    </h2>
                </div>
                <div class="p-4">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.ip_address') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ $auditLog->ip_address ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.user_agent') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white break-all">
                                {{ $auditLog->user_agent ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.request_id') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">
                                {{ $auditLog->request_id ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin/settings/systems/logs/index.session_id') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">
                                {{ $auditLog->session_id ? Str::limit($auditLog->session_id, 20) : '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Related Logs --}}
            @if($relatedLogs && $relatedLogs->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin/settings/systems/logs/index.related_logs') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('admin/settings/systems/logs/index.same_request') }}
                    </p>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($relatedLogs as $related)
                    <a href="{{ route('admin.settings.systems.logs.audit.show', $related->id) }}" 
                       class="block p-3 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-900 dark:text-white">{{ $related->action }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $related->occurred_at?->format('H:i:s') }}
                            </span>
                        </div>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $outcomeColors[$related->outcome] ?? $outcomeColors['unknown'] }}">
                                {{ __('admin/settings/systems/logs/index.outcomes.' . $related->outcome) }}
                            </span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Meta Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin/settings/systems/logs/index.meta_info') }}
                    </h2>
                </div>
                <div class="p-4">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $auditLog->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Schema Version</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $auditLog->schema_version }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $auditLog->created_at?->format('Y-m-d H:i:s') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

@endsection
