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
      (see LICENSE.commercial, or contact info@dixlase.org).

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
<div class="mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.settings.security.integrity') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
            <i class="fas fa-arrow-left mr-1"></i>{{ __('admin/settings/security/integrity.back_to_list') }}
        </a>
    </div>

    <h1 class="text-2xl font-bold mb-2">{{ __('admin/settings/security/integrity.scan_details') }}</h1>
    <p class="text-gray-600 dark:text-gray-400 mb-6">{{ $audit->created_at->format('Y-m-d H:i:s') }}</p>

    <!-- スキャン概要 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('admin/settings/security/integrity.scan_summary') }}</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/security/integrity.status') }}</p>
                @if($audit->status === $integrityStatusOk)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <i class="fas fa-check-circle mr-1"></i>{{ __('admin/settings/security/integrity.status_ok') }}
                    </span>
                @elseif($audit->status === $integrityStatusWarning)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('admin/settings/security/integrity.status_warning') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        <i class="fas fa-times-circle mr-1"></i>{{ __('admin/settings/security/integrity.status_critical') }}
                    </span>
                @endif
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/security/integrity.files_scanned') }}</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ $audit->total_files_scanned }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/security/integrity.trigger') }}</p>
                <p class="font-medium text-gray-900 dark:text-white">
                    @switch($audit->trigger)
                        @case($triggerManual)
                            {{ __('admin/settings/security/integrity.trigger_manual') }}
                            @break
                        @case($triggerSchedule)
                            {{ __('admin/settings/security/integrity.trigger_schedule') }}
                            @break
                        @default
                            {{ $audit->trigger }}
                    @endswitch
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/security/integrity.scope') }}</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst($audit->scope) }}</p>
            </div>
        </div>
    </div>

    <!-- 変更されたファイル -->
    @if(!empty($resultPayload['changed']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-red-200 dark:border-red-800 p-6 mb-6">
            <h2 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-4">
                <i class="fas fa-edit mr-2"></i>{{ __('admin/settings/security/integrity.changed_files') }} ({{ count($resultPayload['changed']) }})
            </h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('admin/settings/security/integrity.file_path') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('admin/settings/security/integrity.expected_hash') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('admin/settings/security/integrity.actual_hash') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($resultPayload['changed'] as $file)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono">{{ $file['path'] ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ substr($file['old_hash'] ?? '', 0, 16) }}...</td>
                                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 font-mono">{{ substr($file['new_hash'] ?? '', 0, 16) }}...</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- 追加されたファイル -->
    @if(!empty($resultPayload['added']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-yellow-200 dark:border-yellow-800 p-6 mb-6">
            <h2 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-4">
                <i class="fas fa-plus-circle mr-2"></i>{{ __('admin/settings/security/integrity.added_files') }} ({{ count($resultPayload['added']) }})
            </h2>
            
            <ul class="space-y-2">
                @foreach($resultPayload['added'] as $file)
                    <li class="text-sm text-gray-900 dark:text-white font-mono">
                        <i class="fas fa-file text-yellow-500 mr-2"></i>{{ is_array($file) ? ($file['path'] ?? 'N/A') : $file }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 削除されたファイル -->
    @if(!empty($resultPayload['removed']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-orange-200 dark:border-orange-800 p-6 mb-6">
            <h2 class="text-lg font-semibold text-orange-800 dark:text-orange-200 mb-4">
                <i class="fas fa-minus-circle mr-2"></i>{{ __('admin/settings/security/integrity.removed_files') }} ({{ count($resultPayload['removed']) }})
            </h2>
            
            <ul class="space-y-2">
                @foreach($resultPayload['removed'] as $file)
                    <li class="text-sm text-gray-900 dark:text-white font-mono">
                        <i class="fas fa-file-excel text-orange-500 mr-2"></i>{{ is_array($file) ? ($file['path'] ?? 'N/A') : $file }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 疑わしいファイル -->
    @if(!empty($resultPayload['suspicious']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-purple-200 dark:border-purple-800 p-6 mb-6">
            <h2 class="text-lg font-semibold text-purple-800 dark:text-purple-200 mb-4">
                <i class="fas fa-question-circle mr-2"></i>{{ __('admin/settings/security/integrity.suspicious_files') }} ({{ count($resultPayload['suspicious']) }})
            </h2>
            
            <p class="text-sm text-purple-600 dark:text-purple-400 mb-4">{{ __('admin/settings/security/integrity.suspicious_files_help') }}</p>
            
            <ul class="space-y-2">
                @foreach($resultPayload['suspicious'] as $file)
                    <li class="text-sm text-gray-900 dark:text-white font-mono">
                        <i class="fas fa-exclamation text-purple-500 mr-2"></i>{{ is_array($file) ? ($file['path'] ?? 'N/A') : $file }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 問題なしの場合 -->
    @if(!$audit->hasIssues())
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
            <div class="flex items-center text-green-700 dark:text-green-300">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <div>
                    <h3 class="font-semibold">{{ __('admin/settings/security/integrity.all_files_ok') }}</h3>
                    <p class="text-sm">{{ __('admin/settings/security/integrity.all_files_ok_description') }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
