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

@section('content')
<section>
    <h2>{{ __('admin/settings/systems/backup/index.heading') }}</h2>
    <p class="mb-6 text-gray-600 dark:text-gray-300">
        {{ __('admin/settings/systems/backup/index.description') }}
    </p>

    {{-- Create new button --}}
    <div class="mb-6">
        <x-form-button
            type="button"
            variant="primary"
            :label="__('admin/settings/systems/backup/index.create_button')"
            icon="fas fa-plus"
            @click="openModal('createBackupModal')"
        />
    </div>

    {{-- Backup list --}}
    @if($records->isEmpty())
        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
            <i class="fas fa-archive text-4xl text-gray-400 dark:text-gray-500 mb-3"></i>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('admin/settings/systems/backup/index.table.no_records') }}
            </p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse" aria-label="{{ __('admin/settings/systems/backup/index.table.caption') }}">
                <caption class="sr-only">{{ __('admin/settings/systems/backup/index.table.caption') }}</caption>
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/index.table.created_at') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/index.table.targets') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('admin/settings/systems/backup/index.table.size') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/index.table.status') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/index.table.verification') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/index.table.hash') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('admin/settings/systems/backup/index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <td class="px-4 py-2 whitespace-nowrap">{{ $record->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-2">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($record->targets ?? [] as $target)
                                        <span class="px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {{ __('admin/settings/systems/backup/index.targets.' . $target) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap font-mono text-xs">
                                {{ $record->file_size ? number_format($record->file_size / 1048576, 2) . ' MB' : '-' }}
                            </td>
                            <td class="px-4 py-2">
                                @if($record->status === 'completed')
                                    <span class="px-2 py-0.5 text-xs rounded bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200">
                                        <i class="fas fa-check mr-1"></i>{{ __('admin/settings/systems/backup/index.statuses.completed') }}
                                    </span>
                                @elseif($record->status === 'failed')
                                    <span class="px-2 py-0.5 text-xs rounded bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200">
                                        <i class="fas fa-times mr-1"></i>{{ __('admin/settings/systems/backup/index.statuses.failed') }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ __('admin/settings/systems/backup/index.statuses.' . $record->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($record->verification_status === 'valid')
                                    <span class="text-green-600 dark:text-green-400" title="{{ __('admin/settings/systems/backup/index.verifications.valid') }}">
                                        <i class="fas fa-shield-check"></i>
                                    </span>
                                @elseif($record->verification_status === 'invalid')
                                    <span class="text-red-600 dark:text-red-400" title="{{ __('admin/settings/systems/backup/index.verifications.invalid') }}">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500" title="{{ __('admin/settings/systems/backup/index.verifications.unchecked') }}">
                                        <i class="fas fa-question-circle"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">
                                {{ $record->hash ? substr($record->hash, 0, 12) . '...' : '-' }}
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                <div class="flex justify-end gap-3">
                                    @if($record->status === 'completed')
                                        <a href="{{ route('admin.settings.systems.backup.download', $record) }}"
                                           class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                           title="{{ __('admin/settings/systems/backup/index.actions.download') }}">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button"
                                                @click="openModal('restoreBackupModal{{ $record->id }}')"
                                                class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                                                title="{{ __('admin/settings/systems/backup/index.actions.restore') }}">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>
                                    @endif
                                    <button type="button"
                                            @click="openModal('deleteBackupModal{{ $record->id }}')"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            title="{{ __('admin/settings/systems/backup/index.actions.delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection

@section('modals')
    {{-- Create new modal --}}
    <x-ui-modal
        id="createBackupModal"
        :title="__('admin/settings/systems/backup/index.create_modal.title')"
        :message="__('admin/settings/systems/backup/index.create_modal.message')"
        :confirm-label="__('admin/settings/systems/backup/index.create_modal.confirm_label')"
        :cancel-label="__('admin/settings/systems/backup/index.create_modal.cancel_label')"
        icon-type="info"
        confirm-color="blue"
        form="createBackupForm"
    >
        <form id="createBackupForm"
              action="{{ route('admin.settings.systems.backup.create') }}"
              method="POST"
              class="text-left">
            @csrf

            <div class="mb-4">
                <p class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-200">
                    {{ __('admin/settings/systems/backup/index.create_modal.targets_label') }}
                </p>
                <div class="space-y-2">
                    @foreach($availableTargets as $target)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   name="targets[]"
                                   value="{{ $target }}"
                                   class="checkbox-common"
                                   @if(in_array($target, $defaultTargets, true)) checked @endif>
                            <span class="text-sm text-gray-700 dark:text-gray-200">
                                {{ __('admin/settings/systems/backup/index.targets.' . $target) }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mb-2">
                <label for="retentionDaysInput" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">
                    {{ __('admin/settings/systems/backup/index.create_modal.retention_label') }}
                </label>
                <input type="number"
                       id="retentionDaysInput"
                       name="retention_days"
                       min="1"
                       max="3650"
                       value="{{ old('retention_days', $defaultRetentionDays) }}"
                       class="input-common input-md">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('admin/settings/systems/backup/index.create_modal.retention_help') }}
                </p>
            </div>
        </form>
    </x-ui-modal>

    {{-- Restore form + restore confirmation modal (completed records only) --}}
    @foreach($records as $record)
        @if($record->status === 'completed')
            <form id="restoreBackupForm{{ $record->id }}"
                  action="{{ route('admin.settings.systems.backup.restore', $record) }}"
                  method="POST"
                  class="hidden">
                @csrf
            </form>

            <x-ui-modal
                id="restoreBackupModal{{ $record->id }}"
                :title="__('admin/settings/systems/backup/index.restore_modal.title')"
                :message="__('admin/settings/systems/backup/index.restore_modal.message')"
                :confirm-label="__('admin/settings/systems/backup/index.restore_modal.confirm_label')"
                :cancel-label="__('admin/settings/systems/backup/index.restore_modal.cancel_label')"
                icon-type="warning"
                confirm-color="yellow"
                form="restoreBackupForm{{ $record->id }}"
            />
        @endif
    @endforeach

    {{-- Delete form + delete confirmation modal (per record) --}}
    @foreach($records as $record)
        <form id="deleteBackupForm{{ $record->id }}"
              action="{{ route('admin.settings.systems.backup.destroy', $record) }}"
              method="POST"
              class="hidden">
            @csrf
            @method('DELETE')
        </form>

        <x-ui-modal
            id="deleteBackupModal{{ $record->id }}"
            :title="__('admin/settings/systems/backup/index.delete_modal.title')"
            :message="__('admin/settings/systems/backup/index.delete_modal.message')"
            :confirm-label="__('admin/settings/systems/backup/index.delete_modal.confirm_label')"
            :cancel-label="__('admin/settings/systems/backup/index.delete_modal.cancel_label')"
            icon-type="danger"
            confirm-color="red"
            form="deleteBackupForm{{ $record->id }}"
        />
    @endforeach
@endsection
