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

@php
    // See cache.blade.php for the CheckMenuAccess contract.
    $viewOnly = ! ($menuEditable ?? true);
    $tooltipText = $viewOnly ? __('common.view_only_action_disabled') : '';
@endphp

@section('content')
<section>
    <div class="mb-4">
        <a href="{{ route('admin.settings.systems.backup.index') }}"
           class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
            <i class="fas fa-arrow-left mr-1"></i>{{ __('admin/settings/systems/backup/index.detail.back') }}
        </a>
    </div>

    <h2>{{ __('admin/settings/systems/backup/index.detail.heading') }}</h2>

    @if(! $fileExists)
        <p class="mt-2 mb-4 px-3 py-2 text-sm rounded bg-amber-50 dark:bg-amber-900/40 text-amber-700 dark:text-amber-200">
            <i class="fas fa-triangle-exclamation mr-1"></i>{{ __('admin/settings/systems/backup/index.detail.file_missing') }}
        </p>
    @endif

    {{-- Metadata --}}
    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/backup/index.detail.created_at') }}</dt>
            <dd>{{ $record->created_at?->format('Y-m-d H:i:s') }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/backup/index.detail.status') }}</dt>
            <dd>{{ __('admin/settings/systems/backup/index.statuses.' . $record->status) }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/backup/index.detail.targets') }}</dt>
            <dd class="flex flex-wrap gap-1 mt-1">
                @foreach($record->targets ?? [] as $target)
                    <span class="px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        {{ __('admin/settings/systems/backup/index.targets.' . $target) }}
                    </span>
                @endforeach
            </dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/backup/index.detail.size') }}</dt>
            <dd class="font-mono">{{ $record->file_size ? number_format($record->file_size / 1048576, 2) . ' MB' : '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/backup/index.detail.file_name') }}</dt>
            <dd class="font-mono break-all">{{ $record->file_name ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/backup/index.detail.path') }}</dt>
            <dd class="font-mono break-all">{{ $record->file_path ? dirname($record->file_path) : '-' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/backup/index.detail.hash') }}</dt>
            <dd class="font-mono break-all text-xs">{{ $record->hash ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/backup/index.detail.retention') }}</dt>
            <dd>{{ $record->retention_until?->format('Y-m-d H:i:s') ?: __('admin/settings/systems/backup/index.detail.retention_none') }}</dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/backup/index.detail.duration') }}</dt>
            <dd class="font-mono">{{ isset($record->metadata['duration_seconds']) ? $record->metadata['duration_seconds'] . ' s' : '-' }}</dd>
        </div>
    </dl>

    {{-- Editable note --}}
    <form action="{{ route('admin.settings.systems.backup.note.update', $record) }}" method="POST" class="mt-8 max-w-2xl">
        @csrf
        <label for="note" class="block text-sm font-medium mb-1">
            {{ __('admin/settings/systems/backup/index.detail.note_label') }}
        </label>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
            {{ __('admin/settings/systems/backup/index.detail.note_help') }}
        </p>
        <textarea id="note" name="note" rows="4" maxlength="2000"
                  placeholder="{{ __('admin/settings/systems/backup/index.detail.note_placeholder') }}"
                  class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">{{ old('note', $record->note) }}</textarea>
        @error('note')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        <div class="mt-3">
            <x-form-button
                type="submit"
                variant="primary"
                :label="__('admin/settings/systems/backup/index.detail.note_save')"
                icon="fas fa-floppy-disk"
                :disabled="$viewOnly"
                :title="$tooltipText"
            />
        </div>
    </form>
</section>
@endsection
