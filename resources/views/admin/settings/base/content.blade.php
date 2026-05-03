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
      (see LICENSE.commercial, or contact office@exc-d.com).

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
<form id="base-content-form" action="{{ route('admin.settings.base.content.update') }}" method="POST">
    @csrf

    <section>
        <h2>{{ __('admin/settings/base/content.revision_settings') }}</h2>

        <fieldset>
            <legend>{{ __('admin/settings/base/content.revision_retention_count') }}</legend>
            <x-form-text
                name="revision_retention_count"
                type="number"
                :value="old('revision_retention_count', $settings['revision_retention_count'])"
                :min="0"
                :max="$maxRetention"
                :required="true"
            />
            <p>{{ __('admin/settings/base/content.revision_retention_count_help', ['default' => $defaultRetention, 'max' => $maxRetention]) }}</p>
            <p>{{ __('admin/settings/base/content.revision_retention_count_zero_help') }}</p>
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
        form="base-content-form"
    />
@endsection
