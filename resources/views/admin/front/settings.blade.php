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
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">{{ __('admin.settings.front.heading') }}</h1>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.front.settings.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- OGP設定 -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">{{ __('admin.settings.front.ogp_settings') }}</h2>

            <fieldset class="mb-6">
                <legend class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('admin.settings.front.front_ogp_image') }}
                </legend>
                
                <!-- 隠しフィールド -->
                <input type="hidden" id="front_ogp_image_id" name="front_ogp_image_id" value="{{ old('front_ogp_image_id', $settings['front_ogp_image_id']) }}">
                
                <!-- プレビュー表示 -->
                <div id="front_ogp_image_preview" class="mb-4">
                    @if($frontOgpImage)
                        <div class="relative inline-block">
                            <img src="{{ asset('storage/media/' . $frontOgpImage->path) }}" 
                                 alt="{{ $frontOgpImage->name }}" 
                                 class="w-64 h-auto object-cover rounded border border-gray-300 dark:border-gray-600">
                            <button type="button" 
                                    onclick="removeMediaPreview('front_ogp_image_id', 'front_ogp_image_preview')" 
                                    class="absolute -top-2 -right-2 w-8 h-8 bg-red-600 text-white rounded-full hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif
                </div>
                
                <!-- 選択ボタン -->
                <button type="button" 
                        onclick="openMediaSelector('frontOgpImageSelector', 'front_ogp_image_id', 'front_ogp_image_preview', false)"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-image mr-2"></i>{{ __('admin.settings.front.select_ogp_image') }}
                </button>
                
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.settings.front.front_ogp_image_help') }}</p>
                
                @error('front_ogp_image_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </fieldset>
        </section>

        <!-- 保存ボタン -->
        <div class="flex justify-end">
            <button type="submit" 
                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500">
                <i class="fas fa-save mr-2"></i>{{ __('common.save') }}
            </button>
        </div>
    </form>
</div>

<!-- メディア選択モーダル -->
@include('components.media-selector', [
    'id' => 'frontOgpImageSelector',
    'inputId' => 'front_ogp_image_id',
    'previewId' => 'front_ogp_image_preview',
    'multiple' => false
])
@endsection
