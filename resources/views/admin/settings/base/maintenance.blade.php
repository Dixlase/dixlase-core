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

@section('content')
<div class="mx-auto">
<div x-data="{ maintenanceMode: '{{ old('maintenance_mode', $settings['maintenance_mode']) ? '1' : '0' }}', autoRelease: '{{ old('maintenance_auto_release', $settings['maintenance_auto_release']) }}' }">
<form id="base-maintenance-form" action="{{ route('admin.settings.base.maintenance.update') }}" method="POST">
    @csrf

    <!-- メンテナンスモード設定 -->
    <section>
        <h2>{{ __('admin/settings/base/maintenance.maintenance_settings') }}</h2>
        
        <fieldset>
            <x-form-toggle
                name="maintenance_mode"
                :label="__('admin/settings/base/maintenance.maintenance_mode')"
                :checked="old('maintenance_mode', $settings['maintenance_mode'])"
                xModel="maintenanceMode"
            />
            <p class="mt-2">{{ __('admin/settings/base/maintenance.maintenance_mode_help') }}</p>
        </fieldset>

        <div>
            <fieldset>
                <legend>{{ __('admin/settings/base/maintenance.maintenance_message') }}</legend>
                <x-form-textarea
                    name="maintenance_message"
                    :value="old('maintenance_message', $settings['maintenance_message'])"
                    :rows="3"
                    class="input-full"
                />
                <p>{{ __('admin/settings/base/maintenance.maintenance_message_help') }}</p>
            </fieldset>

            <fieldset>
                <legend>{{ __('admin/settings/base/maintenance.release_method') }}</legend>
                <x-form-radio-card-group
                    name="maintenance_auto_release"
                    :options="[
                        ['value' => '0', 'label' => __('admin/settings/base/maintenance.manual_release'), 'description' => __('admin/settings/base/maintenance.manual_release_help')],
                        ['value' => '1', 'label' => __('admin/settings/base/maintenance.auto_release'), 'description' => __('admin/settings/base/maintenance.auto_release_help')]
                    ]"
                    :value="old('maintenance_auto_release', $settings['maintenance_auto_release'])"
                    xModel="autoRelease"
                />
            </fieldset>

            <fieldset :class="{ 'opacity-50 pointer-events-none': maintenanceMode === '0' }">
                <legend>{{ __('admin/settings/base/maintenance.schedule_settings') }}</legend>

                <div class="mb-4">
                    <label for="maintenance_start_at">{{ __('admin/settings/base/maintenance.start_at') }}</label>
                    <x-form-text
                        type="datetime-local"
                        name="maintenance_start_at"
                        :value="old('maintenance_start_at', $settings['maintenance_start_at'] ? \Carbon\Carbon::parse($settings['maintenance_start_at'])->format('Y-m-d\TH:i') : '')"
                        x-bind:disabled="maintenanceMode === '0'"
                    />
                    <p class="text-sm text-gray-600 mt-1">{{ __('admin/settings/base/maintenance.start_at_help') }}</p>
                </div>

                <div class="mb-4">
                    <label for="maintenance_release_at">{{ __('admin/settings/base/maintenance.release_at') }}</label>
                    <x-form-text
                        type="datetime-local"
                        name="maintenance_release_at"
                        :value="old('maintenance_release_at', $settings['maintenance_release_at'] ? \Carbon\Carbon::parse($settings['maintenance_release_at'])->format('Y-m-d\TH:i') : '')"
                        x-bind:disabled="maintenanceMode === '0'"
                    />
                    <p class="text-sm text-gray-600 mt-1">{{ __('admin/settings/base/maintenance.release_at_help') }}</p>
                </div>
            </fieldset>

            <fieldset>
                <x-form-button
                    type="button"
                    x-on:click="previewMaintenance()"
                    variant="secondary"
                >
                    {{ __('admin/settings/base/maintenance.preview_button') }}
                </x-form-button>
                <p class="text-sm text-gray-600 mt-2">{{ __('admin/settings/base/maintenance.preview_help') }}</p>
            </fieldset>
        </div>
    </section>

</form>
</div>
@endsection

@push('scripts')
<div id="maintenance-settings"
     data-preview-url="{{ route('admin.settings.base.maintenance.preview') }}"
     data-default-message="{{ __('admin/settings/base/maintenance.default_message') }}"
     style="display:none;"></div>
@endpush

@section('save')
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="base-maintenance-form"
    />
@endsection
