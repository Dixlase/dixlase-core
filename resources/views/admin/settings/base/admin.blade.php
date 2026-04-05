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
    </section>

    <!-- コンテンツエディター設定 -->
    <section>
        <h2>{{ __('admin/settings/base/admin.content_editor_settings') }}</h2>

        <fieldset>
            <legend>{{ __('admin/settings/base/admin.preferred_gui_editor') }}</legend>

            @if(count($guiEditors) === 0)
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle text-lg"></i>
                        <p class="text-sm">{{ __('admin/settings/base/admin.no_gui_editor_available') }}</p>
                    </div>
                </div>
            @elseif(count($guiEditors) === 1)
                <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <i class="{{ $guiEditors[0]->icon }} text-lg text-blue-600 dark:text-blue-400"></i>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $guiEditors[0]->label }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $guiEditors[0]->description }}</div>
                    </div>
                </div>
                <input type="hidden" name="preferred_gui_editor" value="{{ $guiEditors[0]->pluginSlug }}">
                <p class="mt-2">{{ __('admin/settings/base/admin.gui_editor_auto') }}</p>
            @else
                <div class="space-y-3">
                    @foreach($guiEditors as $editor)
                        <label class="relative flex cursor-pointer rounded-lg border p-4 shadow-sm focus:outline-none transition-all duration-150
                            {{ old('preferred_gui_editor', $settings['preferred_gui_editor']) === $editor->pluginSlug
                                ? 'border-blue-600 dark:border-blue-500 ring-2 ring-blue-600 dark:ring-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-500' }}">
                            <input type="radio"
                                   name="preferred_gui_editor"
                                   value="{{ $editor->pluginSlug }}"
                                   class="sr-only"
                                   {{ old('preferred_gui_editor', $settings['preferred_gui_editor']) === $editor->pluginSlug ? 'checked' : '' }}>
                            <span class="flex flex-1">
                                <span class="flex flex-col">
                                    <span class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white">
                                        <i class="{{ $editor->icon }}"></i>
                                        {{ $editor->label }}
                                    </span>
                                    <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $editor->description }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif

            <x-form-error field="preferred_gui_editor" />
            <p class="mt-2">{{ __('admin/settings/base/admin.preferred_gui_editor_help') }}</p>
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
