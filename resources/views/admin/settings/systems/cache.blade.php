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
      (see LICENSE.commercial, or contact info@dixlase.org).

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

    @if(session('error'))
        <x-ui-message
            type="error"
            :message="session('error')"
        />
    @endif

    <section>
        <h2>{{ __('admin/settings/systems/cache.title') }}</h2>
        @foreach($cacheInfo as $type => $info)
        <section class="flex flex-col md:flex-row md:items-center justify-center md:justify-between">
            <div class="flex-1 mb-4 md:mb-0 md:mr-6">
                <h3 class="text-center md:text-left">{{ $info['name'] }}</h3>
                <p class="mb-4">{{ $info['description'] }}</p>
                <div class="flex flex-wrap gap-2">
                    <code class="text-sm rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1">php artisan {{ $info['command'] }}</code>
                    @if($info['rebuildable'])
                        <code class="text-sm rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1">php artisan {{ $info['rebuild_command'] }}</code>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-center md:justify-end flex-shrink-0">
                <form id="clearCacheForm{{ ucfirst($type) }}" action="{{ route('admin.settings.systems.cache.clear') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                </form>

                <x-form-button
                    type="button"
                    variant="danger"
                    :label="__('common.clear')"
                    icon="fas fa-trash"
                    @click="openModal('clearCacheModal{{ ucfirst($type) }}')"
                />

                @if($info['rebuildable'])
                    <form id="rebuildCacheForm{{ ucfirst($type) }}" action="{{ route('admin.settings.systems.cache.rebuild') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                    </form>

                    <x-form-button
                        type="button"
                        variant="primary"
                        :label="__('admin/settings/systems/cache.rebuild')"
                        icon="fas fa-bolt"
                        @click="openModal('rebuildCacheModal{{ ucfirst($type) }}')"
                    />
                @endif
            </div>
        </section>
        @endforeach

        <section class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex-1 mb-4 md:mb-0 md:mr-6">
                <h2>{{ __('admin/settings/systems/cache.clear_all_title') }}</h2>
                <p>{{ __('admin/settings/systems/cache.clear_all_description') }}</p>
                <p>{{ __('common.warning') }}: {{ __('admin/settings/systems/cache.clear_all_warning') }}</p>
            </div>

            <div class="flex-shrink-0">
                <form id="clearAllCacheForm" action="{{ route('admin.settings.systems.cache.clear') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="all">
                </form>

                <x-form-button
                    type="button"
                    variant="danger"
                    :label="__('admin/settings/systems/cache.clear_all_button')"
                    icon="fas fa-trash-alt"
                    @click="openModal('clearAllCacheModal')"
                />
            </div>
        </section>

        <section class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex-1 mb-4 md:mb-0 md:mr-6">
                <h2>{{ __('admin/settings/systems/cache.rebuild_all_title') }}</h2>
                <p>{{ __('admin/settings/systems/cache.rebuild_all_description') }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/cache.rebuild_all_warning') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/settings/systems/cache.rebuild_not_supported') }}</p>
            </div>

            <div class="flex-shrink-0">
                <form id="rebuildAllCacheForm" action="{{ route('admin.settings.systems.cache.rebuild') }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="all">
                </form>

                <x-form-button
                    type="button"
                    variant="primary"
                    :label="__('admin/settings/systems/cache.rebuild_all_button')"
                    icon="fas fa-bolt"
                    @click="openModal('rebuildAllCacheModal')"
                />
            </div>
        </section>

        <section class="info-section">
            <div>
                <h2>{{ __('common.info') }}</h2>
                <dl class="text-sm">
                    <dt class="font-semibold">{{ __('admin/settings/systems/cache.config_cache.name') }}</dt>
                    <dd class="font-normal mb-2">{{ __('admin/settings/systems/cache.info_config') }}</dd>
                    
                    <dt class="font-semibold">{{ __('admin/settings/systems/cache.route_cache.name') }}</dt>
                    <dd class="font-normal mb-2">{{ __('admin/settings/systems/cache.info_route') }}</dd>
                    
                    <dt class="font-semibold">{{ __('admin/settings/systems/cache.view_cache.name') }}</dt>
                    <dd class="font-normal mb-2">{{ __('admin/settings/systems/cache.info_view') }}</dd>
                    
                    <dt class="font-semibold">{{ __('admin/settings/systems/cache.application_cache.name') }}</dt>
                    <dd class="font-normal mb-2">{{ __('admin/settings/systems/cache.info_application') }}</dd>
                </dl>
            </div>
    </section>


<!-- Individual Cache Clear Modals -->
@foreach($cacheInfo as $type => $info)
    <x-ui-modal
        id="clearCacheModal{{ ucfirst($type) }}"
        :title="__('admin/settings/systems/cache.clear_confirm', ['name' => $info['name']])"
        :message="$info['description']"
        :confirm_label="__('common.clear')"
        :cancel_label="__('common.cancel')"
        form="clearCacheForm{{ ucfirst($type) }}"
        icon_type="danger"
        confirm_color="red"
    />
@endforeach

<!-- Individual Cache Rebuild Modals -->
@foreach($cacheInfo as $type => $info)
    @if($info['rebuildable'])
        <x-ui-modal
            id="rebuildCacheModal{{ ucfirst($type) }}"
            :title="__('admin/settings/systems/cache.rebuild_confirm', ['name' => $info['name']])"
            :message="$info['description']"
            :confirm_label="__('admin/settings/systems/cache.rebuild')"
            :cancel_label="__('common.cancel')"
            form="rebuildCacheForm{{ ucfirst($type) }}"
            icon_type="info"
            confirm_color="blue"
        />
    @endif
@endforeach

<!-- Clear All Cache Modal -->
    <x-ui-modal
        id="clearAllCacheModal"
        :title="__('admin/settings/systems/cache.clear_all_title')"
        :message="__('admin/settings/systems/cache.clear_all_description') . ' ' . __('admin/settings/systems/cache.clear_all_warning')"
        :confirm_label="__('admin/settings/systems/cache.clear_all_button')"
        :cancel_label="__('common.cancel')"
        form="clearAllCacheForm"
        icon_type="danger"
        confirm_color="red"
    />

<!-- Rebuild All Cache Modal -->
    <x-ui-modal
        id="rebuildAllCacheModal"
        :title="__('admin/settings/systems/cache.rebuild_all_title')"
        :message="__('admin/settings/systems/cache.rebuild_all_description') . ' ' . __('admin/settings/systems/cache.rebuild_all_warning')"
        :confirm_label="__('admin/settings/systems/cache.rebuild_all_button')"
        :cancel_label="__('common.cancel')"
        form="rebuildAllCacheForm"
        icon_type="info"
        confirm_color="blue"
    />

@endsection
