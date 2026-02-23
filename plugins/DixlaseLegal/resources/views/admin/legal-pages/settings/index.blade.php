{{--
This file is part of Dixlase Legal.

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
    <form id="legal-settings-form" action="{{ route('dixlase-legal::admin.legal-pages.settings.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="space-y-6">
            {{-- クッキー同意バナー --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ __('dixlase-legal::admin/legal-pages/settings.cookie_consent_heading') }}
                </h3>
                <x-form-toggle
                    name="cookie_consent_enabled"
                    :label="__('dixlase-legal::admin/legal-pages/settings.cookie_consent_label')"
                    :checked="old('cookie_consent_enabled', $cookieConsentEnabled)"
                    value="1"
                />
                <x-form-help-text :text="__('dixlase-legal::admin/legal-pages/settings.cookie_consent_help')" />
            </div>
        </div>
    </form>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmLegalSettingsModal"
        :label="__('common.save')"
        :title="__('dixlase-legal::admin/legal-pages/settings.confirm_title')"
        :message="__('dixlase-legal::admin/legal-pages/settings.confirm_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="legal-settings-form"
    />
@endsection
