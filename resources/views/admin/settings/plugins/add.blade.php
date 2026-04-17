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
<div class="mx-auto">

    {{-- エラー・フラッシュメッセージは管理レイアウトの <x-ui-flash-message /> で表示 --}}

    <!-- タブ切り替え -->
    <div x-data="{ activeTab: '{{ old('_tab', 'online') }}' }">
        <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6">
            <button
                type="button"
                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                :class="activeTab === 'online'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                @click="activeTab = 'online'"
            >
                <i class="fas fa-globe mr-1.5"></i>
                {{ __('admin/settings/plugins/add.tab_online') }}
            </button>
            <button
                type="button"
                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors"
                :class="activeTab === 'zip'
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                @click="activeTab = 'zip'"
            >
                <i class="fas fa-file-archive mr-1.5"></i>
                {{ __('admin/settings/plugins/add.tab_zip') }}
            </button>
        </div>

        <!-- ZIPファイルから追加 -->
        <div x-show="activeTab === 'zip'" x-cloak>
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-6 text-gray-700 dark:text-white">{{ __('admin/settings/plugins/add.upload_title') }}</h2>
                <form
                    action="{{ route('admin.settings.plugins.upload') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-6"
                    x-data="{ fileName: '' }"
                    @submit="
                        const nameEl = document.getElementById('uploadingPluginName');
                        if (nameEl) { nameEl.textContent = fileName; }
                        if (typeof window.openModal === 'function') { window.openModal('uploadingPluginModal'); }
                    "
                >
                    @csrf

                    <div class="flex flex-col gap-2">
                        <label for="plugin_file" class="text-gray-600 dark:text-gray-300 font-medium">
                            {{ __('admin/settings/plugins/add.file_select_label') }}
                        </label>
                        <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition duration-300 max-w-full">
                            <input
                                type="file"
                                name="plugin_file"
                                id="plugin_file"
                                accept=".zip"
                                class="absolute top-0 left-0 w-full h-full opacity-0 cursor-pointer"
                                @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''"
                                required
                            >
                            <div class="flex flex-col items-center justify-center text-center pointer-events-none">
                                <svg class="w-12 h-12 text-blue-500 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v4m0 0H8m4 0h4m-4-4a4 4 0 01-4-4 4 4 0 014-4 4 4 0 014 4 4 4 0 01-4 4z"></path>
                                </svg>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="fileName || '{{ __('admin/settings/plugins/add.drag_drop_text') }}'"></p>
                                <p class="text-xs text-gray-400 mt-1">{{ __('admin/settings/plugins/add.supported_format') }} <strong>.zip</strong></p>
                                <p class="text-xs text-gray-400 mt-1">{{ __('admin/settings/plugins/add.upload_limit') }} <strong>{{ $uploadMaxMB }} MB</strong></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow-md transition duration-300">
                            {{ __('common.upload') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- オンラインから追加 -->
        <div x-show="activeTab === 'online'" x-cloak>
            <div x-data="onlinePlugins({
                     listUrl: '{{ route('admin.settings.plugins.available-from-source') }}',
                     downloadUrl: '{{ route('admin.settings.plugins.download-from-source') }}',
                     csrfToken: '{{ csrf_token() }}',
                     defaultThumbnail: '{{ asset('assets/images/plugin-default.svg') }}'
                 })"
            >
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                    <h2 class="text-2xl font-bold mb-2 text-gray-700 dark:text-white">{{ __('admin/settings/plugins/add.online.title') }}</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('admin/settings/plugins/add.online.description') }}</p>

                    <!-- ローディング -->
                    <div x-show="status === 'loading'" class="py-12 text-center">
                        <i class="fas fa-spinner fa-spin text-2xl text-blue-500 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/add.online.loading') }}</p>
                    </div>

                    <!-- エラー -->
                    <div x-show="status === 'error'" x-cloak class="py-8">
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                                <i class="fas fa-exclamation-circle"></i>
                                <span class="font-medium">{{ __('admin/settings/plugins/add.online.connection_error') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errorMessage"></p>
                        </div>
                    </div>

                    <!-- 空 -->
                    <div x-show="status === 'empty'" x-cloak class="py-12 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <i class="fas fa-box-open text-3xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/add.online.no_plugins') }}</p>
                    </div>

                    <!-- プラグイン一覧（プラグインカード風） -->
                    <div x-show="status === 'loaded'" x-cloak>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <template x-for="plugin in plugins" :key="plugin.slug">
                                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all duration-200 flex flex-col group">
                                    {{-- サムネイル --}}
                                    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
                                        <img
                                            :src="plugin.thumbnail_url || '{{ asset('assets/images/plugin-default.svg') }}'"
                                            :alt="plugin.name || plugin.slug"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            x-on:error="$el.src = '{{ asset('assets/images/plugin-default.svg') }}'; $el.onerror = null;"
                                        >
                                        {{-- バージョンバッジ --}}
                                        <div class="absolute top-3 right-3">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shadow-sm bg-blue-500 text-white" x-show="plugin.version">
                                                <span x-text="'v' + plugin.version"></span>
                                            </span>
                                        </div>
                                    </div>

                                    {{-- コンテンツ --}}
                                    <div class="p-4 flex-1 flex flex-col">
                                        {{-- タイトル --}}
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white line-clamp-1 mb-2" :title="plugin.name || plugin.slug" x-text="plugin.name || plugin.slug"></h3>

                                        {{-- 説明 --}}
                                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3" x-show="plugin.description" x-text="plugin.description"></p>
                                        <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-3" x-show="!plugin.description">{{ __('common.no_description') }}</p>

                                        {{-- 作者 & ライセンス --}}
                                        <div class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                                            <div class="flex items-center gap-1.5" x-show="plugin.author">
                                                <i class="fas fa-user text-[10px]"></i>
                                                <span x-text="plugin.author"></span>
                                            </div>
                                            <div class="flex items-center gap-1.5" x-show="plugin.license">
                                                <i class="fas fa-certificate text-[10px]"></i>
                                                <span x-text="plugin.license"></span>
                                            </div>
                                        </div>

                                        {{-- 詳細ボタン --}}
                                        <div class="mt-3 flex justify-center">
                                            <a
                                                :href="'{{ route('admin.settings.plugins.show-online', ['slug' => '__SLUG__']) }}'.replace('__SLUG__', plugin.slug)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                                            >
                                                <i class="fas fa-info-circle"></i>
                                                {{ __('admin/settings/plugins/index.view_details') }}
                                            </a>
                                        </div>

                                        {{-- ダウンロードボタン --}}
                                        <div class="mt-3 flex justify-center">
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                                                :disabled="downloadingSlug !== null"
                                                @click="download(plugin)"
                                            >
                                                <i class="fas fa-download"></i>
                                                {{ __('admin/settings/plugins/add.online.download') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
    {{-- ダウンロード中モーダル（onlinePlugins.download() から openModal で呼び出す） --}}
    <x-ui-modal
        id="downloadingPluginModal"
        iconType="loading"
        :title="__('admin/settings/plugins/add.online.downloading_title')"
        message=""
        :dismissible="false"
        :hideActions="true"
    >
        <div class="modal-message text-center">
            <p id="downloadingPluginName" class="font-medium"></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/settings/plugins/add.online.downloading_wait') }}</p>
        </div>
    </x-ui-modal>

    {{-- アップロード中モーダル（ZIP アップロードフォームの @submit から openModal で呼び出す） --}}
    <x-ui-modal
        id="uploadingPluginModal"
        iconType="loading"
        :title="__('admin/settings/plugins/add.uploading_title')"
        message=""
        :dismissible="false"
        :hideActions="true"
    >
        <div class="modal-message text-center">
            <p id="uploadingPluginName" class="font-medium"></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/settings/plugins/add.uploading_wait') }}</p>
        </div>
    </x-ui-modal>
@endpush
