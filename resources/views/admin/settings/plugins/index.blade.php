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
<div class="mx-auto">
    {{-- インストール直後の有効化バナー --}}
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

    {{-- インストール済みプラグイン一覧セクション --}}
    <section>
        <div class="flex items-center justify-between mb-6">
            <h2 class="mb-0">{{ __('admin/settings/plugins/index.installed_heading') }}</h2>
            <x-form-button
                type="link"
                :href="route('admin.settings.plugins.add')"
                :label="__('admin/settings/plugins/index.add_plugin')"
                variant="primary"
                icon="fas fa-plus"
            />
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

    {{-- アンインストール済みプラグイン一覧セクション --}}
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

{{-- インストール直後の有効化確認モーダル --}}
@if($installedPluginCard)
    @include('admin.settings.plugins.partials.quick-enable-modal', ['card' => $installedPluginCard])
@endif

@endsection

{{-- 監査スクリプト --}}
@include('admin.settings.plugins.partials.audit-script')
