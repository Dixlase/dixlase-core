{{--
This file is part of Your Software Name.

Copyright (C) 2024 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@php
// テーマの設定を取得
$theme = config('admin.theme');
$theme_class_header = $theme == 'dark' ? 'bg-gray-900 text-white' : 'bg-white text-gray-900';
@endphp

<x-admin-layout :title="__($title)">
    <!-- メインコンテンツ -->
    <div class="{{ $theme_class }} w-full min-h-screen py-12">
        <div>
            <!-- コンテンツ部分 -->
            <div class="w-full sm:px-6 lg:px-8">
                <div class="{{ $theme_class_header }} overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        {{ __("You're logged in!") }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
