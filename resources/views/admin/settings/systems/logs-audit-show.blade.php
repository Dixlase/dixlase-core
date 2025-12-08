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

    {{-- ヘッダー --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db']) }}" 
               class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('admin.settings.audit_logs.detail_title') }} #{{ $auditLog->id }}
            </h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- メイン情報 --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- 基本情報 --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin.settings.audit_logs.basic_info') }}
                    </h2>
                </div>
                <div class="p-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.occurred_at') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $auditLog->occurred_at?->format('Y-m-d H:i:s') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.category') }}
                            </dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    {{ __('admin.settings.audit_logs.categories.' . $auditLog->category, [], $auditLog->category) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.action') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ $auditLog->action }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.severity') }}
                            </dt>
                            <dd class="mt-1">
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $severityColors[$auditLog->severity] ?? $severityColors['info'] }}">
                                    {{ __('admin.settings.audit_logs.severities.' . $auditLog->severity) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.outcome') }}
                            </dt>
                            <dd class="mt-1">
                                @php
                                    $outcomeColors = [
                                        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'failure' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'denied' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'unknown' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $outcomeColors[$auditLog->outcome] ?? $outcomeColors['unknown'] }}">
                                    {{ __('admin.settings.audit_logs.outcomes.' . $auditLog->outcome) }}
                                </span>
                            </dd>
                        </div>
                        @if($auditLog->plugin_name)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.plugin') }}
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

            {{-- 行為者・対象 --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin.settings.audit_logs.actor_target') }}
                    </h2>
                </div>
                <div class="p-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.actor') }}
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
                                    <span class="text-gray-400 dark:text-gray-500">{{ __('admin.settings.audit_logs.system') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.target') }}
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
                                {{ __('admin.settings.audit_logs.impersonated_by') }}
                            </dt>
                            <dd class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                                ID: {{ $auditLog->impersonated_by_id }}
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- コンテキスト --}}
            @if($auditLog->context)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin.settings.audit_logs.context') }}
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
                            {{ __('admin.settings.audit_logs.changes') }}
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">{{ __('admin.settings.audit_logs.field') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">{{ __('admin.settings.audit_logs.before') }}</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">{{ __('admin.settings.audit_logs.after') }}</th>
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

                    {{-- 生のJSON --}}
                    <div x-data="{ showRaw: false }">
                        <button @click="showRaw = !showRaw" 
                                class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                            <span x-show="!showRaw">{{ __('admin.settings.audit_logs.show_raw_json') }}</span>
                            <span x-show="showRaw">{{ __('admin.settings.audit_logs.hide_raw_json') }}</span>
                        </button>
                        <div x-show="showRaw" x-collapse class="mt-2">
                            <pre class="p-3 bg-gray-900 text-gray-100 rounded-lg text-xs overflow-x-auto">{{ json_encode($auditLog->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- サイドバー --}}
        <div class="space-y-6">
            {{-- リクエスト情報 --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin.settings.audit_logs.request_info') }}
                    </h2>
                </div>
                <div class="p-4">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.ip_address') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ $auditLog->ip_address ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.user_agent') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white break-all">
                                {{ $auditLog->user_agent ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.request_id') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">
                                {{ $auditLog->request_id ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ __('admin.settings.audit_logs.session_id') }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">
                                {{ $auditLog->session_id ? Str::limit($auditLog->session_id, 20) : '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- 関連ログ --}}
            @if($relatedLogs && $relatedLogs->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin.settings.audit_logs.related_logs') }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('admin.settings.audit_logs.same_request') }}
                    </p>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($relatedLogs as $related)
                    <a href="{{ route('admin.settings.systems.audit-logs.show', $related->id) }}" 
                       class="block p-3 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-900 dark:text-white">{{ $related->action }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $related->occurred_at?->format('H:i:s') }}
                            </span>
                        </div>
                        <div class="mt-1">
                            @php
                                $relatedOutcomeColors = [
                                    'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'failure' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'denied' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'unknown' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $relatedOutcomeColors[$related->outcome] ?? $relatedOutcomeColors['unknown'] }}">
                                {{ __('admin.settings.audit_logs.outcomes.' . $related->outcome) }}
                            </span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- メタ情報 --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('admin.settings.audit_logs.meta_info') }}
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
