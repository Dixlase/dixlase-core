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
            />
            <p class="mt-2">{{ __('admin/settings/base/maintenance.maintenance_mode_help') }}</p>
        </fieldset>

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
    </section>

</form>
</div>
@endsection

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
