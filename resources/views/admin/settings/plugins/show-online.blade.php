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

    {{-- ヘッダー（拡張機能詳細カード共通コンポーネント） --}}
    <x-admin.extension-detail
        :title="$details['name'] ?? $details['slug']"
        :version="$details['version'] ?? null"
        :description="$details['description'] ?? null"
        :thumbnail-url="$details['thumbnail_url'] ?? null"
        :fallback-thumbnail-url="asset('assets/images/plugin-default.svg')"
    >
        <x-slot:badges>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                <i class="fas fa-cloud-download-alt mr-1"></i>{{ __('admin/settings/plugins/show.online_badge') }}
            </span>

            @if(! empty($details['source_name']))
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    {{ $details['source_name'] }}
                </span>
            @endif
        </x-slot:badges>

        <x-slot:metadata>
            {{-- リポジトリ・最終更新（説明直下、全幅で表示） --}}
            @if(! empty($details['repository_url']) || ! empty($details['updated_at']))
                <dl class="space-y-2 mb-4">
                    @if(! empty($details['repository_url']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.repository') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                                <a href="{{ $details['repository_url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $details['repository_url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            </dd>
                        </div>
                    @endif

                    @if(! empty($details['updated_at']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.last_updated') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0">{{ \Carbon\Carbon::parse($details['updated_at'])->format('Y/m/d H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            @endif

            {{-- 2カラム：左=人/権利系 / 右=技術識別子系 --}}
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                {{-- 左カラム: 作者 → ライセンス → Email → URL --}}
                <div class="space-y-2">
                    @if(! empty($details['author']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.author') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $details['author'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($details['license']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.license') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $details['license'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($details['email']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.email') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                                <a href="mailto:{{ $details['email'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $details['email'] }}</a>
                            </dd>
                        </div>
                    @endif

                    @if(! empty($details['url']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.url') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                                <a href="{{ $details['url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $details['url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            </dd>
                        </div>
                    @endif
                </div>

                {{-- 右カラム: 名前空間 → スラッグ → パッケージ名 --}}
                <div class="space-y-2">
                    @if(! empty($details['namespace']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.namespace') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $details['namespace'] }}</dd>
                        </div>
                    @endif

                    <div class="flex items-start gap-3">
                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.slug') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $details['slug'] }}</dd>
                    </div>

                    @if(! empty($details['package_name']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.package_name') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $details['package_name'] }}</dd>
                        </div>
                    @endif
                </div>
            </dl>
        </x-slot:metadata>

        <x-slot:actions>
            {{-- ダウンロードボタン（共通モーダルで進行状態を表示） --}}
            <form id="download-form" method="POST" action="{{ route('admin.settings.plugins.download-from-source') }}">
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
        </x-slot:actions>
    </x-admin.extension-detail>

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
        message=""
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
