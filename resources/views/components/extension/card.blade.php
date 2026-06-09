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
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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

@props([
    'type' => 'plugin',
    'name' => '',
    'description' => '',
    'version' => '',
    'author' => '',
    'email' => '',
    'url' => '',
    'license' => '',
    'thumbnail' => null,
    'isEnabled' => false,
    'isInstalled' => true,
    'badges' => [],
    'actions' => null,
])

@php
    $defaultThumbnail = $type === 'plugin' 
        ? asset('assets/images/plugin-default.svg')
        : asset('assets/images/theme-default.svg');
    $thumbnailUrl = $thumbnail ?: $defaultThumbnail;
    
    // author が配列の場合の処理
    $authorName = $author;
    $authorEmail = $email;
    $authorUrl = $url;
    if (is_array($author)) {
        $authorEmail = $author['email'] ?? $email;
        $authorUrl = $author['url'] ?? $author['homepage'] ?? $url;
        $authorName = $author['name'] ?? null;
    }
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow duration-200 flex flex-col']) }}>
    {{-- Thumbnail --}}
    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
        <img 
            src="{{ $thumbnailUrl }}" 
            alt="{{ $name }}" 
            class="w-full h-full object-cover"
            onerror="this.src='{{ $defaultThumbnail }}'"
        >
        {{-- Status badge (overlay) --}}
        <div class="absolute top-2 right-2">
            @if($isInstalled)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $isEnabled ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                    <i class="fas {{ $isEnabled ? 'fa-check-circle' : 'fa-pause-circle' }} mr-1"></i>
                    {{ $isEnabled ? __('common.enabled') : __('common.disabled') }}
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-500 text-white">
                    <i class="fas fa-download mr-1"></i>
                    {{ __('common.not_installed') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="p-4 flex-1 flex flex-col">
        {{-- Title and version --}}
        <div class="flex items-start justify-between gap-2 mb-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white line-clamp-1">{{ $name }}</h3>
            <span class="flex-shrink-0 inline-block font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                v{{ $version }}
            </span>
        </div>

        {{-- Description --}}
        @if($description)
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">{{ $description }}</p>
        @endif

        {{-- Badges --}}
        @if(!empty($badges))
            <div class="flex flex-wrap gap-1 mb-3">
                {{ $badges }}
            </div>
        @endif

        {{-- Author information --}}
        <div class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                <i class="fas fa-user mr-2"></i>
                @if($authorName)
                    <span>{{ $authorName }}</span>
                @else
                    <span class="text-gray-400">{{ __('common.unknown') }}</span>
                @endif
            </div>
            @if($license)
                <div class="flex items-center text-xs text-gray-400 dark:text-gray-500 mt-1">
                    <i class="fas fa-balance-scale mr-2"></i>
                    <span>{{ $license }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions --}}
    @if($actions)
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
            {{ $actions }}
        </div>
    @endif
</div>
