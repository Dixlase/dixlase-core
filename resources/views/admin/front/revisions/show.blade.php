{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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

@push('styles')
<style>
    .diff-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.8125rem; line-height: 1.4; }
    .diff-cell { padding: 0.125rem 0.5rem; white-space: pre-wrap; word-break: break-word; border-top: 1px solid var(--color-gray-100, #f3f4f6); }
    .dark .diff-cell { border-top-color: var(--color-gray-800, #1f2937); }
    .diff-row.diff-same .diff-cell { background-color: transparent; }
    .diff-row.diff-removed .diff-cell-left { background-color: rgba(239, 68, 68, 0.15); }
    .diff-row.diff-added .diff-cell-right { background-color: rgba(34, 197, 94, 0.15); }
    .diff-row.diff-changed .diff-cell-left { background-color: rgba(239, 68, 68, 0.15); }
    .diff-row.diff-changed .diff-cell-right { background-color: rgba(34, 197, 94, 0.15); }
    .diff-header { display: grid; grid-template-columns: 1fr 1fr; font-weight: 600; font-size: 0.75rem; padding: 0.25rem 0.5rem; background: var(--color-gray-100, #f3f4f6); color: var(--color-gray-700, #374151); }
    .dark .diff-header { background: var(--color-gray-800, #1f2937); color: var(--color-gray-200, #e5e7eb); }
    .diff-header > div:first-child { border-right: 1px solid var(--color-gray-200, #e5e7eb); padding-right: 0.5rem; }
    .dark .diff-header > div:first-child { border-right-color: var(--color-gray-700, #374151); }
    .diff-cell-left { border-right: 1px solid var(--color-gray-200, #e5e7eb); }
    .dark .diff-cell-left { border-right-color: var(--color-gray-700, #374151); }
</style>
@endpush

@section('content')
<div class="mx-auto">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('admin.front.revisions.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>
            {{ __('admin/front.revisions.heading') }}
        </a>

        <form action="{{ route('admin.front.revisions.restore', $revision->id) }}" method="POST"
              @submit.prevent="if (window.confirm(@js(__('admin/front.revisions.restore_confirm_message')))) $event.target.submit();">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700">
                <i class="fas fa-rotate-left"></i>
                {{ __('admin/front.revisions.restore') }}
            </button>
        </form>
    </div>

    <section class="mb-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-4">
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.revisions.created_at') }}</dt>
            <dd class="text-gray-900 dark:text-gray-100 sm:col-span-3">{{ $revision->created_at?->format('Y-m-d H:i:s') }}</dd>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.revisions.type') }}</dt>
            <dd class="text-gray-900 dark:text-gray-100 sm:col-span-3">{{ $typeLabels[$revision->type] ?? $revision->type }}</dd>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.revisions.creator') }}</dt>
            <dd class="text-gray-900 dark:text-gray-100 sm:col-span-3">{{ $revision->creator->display_name ?? $revision->creator->account_name ?? __('admin/front.revisions.unknown_user') }}</dd>
            @if ($revision->note)
                <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.revisions.note') }}</dt>
                <dd class="text-gray-900 dark:text-gray-100 sm:col-span-3">{{ $revision->note }}</dd>
            @endif
        </dl>
    </section>

    <section>
        <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin/front.revisions.diff_heading') }}</h2>

        @if (! $hasChanges)
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/front.revisions.diff_no_changes') }}</p>
        @else
            @if (! empty($metaDiffs))
                <div class="mb-6">
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('admin/front.revisions.diff_meta_heading') }}</h3>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-1 text-left font-medium"></th>
                                <th class="px-3 py-1 text-left font-medium">{{ __('admin/front.revisions.diff_left_label') }}</th>
                                <th class="px-3 py-1 text-left font-medium">{{ __('admin/front.revisions.diff_right_label') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($metaDiffs as $field => $values)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-3 py-1 font-medium text-gray-700 dark:text-gray-200">{{ __('admin/front.revisions.diff_field_'.$field) }}</td>
                                    <td class="px-3 py-1 text-gray-900 dark:text-gray-100">{{ is_scalar($values['old']) ? $values['old'] : json_encode($values['old']) }}</td>
                                    <td class="px-3 py-1 text-gray-900 dark:text-gray-100">{{ is_scalar($values['new']) ? $values['new'] : json_encode($values['new']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @foreach ($diffs as $field => $rows)
                <div class="mb-6">
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('admin/front.revisions.diff_field_'.$field) }}</h3>
                    <div class="rounded border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="diff-header">
                            <div>{{ __('admin/front.revisions.diff_left_label') }}</div>
                            <div>{{ __('admin/front.revisions.diff_right_label') }}</div>
                        </div>
                        @foreach ($rows as $row)
                            <div class="diff-row diff-{{ $row['status'] }}">
                                <div class="diff-cell diff-cell-left">{{ $row['left'] ?? '' }}</div>
                                <div class="diff-cell diff-cell-right">{{ $row['right'] ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </section>
</div>
@endsection
