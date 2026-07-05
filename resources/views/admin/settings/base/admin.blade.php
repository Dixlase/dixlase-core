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
    @if($modeData['isGuideOnly'] ?? false)
        <x-admin.mode-guide-banner />
    @endif
<form id="base-admin-form" action="{{ route('admin.settings.base.admin.update') }}" method="POST">
    @csrf
    <fieldset {{ ($modeData['isGuideOnly'] ?? false) ? 'disabled' : '' }}>

    <!-- 管理画面設定 -->
    <section>
        <h2>{{ __('admin/settings/base/admin.admin_panel_settings') }}</h2>

        <fieldset x-data="{
            prefix: '{{ old('admin_url_prefix', $settings['admin_url_prefix']) }}',
            suffix: '{{ old('admin_url_suffix', $settings['admin_url_suffix']) }}',
            baseUrl: '{{ url('/') }}',
            get fullUrl() { return this.baseUrl + '/' + this.prefix + '-' + this.suffix; },
            copied: false,
            copyUrl() {
                navigator.clipboard.writeText(this.fullUrl);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            }
        }">
            <legend>{{ __('admin/settings/base/admin.admin_url') }}</legend>
            <div class="flex items-center">
                <span class="p-2 bg-gray-200 dark:bg-gray-700 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg text-gray-700 dark:text-gray-300 text-sm whitespace-nowrap">{{ url('/') }}/</span>
                <x-form-select
                    id="admin_url_prefix"
                    name="admin_url_prefix"
                    :options="$prefixes"
                    :value="old('admin_url_prefix', $settings['admin_url_prefix'])"
                    :useDefaultClass="false"
                    class="p-2 bg-gray-50 dark:bg-gray-800 border-y border-gray-300 dark:border-gray-500 text-sm dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    xModel="prefix"
                />
                <span class="p-2 bg-gray-200 dark:bg-gray-700 border-y border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">-</span>
                <x-form-text
                    name="admin_url_suffix"
                    id="admin_url_suffix"
                    :value="old('admin_url_suffix', $settings['admin_url_suffix'])"
                    :required="true"
                    class="input-lg rounded-l-none"
                    x-model="suffix"
                />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                URL: <span x-text="fullUrl"></span>
                <button type="button" @click="copyUrl()" class="ml-1 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    <i class="far" :class="copied ? 'fa-check-circle text-green-500 dark:text-green-400' : 'fa-copy'"></i>
                </button>
            </p>
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

        <fieldset>
            <x-form-textarea
                name="admin_login_notice"
                :label="__('admin/settings/base/admin.login_notice')"
                :value="old('admin_login_notice', $settings['admin_login_notice'])"
                rows="3"
            />
            <p class="mt-2">{{ __('admin/settings/base/admin.login_notice_help') }}</p>
            <x-form-error field="admin_login_notice" />
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
