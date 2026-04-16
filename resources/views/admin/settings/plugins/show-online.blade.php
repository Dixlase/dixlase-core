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
<div class="mx-auto max-w-5xl"
     x-data="{
         downloading: false,
         download() {
             this.downloading = true;
             document.getElementById('download-form').submit();
         }
     }"
>

    {{-- ヘッダー --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
        <div class="grid grid-cols-1 md:grid-cols-[minmax(280px,_1fr)_2fr] gap-0">
            {{-- サムネイル --}}
            <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
                <img
                    src="{{ $details['thumbnail_url'] ?? asset('assets/images/plugin-default.svg') }}"
                    alt="{{ $details['name'] ?? $details['slug'] }}"
                    class="w-full h-full object-cover"
                    x-on:error="$el.src = '{{ asset('assets/images/plugin-default.svg') }}'; $el.onerror = null;"
                >
            </div>

            {{-- 基本情報 --}}
            <div class="p-6 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $details['name'] ?? $details['slug'] }}</h1>
                        <div class="flex items-center gap-2 flex-wrap">
                            @if(! empty($details['version']))
                                <span class="inline-block font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">v{{ $details['version'] }}</span>
                            @endif

                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                <i class="fas fa-cloud-download-alt mr-1"></i>{{ __('admin/settings/plugins/show.online_badge') }}
                            </span>

                            @if(! empty($details['source_name']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $details['source_name'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 短い説明 --}}
                @if(! empty($details['description']))
                    <p class="text-gray-600 dark:text-gray-300 mb-4">{{ $details['description'] }}</p>
                @endif

                {{-- 主要メタ情報 --}}
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-4">
                    @if(! empty($details['author']))
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.author') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200">
                            @if(! empty($details['url']))
                                <a href="{{ $details['url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $details['author'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            @else
                                {{ $details['author'] }}
                            @endif
                        </dd>
                    @endif

                    @if(! empty($details['license']))
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.license') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200">{{ $details['license'] }}</dd>
                    @endif

                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.slug') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs">{{ $details['slug'] }}</dd>

                    @if(! empty($details['package_name']))
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.package_name') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs">{{ $details['package_name'] }}</dd>
                    @endif
                </dl>

                {{-- ダウンロードボタン --}}
                <div class="mt-auto">
                    <form id="download-form" method="POST" action="{{ route('admin.settings.plugins.download-from-source') }}">
                        @csrf
                        <input type="hidden" name="slug" value="{{ $details['slug'] }}">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                            :disabled="downloading"
                            @click="download()"
                        >
                            <template x-if="downloading">
                                <i class="fas fa-spinner fa-spin"></i>
                            </template>
                            <template x-if="! downloading">
                                <i class="fas fa-download"></i>
                            </template>
                            <span x-text="downloading ? '{{ __('admin/settings/plugins/add.online.downloading') }}' : '{{ __('admin/settings/plugins/add.online.download') }}'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- 説明（全文） --}}
    @if(! empty($details['description']))
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ __('admin/settings/plugins/show.sections.description') }}</h2>
            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $details['description'] }}</div>
        </section>
    @endif

    {{-- 詳細情報 --}}
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('admin/settings/plugins/show.sections.details') }}</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            @if(! empty($details['namespace']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('admin/settings/plugins/show.namespace') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all">{{ $details['namespace'] }}</dd>
                </div>
            @endif

            @if(! empty($details['email']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('admin/settings/plugins/show.email') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200">
                        <a href="mailto:{{ $details['email'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $details['email'] }}</a>
                    </dd>
                </div>
            @endif

            @if(! empty($details['url']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('admin/settings/plugins/show.url') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200">
                        <a href="{{ $details['url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                            {{ $details['url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                        </a>
                    </dd>
                </div>
            @endif

            @if(! empty($details['repository_url']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('admin/settings/plugins/show.repository') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200">
                        <a href="{{ $details['repository_url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                            {{ $details['repository_url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                        </a>
                    </dd>
                </div>
            @endif

            @if(! empty($details['updated_at']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('admin/settings/plugins/show.last_updated') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200">{{ \Carbon\Carbon::parse($details['updated_at'])->format('Y/m/d H:i') }}</dd>
                </div>
            @endif
        </dl>
    </section>

    {{-- 戻るボタン --}}
    <div class="mt-6">
        <a href="{{ route('admin.settings.plugins.add') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>{{ __('admin/settings/plugins/show.back_to_add') }}
        </a>
    </div>
</div>
@endsection
