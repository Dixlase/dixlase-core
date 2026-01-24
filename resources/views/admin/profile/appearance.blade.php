{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
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

    <form method="POST" action="{{ route('admin.profile.appearance.update') }}" id="profile-appearance-form"
          x-data="appearanceForm()"
          x-init="init()"
          data-success="{{ session('success') ? '1' : '' }}"
          data-appearance="{{ old('appearance', (string) ($member->appearance->value ?? 0)) }}">
        @csrf

        <!-- 外観設定 -->
        @php
            $appearanceValue = old('appearance', (string) ($member->appearance->value ?? 0));
        @endphp

        <section class="transition-colors duration-[500ms]">
            <h2>{{ __('common.appearance_settings') }}</h2>
            <div class="lg:w-1/2">
                <x-appearance-mode-selector
                    name="appearance"
                    :value="$appearanceValue"
                    :enableRealtimeSwitch="true"
                    :columns="3"
                    color="primary"
                    variant="filled"
                    :showCheck="true"
                />
            </div>
        </section>

    </form>

@endsection

@section('save')
    <x-save
        id_confirmation="confirmProfileAppearanceModal"
        :label="__('common.update')"
        :title="__('admin/profile.confirm_title')"
        :message="__('admin/profile.confirm_message')"
        :confirm_label="__('common.update')"
        :cancel_label="__('common.cancel')"
        form="profile-appearance-form"
    />
@endsection
