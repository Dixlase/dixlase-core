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
    {{-- Banner for activation immediately after installation --}}
    @if($installedPluginCard)
        <div class="mb-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 mr-2"></i>
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ __('admin/settings/plugins/index.messages.enable_prompt', ['name' => $installedPluginCard['translatedName']]) }}
                    </span>
                </div>
                <x-form-button
                    type="button"
                    :label="__('common.enable')"
                    variant="success"
                    size="xs"
                    icon="fas fa-play"
                    class="two-stage-action-btn"
                    data-action-type="enable"
                    data-needs-scan="{{ $installedPluginCard['needsScan'] ? '1' : '0' }}"
                    data-plugin-slug="{{ $installedPluginCard['slug'] }}"
                    data-plugin-name="{{ $installedPluginCard['translatedName'] }}"
                    data-form-id="quickEnableForm"
                    data-enable-action="{{ $installedPluginCard['enableAction'] }}"
                    data-health-score="{{ $installedPluginCard['healthScore'] ?? '' }}"
                    data-health-status="{{ $installedPluginCard['healthStatus'] ?? '' }}"
                    data-health-issues="{{ json_encode($installedPluginCard['healthIssues'] ?? []) }}"
                />
            </div>
        </div>
    @endif

    {{-- Page-level action bar: lives above the section so the section header
         reads as a heading, not a button toolbar. Right-aligned so the
         buttons sit under the breadcrumb / description on the admin
         layout, matching other settings pages. --}}
    <div class="flex flex-wrap items-center justify-end gap-3 mb-6">
        {{-- Link to update management page (with count badge if updates available) --}}
        <a href="{{ route('admin.settings.systems.updates.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
            <i class="fas fa-cloud-arrow-down"></i>
            {{ __('admin/navigation.settings.systems.updates') }}
            @if(count($updatableExtensions) > 0)
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-bold bg-blue-600 text-white rounded-full">{{ count($updatableExtensions) }}</span>
            @endif
        </a>

        {{-- Rescan all plugins button --}}
        <form action="{{ route('admin.settings.plugins.audit-all') }}" method="POST" class="inline-block" id="bulkAuditPluginsForm">
            @csrf
            <x-form-button
                type="button"
                :label="__('admin/settings/plugins/index.audit.audit_all_button')"
                variant="secondary"
                icon="fas fa-search"
                @click="openModal('bulkAuditPluginsModal')"
            />

            <x-ui-modal
                id="bulkAuditPluginsModal"
                :title="__('admin/settings/plugins/index.audit.audit_all_confirm_title')"
                :message="__('admin/settings/plugins/index.audit.audit_all_confirm_message')"
                icon_type="info"
                confirm_color="blue"
                :confirm_label="__('admin/settings/plugins/index.audit.audit_all_button')"
                :cancel_label="__('common.cancel')"
                form="bulkAuditPluginsForm"
            />
        </form>

        {{-- All update operations are consolidated in the integrated update management page --}}

        <x-form-button
            type="link"
            :href="route('admin.settings.plugins.add')"
            :label="__('admin/settings/plugins/index.add_plugin')"
            variant="primary"
            icon="fas fa-plus"
        />
    </div>

    {{-- Installed plugin list section --}}
    <section>
        <div class="mb-6">
            <h2 class="mb-0">{{ __('admin/settings/plugins/index.installed_heading') }}</h2>
        </div>

        @if($plugins->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($pluginCards as $card)
                    @include('admin.settings.plugins.partials.plugin-card', ['card' => $card])
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="max-w-md mx-auto">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <i class="fas fa-puzzle-piece text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('admin/settings/plugins/index.no_plugins') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('admin/settings/plugins/index.no_plugins_description') }}</p>
                    <x-form-button
                        type="link"
                        :href="route('admin.settings.plugins.add')"
                        :label="__('admin/settings/plugins/index.add_plugin')"
                        variant="primary"
                        icon="fas fa-plus"
                    />
                </div>
            </div>
        @endif
    </section>

    {{-- Uninstalled plugin list section --}}
    @if(count($uninstalledPlugins) > 0)
    <section class="mt-12">
        <h2>{{ __('admin/settings/plugins/index.uninstalled_heading') }}</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('admin/settings/plugins/index.uninstalled_description') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($uninstalledPluginCards as $card)
                @include('admin.settings.plugins.partials.plugin-card', ['card' => $card])
            @endforeach
        </div>
    </section>
    @endif
</div>

{{-- Activation confirmation modal immediately after installation --}}
@if($installedPluginCard)
    @include('admin.settings.plugins.partials.quick-enable-modal', ['card' => $installedPluginCard])
@endif

@endsection

{{-- Audit script --}}
@include('admin.settings.plugins.partials.audit-script')
