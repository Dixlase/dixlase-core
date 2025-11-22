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
<div class="max-w-4xl mx-auto">
    <!-- インストール済みテーマ一覧セクション -->
    <section>
        <h2>{{ __('admin.settings.themes.index.installed_heading') }}</h2>

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.themes.index.table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('admin.settings.themes.index.table.name') }}</th>
                        <th>{{ __('common.details') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($themes as $theme)
                        <tr>
                            <td data-label="{{ __('common.id') }}">
                                <span class="text-sm font-mono px-2 py-1 rounded">{{ $theme->id }}</span>
                            </td>
                            <td data-label="{{ __('admin.settings.themes.index.table.name') }}">
                                <div>
                                    <strong class="text-lg">{{ $theme->name }}</strong>
                                    @if($theme->description)
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $theme->description }}</div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="{{ __('common.details') }}">
                                <div class="text-sm space-y-1">
                                    <!-- 作者情報 -->
                                    <div class="mb-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('common.author') }}:</span>
                                        @if($theme->author)
                                            <span>{{ $theme->author }}</span>
                                            @if($theme->email)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $theme->email }}</div>
                                            @endif
                                            @if($theme->web)
                                                <div class="text-xs">
                                                    <a href="{{ $theme->web }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $theme->web }}</a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">{{ __('common.unknown') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- バージョン情報 -->
                                    <div class="inline-block font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $theme->version }}</div>
                                    <!-- ライセンス情報 -->
                                    <div>
                                        @if($theme->license)
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">{{ $theme->license }}</div>
                                        @else
                                            <div class="inline-block text-gray-400">{{ __('common.unknown') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('common.status') }}">
                                @if($theme->id === $activeThemeId)
                                    <span class="status-badge status-badge--enabled">
                                        {{ __('common.enabled') }}
                                    </span>
                                @else
                                    <span class="status-badge status-badge--disabled">
                                        {{ __('common.disabled') }}
                                    </span>
                                @endif
                            </td>
                            <td data-label="{{ __('common.actions') }}">
                                <div class="action-buttons">
                                    @if($theme->id === $activeThemeId)
                                        <!-- 有効化中のテーマ：設定ボタンのみ -->
                                        @if($theme->has_settings ?? false)
                                            <a href="{{ route('admin.settings.themes.settings') }}" class="inline-block">
                                                <x-form.button
                                                    type="button"
                                                    label="設定"
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fas fa-cog"
                                                />
                                            </a>
                                        @endif
                                    @else
                                        <!-- 有効化ボタン -->
                                        <form action="{{ route('admin.settings.themes.activate', $theme->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            <x-form.button
                                                type="submit"
                                                :label="__('common.enable')"
                                                variant="success"
                                                size="sm"
                                                icon="fas fa-check"
                                            />
                                        </form>

                                        <!-- アンインストールボタン（デフォルトテーマ以外） -->
                                        @php
                                            $defaultThemeSlug = config('themes.default_theme_slug', 'dixlase-default-theme');
                                        @endphp
                                        @if($theme->slug !== $defaultThemeSlug)
                                            <form action="{{ route('admin.settings.themes.uninstall', $theme->id) }}" method="POST" class="inline-block" id="uninstallThemeForm-{{ $theme->id }}">
                                                @csrf
                                                <x-form.button
                                                    type="button"
                                                    :label="__('common.uninstall')"
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fas fa-times"
                                                    onclick="openModal('uninstallThemeModal-{{ $theme->id }}')"
                                                />

                                                <!-- 確認画面のモーダル -->
                                                <x-modal
                                                    id="uninstallThemeModal-{{ $theme->id }}"
                                                    :title="__('admin.settings.themes.index.uninstall.confirm_title')"
                                                    :message="str_replace('{name}', $theme->name, __('admin.settings.themes.index.uninstall.confirm_message'))"
                                                    :confirm_label="__('common.uninstall')"
                                                    :cancel_label="__('common.cancel')"
                                                    form="uninstallThemeForm-{{ $theme->id }}"
                                                    icon_type="danger"
                                                    confirm_color="red"
                                                />
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8">
                                <div class="empty-state">
                                    <i class="fas fa-palette text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500">{{ __('admin.settings.themes.index.no_themes') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- アンインストール済みテーマ一覧セクション -->
    @if(count($uninstalledThemes) > 0)
    <section class="mt-8">
        <h2>{{ __('admin.settings.themes.index.uninstalled_heading') }}</h2>
        
        {{-- デバッグ情報 --}}
        @if(config('app.debug'))
        <div class="mb-4 p-4 bg-yellow-100 dark:bg-yellow-900 rounded">
            <p class="font-bold">デバッグ情報:</p>
            <p>インストール済みテーマ数: {{ count($themes) }}</p>
            <p>アンインストール済みテーマ数: {{ count($uninstalledThemes) }}</p>
            <details class="mt-2">
                <summary class="cursor-pointer">インストール済みディレクトリ一覧</summary>
                <pre class="mt-2 text-xs">{{ json_encode($themes->pluck('directory')->toArray(), JSON_PRETTY_PRINT) }}</pre>
            </details>
            <details class="mt-2">
                <summary class="cursor-pointer">アンインストール済みディレクトリ一覧</summary>
                <pre class="mt-2 text-xs">{{ json_encode(collect($uninstalledThemes)->pluck('directory')->toArray(), JSON_PRETTY_PRINT) }}</pre>
            </details>
        </div>
        @endif

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.themes.index.uninstalled_table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('admin.settings.themes.index.table.name') }}</th>
                        <th>{{ __('common.details') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($uninstalledThemes as $theme)
                        <tr>
                            <td data-label="{{ __('admin.settings.themes.index.table.name') }}">
                                <div>
                                    <strong class="text-lg">{{ $theme['name'] }}</strong>
                                    @if($theme['description'])
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $theme['description'] }}</div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="{{ __('common.details') }}">
                                <div class="text-sm space-y-1">
                                    <!-- 作者情報 -->
                                    <div class="mb-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('common.author') }}:</span>
                                        @if($theme['author'])
                                            <span>{{ $theme['author'] }}</span>
                                            @if($theme['email'])
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $theme['email'] }}</div>
                                            @endif
                                            @if($theme['url'])
                                                <div class="text-xs">
                                                    <a href="{{ $theme['url'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $theme['url'] }}</a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">{{ __('common.unknown') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- バージョン情報 -->
                                    <div class="inline-block font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $theme['version'] }}</div>
                                    <!-- ライセンス情報 -->
                                    <div>
                                        @if($theme['license'])
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">{{ $theme['license'] }}</div>
                                        @else
                                            <div class="inline-block text-gray-400">{{ __('common.unknown') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('common.actions') }}">
                                <div class="action-buttons">
                                    <!-- インストールボタン -->
                                    <form action="{{ route('admin.settings.themes.install-from-directory') }}" method="POST" class="inline-block" id="installThemeForm-{{ $theme['directory'] }}">
                                        @csrf
                                        <input type="hidden" name="directory" value="{{ $theme['directory'] }}">
                                        <x-form.button
                                            type="button"
                                            :label="__('common.install')"
                                            variant="success"
                                            size="sm"
                                            icon="fas fa-download"
                                            onclick="openModal('installThemeModal-{{ $theme['directory'] }}')"
                                        />

                                        <!-- インストール確認モーダル -->
                                        <x-modal
                                            id="installThemeModal-{{ $theme['directory'] }}"
                                            :title="__('admin.settings.themes.index.install.confirm_title')"
                                            :message="str_replace('{name}', $theme['name'], __('admin.settings.themes.index.install.confirm_message'))"
                                            :confirm_label="__('common.install')"
                                            :cancel_label="__('common.cancel')"
                                            form="installThemeForm-{{ $theme['directory'] }}"
                                            icon_type="info"
                                            confirm_color="green"
                                        />
                                    </form>

                                    <!-- 削除ボタン（デフォルトテーマ以外） -->
                                    @php
                                        $defaultThemeSlug = config('themes.default_theme_slug', 'dixlase-default-theme');
                                    @endphp
                                    @if($theme['slug'] !== $defaultThemeSlug)
                                        <form action="{{ route('admin.settings.themes.delete-directory') }}" method="POST" class="inline-block" id="deleteThemeForm-{{ $theme['directory'] }}">
                                            @csrf
                                            <input type="hidden" name="directory" value="{{ $theme['directory'] }}">
                                            <x-form.button
                                                type="button"
                                                :label="__('common.delete')"
                                                variant="danger"
                                                size="sm"
                                                icon="fas fa-trash"
                                                onclick="openModal('deleteThemeModal-{{ $theme['directory'] }}')"
                                            />

                                            <!-- 削除確認モーダル -->
                                            <x-modal
                                                id="deleteThemeModal-{{ $theme['directory'] }}"
                                                :title="__('admin.settings.themes.index.delete.confirm_title')"
                                                :message="str_replace('{name}', $theme['name'], __('admin.settings.themes.index.delete.confirm_message'))"
                                                :confirm_label="__('common.delete')"
                                                :cancel_label="__('common.cancel')"
                                                form="deleteThemeForm-{{ $theme['directory'] }}"
                                                icon_type="danger"
                                                confirm_color="red"
                                            />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>
@endsection
