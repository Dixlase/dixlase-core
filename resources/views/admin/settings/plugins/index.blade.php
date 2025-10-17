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
    <!-- プラグイン一覧セクション -->
    <section>
        <h2>{{ __('admin.settings.plugins.index.heading') }}</h2>

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.plugins.index.table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('admin.settings.plugins.index.table.name') }}</th>
                        <th>{{ __('common.details') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plugins as $plugin)
                        <tr>
                            <td data-label="{{ __('admin.settings.plugins.index.table.id') }}">
                                <span class="text-sm font-mono px-2 py-1 rounded">{{ $plugin->id }}</span>
                            </td>
                            <td data-label="{{ __('admin.settings.plugins.index.table.name') }}">
                                <div>
                                    <strong class="text-lg">{{ $plugin->translated_name }}</strong>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $plugin->translated_description }}</div>
                                </div>
                            </td>
                            <td data-label="{{ __('common.details') }}">
                                <div class="text-sm space-y-1">
                                    <!-- 作者情報 -->
                                    <div class="mb-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('common.author') }}:</span>
                                        @if($plugin->author)
                                            <span>{{ $plugin->author }}</span>
                                            @if($plugin->email)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $plugin->email }}</div>
                                            @endif
                                            @if($plugin->web)
                                                <div class="text-xs">
                                                    <a href="{{ $plugin->web }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $plugin->web }}</a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">{{ __('common.unknown') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- バージョン情報 -->
                                    <div class="inline-block font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $plugin->version }}</div>
                                    <!-- ライセンス情報 -->
                                    <div>
                                        @if($plugin->license)
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">{{ $plugin->license }}</div>
                                        @else
                                            <div class="inline-block text-gray-400">{{ __('common.unknown') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('common.status') }}">
                                <span class="status-badge status-badge--{{ $plugin->status === 1 ? 'enabled' : 'disabled' }}">
                                    {{ $plugin->status === 1 ? __('common.enabled') : __('common.disabled') }}
                                </span>
                            </td>
                            <td data-label="{{ __('common.actions') }}">
                                <div class="action-buttons">
                                    <!-- 設定画面リンク -->
                                    @if ($plugin->status === 1 && isset($plugin->has_settings) && $plugin->has_settings)
                                        @php
                                            $settingsUrl = app('App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController')->getPluginSettingsUrl($plugin);
                                        @endphp
                                        @if ($settingsUrl)
                                            <a href="{{ $settingsUrl }}" class="inline-block">
                                                @include('components::form.button', [
                                                    'type' => 'button',
                                                    'label' => '設定',
                                                    'variant' => 'primary',
                                                    'size' => 'sm',
                                                    'icon' => 'fas fa-cog'
                                                ])
                                            </a>
                                        @endif
                                    @endif

                                    @if ($plugin->status === 1)
                                        <form action="{{ route('admin.settings.plugins.disable', $plugin->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @include('components::form.button', [
                                                'type' => 'submit',
                                                'label' => __('common.disable'),
                                                'variant' => 'warning',
                                                'size' => 'sm',
                                                'icon' => 'fas fa-pause'
                                            ])
                                        </form>
                                    @else
                                        <form action="{{ route('admin.settings.plugins.enable', $plugin->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @include('components::form.button', [
                                                'type' => 'submit',
                                                'label' => __('common.enable'),
                                                'variant' => 'success',
                                                'size' => 'sm',
                                                'icon' => 'fas fa-play'
                                            ])
                                        </form>
                                    @endif

                                    <form action="{{ route('admin.settings.plugins.uninstall', $plugin->id) }}" method="POST" class="inline-block" id="uninstallForm-{{ $plugin->id }}">
                                        @csrf
                                        @include('components::form.button', [
                                            'type' => 'button',
                                            'label' => __('common.delete'),
                                            'variant' => 'danger',
                                            'size' => 'sm',
                                            'icon' => 'fas fa-trash',
                                            'onclick' => "openModal('uninstallModal-{$plugin->id}')"
                                        ])

                                        <!-- 確認画面のモーダル -->
                                        @include('components.modal', [
                                            'id' => "uninstallModal-{$plugin->id}",
                                            'title' => __('admin.settings.plugins.index.uninstall.confirm_title'),
                                            'message' => str_replace('{name}', $plugin->name, __('admin.settings.plugins.index.uninstall.confirm_message')),
                                            'confirm_label' => __('common.uninstall'),
                                            'cancel_label' => __('common.cancel'),
                                            'checkbox' => true,
                                            'checkbox_name' => 'remove_db_data',
                                            'checkbox_label' => __('admin.settings.plugins.index.uninstall.remove_data_checkbox'),
                                            'form' => "uninstallForm-{$plugin->id}",
                                            'icon_type' => 'danger',
                                            'confirm_color' => 'red'
                                        ])
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8">
                                <div class="empty-state">
                                    <i class="fas fa-puzzle-piece text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500">{{ __('admin.settings.plugins.index.no_plugins') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
