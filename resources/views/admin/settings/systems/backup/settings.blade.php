{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
    <h2>{{ __('admin/settings/systems/backup/settings.heading') }}</h2>
    <p class="mb-6 text-gray-600 dark:text-gray-300">
        {{ __('admin/settings/systems/backup/settings.description') }}
    </p>

    <form action="{{ route('admin.settings.systems.backup.settings.update') }}"
          method="POST"
          class="max-w-2xl">
        @csrf

        {{-- Default backup targets --}}
        <div class="mb-6">
            <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-200">
                {{ __('admin/settings/systems/backup/settings.form.default_targets_label') }}
            </label>
            <div class="space-y-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                @foreach($availableTargets as $target)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="default_targets[]"
                               value="{{ $target }}"
                               class="checkbox-common"
                               @if(in_array($target, old('default_targets', $defaultTargets), true)) checked @endif>
                        <span class="text-sm text-gray-700 dark:text-gray-200">
                            {{ __('admin/settings/systems/backup/settings.targets.' . $target) }}
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                {{ __('admin/settings/systems/backup/settings.form.default_targets_help') }}
            </p>
            @error('default_targets')
                <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Default retention period --}}
        <div class="mb-6">
            <label for="defaultRetentionDays" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-200">
                {{ __('admin/settings/systems/backup/settings.form.default_retention_label') }}
            </label>
            <input type="number"
                   id="defaultRetentionDays"
                   name="default_retention_days"
                   min="1"
                   max="3650"
                   value="{{ old('default_retention_days', $defaultRetentionDays) }}"
                   class="input-common input-md">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                <i class="fas fa-info-circle mr-1"></i>
                {{ __('admin/settings/systems/backup/settings.form.default_retention_help') }}
            </p>
            @error('default_retention_days')
                <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Source-tree exclusions (plugins/themes targets) --}}
        <div class="mb-6">
            <p class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-200">
                {{ __('admin/settings/systems/backup/settings.form.exclusions_label') }}
            </p>
            <div class="space-y-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div>
                    <x-form-toggle
                        :label="__('admin/settings/systems/backup/settings.form.exclude_node_modules_label')"
                        id="excludeNodeModules"
                        name="exclude_node_modules"
                        :checked="old('exclude_node_modules', $excludeNodeModules)"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('admin/settings/systems/backup/settings.form.exclude_node_modules_help') }}
                    </p>
                </div>
                <div>
                    <x-form-toggle
                        :label="__('admin/settings/systems/backup/settings.form.exclude_vendor_label')"
                        id="excludeVendor"
                        name="exclude_vendor"
                        :checked="old('exclude_vendor', $excludeVendor)"
                    />
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        {{ __('admin/settings/systems/backup/settings.form.exclude_vendor_help') }}
                    </p>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                {{ __('admin/settings/systems/backup/settings.form.exclusions_help') }}
            </p>
        </div>

        {{-- Save button --}}
        <div class="flex justify-end">
            <x-form-button
                type="submit"
                variant="primary"
                :label="__('admin/settings/systems/backup/settings.form.save_button')"
                icon="fas fa-save"
                :disabled="$viewOnly"
                :title="$tooltipText"
            />
        </div>
    </form>
</section>
@endsection
