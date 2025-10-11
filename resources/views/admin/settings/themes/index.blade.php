{{--
This file is part of MySoftware.

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

@extends('admin::partials.layout')

@section('content')
    <!-- テーマ一覧 -->
    <div class="max-w-4xl mx-auto mt-12">
        <h2 class="text-2xl font-bold mb-4">{{ __('admin.settings.themes.index.available_themes') }}</h2>

        <!-- デフォルトテーマ -->
        @if ($defaultTheme)
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-4 relative">
                <h3 class="text-lg font-bold mb-2">{{ $defaultTheme->name }} <span class="text-sm text-gray-500">({{ $defaultTheme->version }})</span></h3>

                <div class="mt-4 flex items-center space-x-2">
                    @if ($defaultTheme->id === $activeThemeId)
                        <span class="text-green-500 font-semibold">{{ __('admin.settings.themes.index.currently_active') }}</span>
                    @else
                        <!-- 有効化ボタン -->
                        <form action="{{ route('admin.contents.themes.activate', $defaultTheme->id) }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit" onclick="return confirm('{{ __('admin.settings.themes.index.activate_confirm') }}')" class="text-white bg-blue-500 hover:bg-blue-600 font-medium rounded-lg text-sm px-4 py-2">
                                {{ __('admin.settings.themes.index.activate_button') }}
                            </button>
                        </form>
                    @endif

                    <!-- 設定ボタン -->
                    @if ($defaultTheme->has_settings ?? false)
                        <a href="{{ route('admin.settings.themes.settings') }}" 
                           class="text-white bg-gray-600 hover:bg-gray-700 font-medium rounded-lg text-sm px-4 py-2">
                            {{ __('admin.settings.themes.index.settings_button') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <!-- 他のテーマ一覧 -->
        <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2">
            @foreach ($themes as $theme)
                <div class="bg-white  dark:bg-gray-800 shadow-md rounded-lg p-6 relative">
                    <h3 class="text-lg font-bold mb-2">{{ $theme->name }} <span class="text-sm text-gray-500">({{ $theme->version }})</span></h3>

                    <div class="mt-4 flex items-center space-x-2">
                        @if ($theme->id === $activeThemeId)
                            <span class="text-green-500 font-semibold">{{ __('admin.settings.themes.index.currently_active') }}</span>
                        @else
                            <!-- 有効化ボタン -->
                            <form action="{{ route('admin.contents.themes.activate', $theme->id) }}" method="POST" class="inline-block">
                                @csrf
                                <button type="submit" onclick="return confirm('{{ __('admin.settings.themes.index.activate_confirm') }}')" class="text-white bg-blue-500 hover:bg-blue-600 font-medium rounded-lg text-sm px-4 py-2">
                                    {{ __('admin.settings.themes.index.activate_button') }}
                                </button>
                            </form>
                            <!-- 削除ボタン -->
                            <form action="{{ route('admin.contents.themes.delete', $theme->slug) }}" method="POST" class="inline-block">
                                @csrf
                                <button type="submit" onclick="return confirm('{{ __('admin.settings.themes.index.delete_confirm') }}')" class="text-white bg-red-500 hover:bg-red-600 font-medium rounded-lg text-sm px-4 py-2">
                                    {{ __('admin.settings.themes.index.delete_button') }}
                                </button>
                            </form>
                        @endif

                        <!-- 設定ボタン -->
                        @if ($theme->has_settings ?? false)
                            <a href="{{ route('admin.settings.themes.settings') }}" 
                               class="text-white bg-gray-600 hover:bg-gray-700 font-medium rounded-lg text-sm px-4 py-2">
                                {{ __('admin.settings.themes.index.settings_button') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- ページネーション -->
        <div class="mt-6">
            {{ $themes->links('pagination::tailwind') }}
        </div>
    </div>

@endsection
