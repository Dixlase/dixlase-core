{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-media.picker />

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

@props([
    'id' => 'media_picker',
    'name' => 'media_id',
    'label' => null,
    'value' => null,
    'media' => null,
    'help' => null,
    'required' => false,
    'error' => null,
    'aspectRatio' => 'original', // 'original', 'ogp' (1.91:1), 'square' (1:1), '16:9', '4:3', 'hero' (21:9)
    'buttonText' => null, // ボタンのテキスト（指定しない場合はデフォルト）
    'confirmUploadNavigation' => false, // アップロード画面遷移時に確認モーダルを表示するか
    'allowedTypes' => null, // 許可するMIMEタイプ（null=デフォルト画像のみ）
])

@php
    $inputId = $name;
    $previewId = $name . '_preview';
    $selectorId = $name . '_selector';
    
    // アスペクト比に応じたクラスを設定
    $aspectClasses = match($aspectRatio) {
        'ogp' => 'aspect-[1.91/1] object-cover',
        'square' => 'aspect-square object-cover',
        '16:9' => 'aspect-video object-cover',
        '4:3' => 'aspect-[4/3] object-cover',
        'hero' => 'aspect-[21/9] object-cover',
        'original' => 'h-auto object-contain',
        default => 'h-auto object-contain',
    };
@endphp

<div class="mb-4">
    @if($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-600">*</span>
            @endif
        </label>
    @endif
    
    <!-- 隠しフィールド -->
    <input type="hidden" id="{{ $inputId }}" name="{{ $name }}" value="{{ old($name, $value) }}">
    
    <!-- プレビュー表示 -->
    <div id="{{ $previewId }}" class="mb-4">
        @if($media)
            <div class="relative inline-block">
                @if(str_starts_with($media->type ?? '', 'video/'))
                    <div class="w-64 {{ $aspectClasses }} rounded border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <div class="text-center p-4">
                            <i class="fas fa-video text-3xl text-gray-400 dark:text-gray-500 mb-2"></i>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]">{{ $media->name }}</p>
                        </div>
                    </div>
                @else
                    <img src="{{ asset('storage/media/' . $media->path) }}"
                         alt="{{ $media->name }}"
                         class="w-64 {{ $aspectClasses }} rounded border border-gray-300 dark:border-gray-600">
                @endif
                <button type="button"
                        @click="removeMediaPreview('{{ $inputId }}', '{{ $previewId }}')"
                        class="absolute -top-2 -right-2 w-8 h-8 bg-red-600 text-white rounded-full hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                        title="{{ __('common.delete') }}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif
    </div>
    
    <!-- 選択ボタン -->
    <button type="button" 
            @click="openMediaSelector('{{ $selectorId }}', '{{ $inputId }}', '{{ $previewId }}', false, '{{ $aspectRatio }}')"
            class="px-4 py-2 mb-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <i class="fas fa-image mr-2"></i>{{ $buttonText ?? __('admin/settings/base/select_ogp_image') }}
    </button>
    
    @if($help)
        <p class="text-sm">{{ $help }}</p>
    @endif
    
    @if($error)
        <p class="mt-4 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>

<!-- メディア選択モーダル -->
@push('modals')
    <x-media.selector
        :id="$selectorId"
        :inputId="$inputId"
        :previewId="$previewId"
        :multiple="false"
        :confirmUploadNavigation="$confirmUploadNavigation"
        :allowedTypes="$allowedTypes ?? ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']"
    />
@endpush
