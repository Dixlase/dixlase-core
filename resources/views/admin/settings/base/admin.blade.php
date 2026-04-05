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
    @if($modeData['isGuideOnly'] ?? false)
        <x-admin.mode-guide-banner />
    @endif
<form id="base-admin-form" action="{{ route('admin.settings.base.admin.update') }}" method="POST">
    @csrf
    <fieldset {{ ($modeData['isGuideOnly'] ?? false) ? 'disabled' : '' }}>

    <!-- 管理画面設定 -->
    <section>
        <h2>{{ __('admin/settings/base/admin.admin_panel_settings') }}</h2>

        <fieldset>
            <legend>{{ __('admin/settings/base/admin.admin_url') }}</legend>
            <div class="flex items-center">
                <x-form-select
                    id="admin_url_prefix"
                    name="admin_url_prefix"
                    :options="$prefixes"
                    :value="old('admin_url_prefix', $settings['admin_url_prefix'])"
                    class="input-lg rounded-r-none"
                />
                <span class="p-2 bg-gray-200 dark:bg-gray-700 border-y border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">-</span>
                <x-form-text
                    name="admin_url_suffix"
                    id="admin_url_suffix"
                    :value="old('admin_url_suffix', $settings['admin_url_suffix'])"
                    :required="true"
                    class="input-lg rounded-l-none"
                />
            </div>
            <x-form-error field="admin_url_prefix" />
            <x-form-error field="admin_url_suffix" />
            <x-form-error field="admin_url" />
            <p>{!! __('admin/settings/base/admin.admin_url_help') !!}</p>
        </fieldset>

        <fieldset>
            <x-form-toggle
                name="force_ssl"
                :label="__('admin/settings/base/admin.force_ssl')"
                :checked="old('force_ssl', $settings['force_ssl'])"
            />
            <p class="mt-2">{{ __('admin/settings/base/admin.force_ssl_help') }}</p>
        </fieldset>
    </section>

    </fieldset>
</form>
</div>
@endsection

@section('save')
@if($modeData['isEditable'] ?? true)
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="base-admin-form"
    />
@endif
@endsection
