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
<div class="mx-auto">
<form id="base-site-form" action="{{ route('admin.settings.base.site.update') }}" method="POST">
    @csrf

    <!-- サイト設定 -->
    <section>
        <h2>{{ __('admin/settings/base/site.site_settings') }}</h2>

        <fieldset>
            <legend>{{ __('admin/settings/base/site.app_name') }}</legend>
            <x-form-text
                name="app_name"
                :value="old('app_name', $settings['app_name'])"
                :required="true"
                maxlength="60"
                class="input-full"
            />
            <x-form-help-text :text="__('admin/settings/base/site.app_name_help')" />
        </fieldset>

        @unless ($seoPluginEnabled)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-6 mt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin/settings/base/site.ogp_seo_plugin_notice') }}
                </p>
                <a href="{{ route('admin.settings.plugins.add') }}"
                    class="inline-flex items-center mt-3 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                    <i class="fas fa-plus-circle mr-2"></i>
                    {{ __('admin/settings/base/site.ogp_seo_plugin_install_link') }}
                </a>
            </div>
        @endunless
    </section>

    <!-- 言語・地域設定 -->
    <section>
        <h2>{{ __('admin/settings/base/site.language_region_settings') }}</h2>

        <fieldset>
            <legend>{{ __('admin/settings/base/site.locale') }}</legend>
            <x-form-select
                name="locale"
                :options="$locales"
                :value="old('locale', $settings['locale'])"
                :required="true"
            />
        </fieldset>

        <fieldset>
            <legend>{{ __('common.timezone') }}</legend>
            <x-form-select
                name="timezone"
                :options="$timezones"
                :value="$settings['timezone']"
            />
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
        form="base-site-form"
    />
@endsection
