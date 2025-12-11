{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the  implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
    <h1 class="text-2xl font-bold mb-2">{{ __('admin.settings.security.integrity.heading') }}</h1>
    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('admin.settings.security.integrity.description') }}</p>

    <!-- ベースライン情報 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('admin.settings.security.integrity.baseline_info') }}</h2>
        
        @if($hasBaseline && $baselineMeta)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.security.integrity.baseline_version') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $baselineMeta['version'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.security.integrity.baseline_generated_at') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $baselineMeta['generated_at'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.security.integrity.baseline_files_count') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $baselineMeta['files_count'] ?? 'N/A' }}</p>
                </div>
            </div>
            
            <form method="POST" action="{{ route('admin.settings.security.integrity.regenerate-baseline') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-blue-600 dark:text-blue-400 hover:underline" onclick="return confirm('{{ __('admin.settings.security.integrity.regenerate_confirm') }}')">
                    <i class="fas fa-sync-alt mr-1"></i>{{ __('admin.settings.security.integrity.regenerate_baseline') }}
                </button>
            </form>
        @else
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <div class="flex items-center text-yellow-600 dark:text-yellow-400">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span>{{ __('admin.settings.security.integrity.no_baseline') }}</span>
                </div>
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-2">{{ __('admin.settings.security.integrity.no_baseline_help') }}</p>
                
                <form method="POST" action="{{ route('admin.settings.security.integrity.regenerate-baseline') }}" class="mt-3">
                    @csrf
                    <x-form.button type="submit">
                        <i class="fas fa-plus mr-1"></i>{{ __('admin.settings.security.integrity.generate_baseline') }}
                    </x-form.button>
                </form>
            </div>
        @endif
    </div>

    <!-- 最新スキャン結果 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin.settings.security.integrity.latest_scan') }}</h2>
            
            @if($hasBaseline)
                <form method="POST" action="{{ route('admin.settings.security.integrity.scan') }}">
                    @csrf
                    <x-form.button type="submit">
                        <i class="fas fa-search mr-1"></i>{{ __('admin.settings.security.integrity.run_scan') }}
                    </x-form.button>
                </form>
            @endif
        </div>
        
        @if($latestAudit)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.security.integrity.scan_date') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $latestAudit->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.security.integrity.files_scanned') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $latestAudit->total_files_scanned }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.security.integrity.status') }}</p>
                    @if($latestAudit->status === \App\Models\FileIntegrityAudit::STATUS_OK)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <i class="fas fa-check-circle mr-1"></i>{{ __('admin.settings.security.integrity.status_ok') }}
                        </span>
                    @elseif($latestAudit->status === \App\Models\FileIntegrityAudit::STATUS_WARNING)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('admin.settings.security.integrity.status_warning') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <i class="fas fa-times-circle mr-1"></i>{{ __('admin.settings.security.integrity.status_critical') }}
                        </span>
                    @endif
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.security.integrity.trigger') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        @switch($latestAudit->trigger)
                            @case(\App\Models\FileIntegrityAudit::TRIGGER_MANUAL)
                                {{ __('admin.settings.security.integrity.trigger_manual') }}
                                @break
                            @case(\App\Models\FileIntegrityAudit::TRIGGER_SCHEDULE)
                                {{ __('admin.settings.security.integrity.trigger_schedule') }}
                                @break
                            @case(\App\Models\FileIntegrityAudit::TRIGGER_INSTALL)
                                {{ __('admin.settings.security.integrity.trigger_install') }}
                                @break
                            @case(\App\Models\FileIntegrityAudit::TRIGGER_UPDATE)
                                {{ __('admin.settings.security.integrity.trigger_update') }}
                                @break
                            @default
                                {{ $latestAudit->trigger }}
                        @endswitch
                    </p>
                </div>
            </div>

            <!-- 問題の詳細 -->
            @if($latestAudit->hasIssues())
                <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <h3 class="font-semibold text-red-800 dark:text-red-200 mb-3">{{ __('admin.settings.security.integrity.issues_found') }}</h3>
                    
                    @php $summary = $latestAudit->summary; @endphp
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        @if(($summary['changed'] ?? 0) > 0)
                            <div class="flex items-center text-red-700 dark:text-red-300">
                                <i class="fas fa-edit mr-2"></i>
                                <span>{{ __('admin.settings.security.integrity.changed_files') }}: {{ $summary['changed'] }}</span>
                            </div>
                        @endif
                        @if(($summary['added'] ?? 0) > 0)
                            <div class="flex items-center text-yellow-700 dark:text-yellow-300">
                                <i class="fas fa-plus-circle mr-2"></i>
                                <span>{{ __('admin.settings.security.integrity.added_files') }}: {{ $summary['added'] }}</span>
                            </div>
                        @endif
                        @if(($summary['removed'] ?? 0) > 0)
                            <div class="flex items-center text-orange-700 dark:text-orange-300">
                                <i class="fas fa-minus-circle mr-2"></i>
                                <span>{{ __('admin.settings.security.integrity.removed_files') }}: {{ $summary['removed'] }}</span>
                            </div>
                        @endif
                        @if(($summary['suspicious'] ?? 0) > 0)
                            <div class="flex items-center text-purple-700 dark:text-purple-300">
                                <i class="fas fa-question-circle mr-2"></i>
                                <span>{{ __('admin.settings.security.integrity.suspicious_files') }}: {{ $summary['suspicious'] }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <a href="{{ route('admin.settings.security.integrity.show', $latestAudit) }}" class="inline-flex items-center mt-3 text-red-600 dark:text-red-400 hover:underline">
                        {{ __('admin.settings.security.integrity.view_details') }}
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            @else
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center text-green-700 dark:text-green-300">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>{{ __('admin.settings.security.integrity.no_issues') }}</span>
                    </div>
                </div>
            @endif
        @else
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-gray-600 dark:text-gray-400">{{ __('admin.settings.security.integrity.no_scan_yet') }}</p>
            </div>
        @endif
    </div>

    <!-- スキャン履歴 -->
    @if($recentAudits->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('admin.settings.security.integrity.scan_history') }}</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('admin.settings.security.integrity.scan_date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('admin.settings.security.integrity.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('admin.settings.security.integrity.files_scanned') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('admin.settings.security.integrity.trigger') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('admin.settings.security.integrity.issues') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentAudits as $audit)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $audit->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($audit->status === \App\Models\FileIntegrityAudit::STATUS_OK)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ __('admin.settings.security.integrity.status_ok') }}
                                        </span>
                                    @elseif($audit->status === \App\Models\FileIntegrityAudit::STATUS_WARNING)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            {{ __('admin.settings.security.integrity.status_warning') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            {{ __('admin.settings.security.integrity.status_critical') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $audit->total_files_scanned }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @switch($audit->trigger)
                                        @case(\App\Models\FileIntegrityAudit::TRIGGER_MANUAL)
                                            {{ __('admin.settings.security.integrity.trigger_manual') }}
                                            @break
                                        @case(\App\Models\FileIntegrityAudit::TRIGGER_SCHEDULE)
                                            {{ __('admin.settings.security.integrity.trigger_schedule') }}
                                            @break
                                        @default
                                            {{ $audit->trigger }}
                                    @endswitch
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    @if($audit->hasIssues())
                                        @php $s = $audit->summary; @endphp
                                        <span class="text-red-600 dark:text-red-400">
                                            {{ ($s['changed'] ?? 0) + ($s['added'] ?? 0) + ($s['removed'] ?? 0) + ($s['suspicious'] ?? 0) }}
                                        </span>
                                    @else
                                        <span class="text-green-600 dark:text-green-400">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <a href="{{ route('admin.settings.security.integrity.show', $audit) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ __('common.details') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
