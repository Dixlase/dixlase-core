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

        <div :class="{ 'opacity-50 pointer-events-none': maintenanceMode === '0' }">
            <!-- Hidden inputs to preserve settings when disabled -->
            <template x-if="maintenanceMode === '0'">
                <div>
                    <input type="hidden" name="maintenance_message" value="{{ old('maintenance_message', $settings['maintenance_message']) }}">
                    <input type="hidden" name="maintenance_auto_release" value="{{ old('maintenance_auto_release', $settings['maintenance_auto_release']) }}">
                    <input type="hidden" name="maintenance_start_at" value="{{ old('maintenance_start_at', $settings['maintenance_start_at']) }}">
                    <input type="hidden" name="maintenance_release_at" value="{{ old('maintenance_release_at', $settings['maintenance_release_at']) }}">
                </div>
            </template>

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
                    :selected="old('maintenance_auto_release', $settings['maintenance_auto_release'])"
                />
            </fieldset>

            <fieldset>
                <legend>{{ __('admin/settings/base/maintenance.schedule_settings') }}</legend>
                
                <div class="mb-4">
                    <label for="maintenance_start_at">{{ __('admin/settings/base/maintenance.start_at') }}</label>
                    <x-form-text
                        type="datetime-local"
                        name="maintenance_start_at"
                        :value="old('maintenance_start_at', $settings['maintenance_start_at'] ? \Carbon\Carbon::parse($settings['maintenance_start_at'])->format('Y-m-d\TH:i') : '')"
                        x-bind:disabled="autoRelease === '0'"
                    />
                    <p class="text-sm text-gray-600 mt-1">{{ __('admin/settings/base/maintenance.start_at_help') }}</p>
                </div>

                <div class="mb-4">
                    <label for="maintenance_release_at">{{ __('admin/settings/base/maintenance.release_at') }}</label>
                    <x-form-text
                        type="datetime-local"
                        name="maintenance_release_at"
                        :value="old('maintenance_release_at', $settings['maintenance_release_at'] ? \Carbon\Carbon::parse($settings['maintenance_release_at'])->format('Y-m-d\TH:i') : '')"
                        x-bind:disabled="autoRelease === '0'"
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
<script nonce="{{ csp_nonce() }}">
// ラジオボタン変更時にAlpineデータを更新
document.addEventListener('alpine:init', () => {
    document.querySelectorAll('input[name="maintenance_auto_release"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const container = document.querySelector('[x-data]');
            if (container && container.__x) {
                container.__x.$data.autoRelease = e.target.value;
            }
        });
    });
});

// プレビュー機能をグローバルスコープに定義
window.previewMaintenance = function() {
    const message = document.querySelector('[name="maintenance_message"]').value;
    const releaseAt = document.querySelector('[name="maintenance_release_at"]').value;
    
    const params = new URLSearchParams({
        message: message || '{{ __('admin/settings/base/maintenance.default_message') }}'
    });
    
    if (releaseAt) {
        params.append('release_at', releaseAt);
    }
    
    window.open('{{ route('admin.settings.base.maintenance.preview') }}?' + params.toString(), '_blank', 'width=800,height=600');
};
</script>
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
