{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-admin.extension-detail />

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
    'title',                    // 見出し（プラグイン名・テーマ名）
    'version' => null,          // バージョン文字列（v プレフィックスなし）
    'description' => null,      // 説明文
    'thumbnailUrl' => null,     // サムネイル画像 URL
    'fallbackThumbnailUrl' => null, // サムネイル取得失敗時のフォールバック URL
])

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
    {{-- サムネイル（16:9 フル幅バナー） --}}
    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
        <img
            src="{{ $thumbnailUrl ?: $fallbackThumbnailUrl }}"
            alt="{{ $title }}"
            class="w-full h-full object-cover"
            x-data
            @if($fallbackThumbnailUrl)
                x-on:error="$el.src = '{{ $fallbackThumbnailUrl }}'; $el.onerror = null;"
            @endif
        >
    </div>

    {{-- 基本情報 --}}
    <div class="p-6 flex flex-col">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $title }}</h1>
                <div class="flex items-center gap-2 flex-wrap">
                    @if($version)
                        <span class="inline-block font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">v{{ $version }}</span>
                    @endif
                    {{ $badges ?? '' }}
                </div>
            </div>
        </div>

        @if(! empty($description))
            <p class="text-gray-600 dark:text-gray-300 mb-4">{{ $description }}</p>
        @endif

        @isset($metadata)
            {{-- メタ情報（2カラム表示で横幅を活用） --}}
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-sm mb-4">
                {{ $metadata }}
            </dl>
        @endisset

        @isset($actions)
            {{-- アクションボタン --}}
            <div class="mt-auto flex flex-wrap gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
