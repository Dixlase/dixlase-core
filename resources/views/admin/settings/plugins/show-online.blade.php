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
<div class="mx-auto max-w-5xl">

    {{-- ヘッダー --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
        {{-- サムネイル（16:9 フル幅バナー） --}}
        <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
            <img
                src="{{ $details['thumbnail_url'] ?? asset('assets/images/plugin-default.svg') }}"
                alt="{{ $details['name'] ?? $details['slug'] }}"
                class="w-full h-full object-cover"
                x-data
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

            {{-- メタ情報（ダウンロード済みページと同一の2カラム表示） --}}
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-sm mb-4">
                @if(! empty($details['author']))
                    <div class="flex gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.author') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $details['author'] }}</dd>
                    </div>
                @endif

                @if(! empty($details['license']))
                    <div class="flex gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.license') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $details['license'] }}</dd>
                    </div>
                @endif

                @if(! empty($details['email']))
                    <div class="flex gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.email') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                            <a href="mailto:{{ $details['email'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $details['email'] }}</a>
                        </dd>
                    </div>
                @endif

                @if(! empty($details['url']))
                    <div class="flex gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.url') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                            <a href="{{ $details['url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $details['url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                            </a>
                        </dd>
                    </div>
                @endif

                <div class="flex gap-3">
                    <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.slug') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $details['slug'] }}</dd>
                </div>

                @if(! empty($details['package_name']))
                    <div class="flex gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.package_name') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $details['package_name'] }}</dd>
                    </div>
                @endif

                @if(! empty($details['namespace']))
                    <div class="flex gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.namespace') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $details['namespace'] }}</dd>
                    </div>
                @endif

                @if(! empty($details['updated_at']))
                    <div class="flex gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.last_updated') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 min-w-0">{{ \Carbon\Carbon::parse($details['updated_at'])->format('Y/m/d H:i') }}</dd>
                    </div>
                @endif

                @if(! empty($details['repository_url']))
                    {{-- リポジトリ URL は長いため全幅で表示 --}}
                    <div class="flex gap-3 md:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.repository') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                            <a href="{{ $details['repository_url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $details['repository_url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                            </a>
                        </dd>
                    </div>
                @endif
            </dl>

            {{-- ダウンロードボタン（共通モーダルで進行状態を表示） --}}
            <form id="download-form" method="POST" action="{{ route('admin.settings.plugins.download-from-source') }}" class="mt-auto">
                @csrf
                <input type="hidden" name="slug" value="{{ $details['slug'] }}">
                <x-form-button
                    type="button"
                    :label="__('admin/settings/plugins/add.online.download')"
                    variant="primary"
                    icon="fas fa-download"
                    class="download-trigger-btn"
                    :data-name="$details['name'] ?? $details['slug']"
                />
            </form>
        </div>
    </div>

    {{-- 戻るボタン --}}
    <div class="mt-6">
        <a href="{{ route('admin.settings.plugins.add') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>{{ __('admin/settings/plugins/show.back_to_add') }}
        </a>
    </div>
</div>
@endsection

@push('modals')
    {{-- ダウンロード中モーダル（ダウンロードボタンクリック時に openModal で開く） --}}
    <x-ui-modal
        id="downloadingPluginModal"
        iconType="loading"
        :title="__('admin/settings/plugins/add.online.downloading_title')"
        :dismissible="false"
        :hideActions="true"
    >
        <div class="modal-message text-center">
            <p id="downloadingPluginName" class="font-medium"></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/settings/plugins/add.online.downloading_wait') }}</p>
        </div>
    </x-ui-modal>
@endpush

@push('scripts')
    <script @cspNonce>
        (function () {
            const btn = document.querySelector('.download-trigger-btn');
            const form = document.getElementById('download-form');
            if (! btn || ! form) {
                return;
            }
            btn.addEventListener('click', function () {
                if (btn.disabled) {
                    return;
                }
                btn.disabled = true;
                const name = btn.dataset.name || '';
                const nameEl = document.getElementById('downloadingPluginName');
                if (nameEl) {
                    nameEl.textContent = name;
                }
                if (typeof window.openModal === 'function') {
                    window.openModal('downloadingPluginModal');
                }
                form.submit();
            });
        })();
    </script>
@endpush
