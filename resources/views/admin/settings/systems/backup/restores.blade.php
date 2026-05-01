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
    <h2>{{ __('admin/settings/systems/backup/restores.heading') }}</h2>
    <p class="mb-6 text-gray-600 dark:text-gray-300">
        {{ __('admin/settings/systems/backup/restores.description') }}
    </p>

    @if($records->isEmpty())
        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
            <i class="fas fa-clock-rotate-left text-4xl text-gray-400 dark:text-gray-500 mb-3"></i>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('admin/settings/systems/backup/restores.table.no_records') }}
            </p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border-collapse" aria-label="{{ __('admin/settings/systems/backup/restores.table.caption') }}">
                <caption class="sr-only">{{ __('admin/settings/systems/backup/restores.table.caption') }}</caption>
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/restores.table.restored_at') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/restores.table.backup') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/restores.table.targets') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/restores.table.restored_by') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('admin/settings/systems/backup/restores.table.duration') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('admin/settings/systems/backup/restores.table.status') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('admin/settings/systems/backup/restores.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <td class="px-4 py-2 whitespace-nowrap">
                                {{ $record->restored_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                @if($record->backupRecord)
                                    <span class="font-mono text-xs text-gray-600 dark:text-gray-300">
                                        {{ $record->backupRecord->file_name }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">
                                        {{ __('admin/settings/systems/backup/restores.table.backup_deleted') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($record->targets ?? [] as $target)
                                        <span class="px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {{ __('admin/settings/systems/backup/restores.targets.' . $target) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $record->restored_by_name ?: '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap font-mono text-xs">
                                {{ $record->duration_seconds !== null ? $record->duration_seconds . 's' : '-' }}
                            </td>
                            <td class="px-4 py-2">
                                @if($record->status === 'completed')
                                    <span class="px-2 py-0.5 text-xs rounded bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200">
                                        <i class="fas fa-check mr-1"></i>{{ __('admin/settings/systems/backup/restores.statuses.completed') }}
                                    </span>
                                @elseif($record->status === 'failed')
                                    <span class="px-2 py-0.5 text-xs rounded bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200" title="{{ $record->error }}">
                                        <i class="fas fa-times mr-1"></i>{{ __('admin/settings/systems/backup/restores.statuses.failed') }}
                                    </span>
                                @elseif($record->status === 'rolled_back')
                                    <span class="px-2 py-0.5 text-xs rounded bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-200">
                                        <i class="fas fa-rotate-left mr-1"></i>{{ __('admin/settings/systems/backup/restores.statuses.rolled_back') }}
                                    </span>
                                @elseif($record->status === 'in_progress')
                                    <span class="px-2 py-0.5 text-xs rounded bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200">
                                        <i class="fas fa-spinner fa-spin mr-1"></i>{{ __('admin/settings/systems/backup/restores.statuses.in_progress') }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ __('admin/settings/systems/backup/restores.statuses.' . $record->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                @if($record->canRollback())
                                    <button type="button"
                                            @click="openModal('rollbackRestoreModal{{ $record->id }}')"
                                            class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300"
                                            title="{{ __('admin/settings/systems/backup/restores.actions.rollback') }}">
                                        <i class="fas fa-rotate-left"></i>
                                    </button>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">
                                        <i class="fas fa-rotate-left"></i>
                                    </span>
                                @endif
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
    {{-- ロールバックフォーム + ロールバック確認モーダル（canRollback() のレコードのみ） --}}
    @foreach($records as $record)
        @if($record->canRollback())
            <form id="rollbackRestoreForm{{ $record->id }}"
                  action="{{ route('admin.settings.systems.backup.restores.rollback', $record) }}"
                  method="POST"
                  class="hidden">
                @csrf
            </form>

            <x-ui-modal
                id="rollbackRestoreModal{{ $record->id }}"
                :title="__('admin/settings/systems/backup/restores.rollback_modal.title')"
                :message="__('admin/settings/systems/backup/restores.rollback_modal.message')"
                :confirm-label="__('admin/settings/systems/backup/restores.rollback_modal.confirm_label')"
                :cancel-label="__('admin/settings/systems/backup/restores.rollback_modal.cancel_label')"
                icon-type="warning"
                confirm-color="yellow"
                form="rollbackRestoreForm{{ $record->id }}"
            />
        @endif
    @endforeach
@endsection
