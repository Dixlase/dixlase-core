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
<form id="base-admin-form" action="{{ route('admin.settings.base.admin.update') }}" method="POST">
    @csrf

    <!-- 管理画面設定 -->
    <section>
        <h2>{{ __('admin.settings.base.admin.admin_panel_settings') }}</h2>

        <fieldset>
            <legend>{{ __('admin.settings.base.admin.admin_url') }}</legend>
            <x-form.text
                name="admin_url"
                :value="old('admin_url', $settings['admin_url'])"
                :required="true"
                class="input-lg"
            />
            <p>{!! __('admin.settings.base.admin.admin_url_help') !!}</p>
        </fieldset>

        <fieldset>
            <x-form.toggle
                name="force_ssl"
                :label="__('admin.settings.base.admin.force_ssl')"
                :checked="old('force_ssl', $settings['force_ssl'])"
            />
            <p class="mt-2">{{ __('admin.settings.base.admin.force_ssl_help') }}</p>
        </fieldset>
    </section>

</form>
</div>
@endsection

@section('save')
    <x-save
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="base-admin-form"
    />
@endsection
