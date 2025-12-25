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
    {{-- インストール済みプラグイン一覧セクション --}}
    <section>
        <div class="flex items-center justify-between mb-6">
            <h2 class="mb-0">{{ __('admin/settings/plugins.index.installed_heading') }}</h2>
            <a href="{{ route('admin.settings.plugins.add') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>
                {{ __('admin/settings/plugins.index.add_plugin') }}
            </a>
        </div>

        @if($plugins->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($plugins as $plugin)
                    @include('admin.settings.plugins.partials.plugin-card', ['plugin' => $plugin])
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="max-w-md mx-auto">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <i class="fas fa-puzzle-piece text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('admin/settings/plugins.index.no_plugins') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('admin/settings/plugins.index.no_plugins_description') }}</p>
                    <a href="{{ route('admin.settings.plugins.add') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        {{ __('admin/settings/plugins.index.add_plugin') }}
                    </a>
                </div>
            </div>
        @endif
    </section>

    {{-- アンインストール済みプラグイン一覧セクション --}}
    @if(count($uninstalledPlugins) > 0)
    <section class="mt-12">
        <h2>{{ __('admin/settings/plugins.index.uninstalled_heading') }}</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('admin/settings/plugins.index.uninstalled_description') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($uninstalledPlugins as $plugin)
                @include('admin.settings.plugins.partials.plugin-card', ['plugin' => $plugin])
            @endforeach
        </div>
    </section>
    @endif
</div>

@endsection

{{-- 監査スクリプト --}}
@include('admin.settings.plugins.partials.audit-script')
