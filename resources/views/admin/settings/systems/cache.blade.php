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

    @php
        // クッキーからフラッシュメッセージを取得（キャッシュクリア後も表示されるように）
        $cookieSuccess = request()->cookie('flash_success');
        $cookieError = request()->cookie('flash_error');
    @endphp

    @if(session('success') || $cookieSuccess)
        <x-ui-message 
            type="success" 
            :message="session('success') ?? $cookieSuccess"
            textSize="text-base font-medium"
        />
    @endif

    @if(session('error') || $cookieError)
        <x-ui-message 
            type="error" 
            :message="session('error') ?? $cookieError"
            textSize="text-base font-medium"
        />
    @endif

    <section>
        <h2>{{ __('admin/settings/systems/cache.title') }}</h2>
        @foreach($cacheInfo as $type => $info)
        <section class="flex flex-col md:flex-row md:items-center justify-center md:justify-between">
            <div class="flex-1 mb-4 md:mb-0 md:mr-6">
                <h3 class="text-center md:text-left">{{ $info['name'] }}</h3>
                <p class="mb-4">{{ $info['description'] }}</p>
                <code class="text-sm rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1">php artisan {{ $info['command'] }}</code>
            </div>
            
            <div class="flex justify-center md:justify-end flex-shrink-0">
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

@endsection
