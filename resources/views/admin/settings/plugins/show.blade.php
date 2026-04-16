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

    {{-- 戻るリンク（上部） --}}
    <div class="mb-4">
        <a href="{{ route('admin.settings.plugins.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>{{ __('admin/settings/plugins/show.back_to_list') }}
        </a>
    </div>

    {{-- ヘッダー --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
        {{-- サムネイル（16:9 フル幅バナー） --}}
        <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
            <img
                src="{{ $card['thumbnailUrl'] }}"
                alt="{{ $card['name'] }}"
                class="w-full h-full object-cover"
                x-on:error="$el.src = '{{ asset('assets/images/plugin-default.svg') }}'; $el.onerror = null;"
            >
        </div>

        {{-- 基本情報 --}}
        <div class="p-6 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $card['name'] }}</h1>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-block font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">v{{ $card['version'] }}</span>

                            @if($isInstalled)
                                @if($card['isEnabled'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        <i class="fas fa-check-circle mr-1"></i>{{ __('common.enabled') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        <i class="fas fa-pause-circle mr-1"></i>{{ __('common.disabled') }}
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                    <i class="fas fa-download mr-1"></i>{{ __('common.not_installed') }}
                                </span>
                            @endif

                            @if($card['hasUpdateAvailable'] ?? false)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    <i class="fas fa-arrow-up mr-1"></i>v{{ $card['availableVersion'] }} {{ __('admin/settings/plugins/show.update_available') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 短い説明 --}}
                @if(! empty($card['description']))
                    <p class="text-gray-600 dark:text-gray-300 mb-4">{{ $card['description'] }}</p>
                @endif

                {{-- メタ情報（2カラム表示で横幅を活用） --}}
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2 text-sm mb-4">
                    @if(! empty($card['authorName']))
                        <div class="flex gap-3">
                            <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.author') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $card['authorName'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($card['license']))
                        <div class="flex gap-3">
                            <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.license') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $card['license'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($rawData['email']))
                        <div class="flex gap-3">
                            <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.email') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                                <a href="mailto:{{ $rawData['email'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $rawData['email'] }}</a>
                            </dd>
                        </div>
                    @endif

                    @if(! empty($rawData['url']))
                        <div class="flex gap-3">
                            <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.url') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                                <a href="{{ $rawData['url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $rawData['url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            </dd>
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.slug') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $card['slug'] }}</dd>
                    </div>

                    @if(! empty($card['directory']))
                        <div class="flex gap-3">
                            <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.directory') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $card['directory'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($rawData['package_name']))
                        <div class="flex gap-3">
                            <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.package_name') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $rawData['package_name'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($rawData['namespace']))
                        <div class="flex gap-3">
                            <dt class="text-gray-500 dark:text-gray-400 min-w-[6rem] flex-shrink-0">{{ __('admin/settings/plugins/show.namespace') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $rawData['namespace'] }}</dd>
                        </div>
                    @endif
                </dl>

                {{-- アクションボタン --}}
                {{-- アクションボタン（一覧ページと同じ2段階モーダルフローを使用） --}}
                <div class="mt-auto flex flex-wrap gap-2">
                    @if($isInstalled)
                        @include('admin.settings.plugins.partials.installed-actions', ['card' => $card])
                    @else
                        @include('admin.settings.plugins.partials.uninstalled-actions', ['card' => $card])
                    @endif
                </div>

                {{-- バッジモーダル（2段階フローでスキャン結果を表示する際に使用） --}}
                @if($card['permissionSummary'])
                    @include('admin.settings.plugins.partials.permission-modal', ['card' => $card])
                @endif
        </div>
    </div>

    {{-- スキャン結果（常に表示、未スキャン時もスキャンボタンを提供） --}}
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3 leading-none">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white m-0 leading-none">{{ __('admin/settings/plugins/show.sections.scan_result') }}</h2>
                    <x-form-button
                        type="button"
                        :label="empty($card['auditedAt']) ? __('admin/settings/plugins/show.scan.scan') : __('admin/settings/plugins/show.scan.rescan')"
                        :variant="empty($card['auditedAt']) ? 'warning' : 'secondary'"
                        size="sm"
                        icon="fas fa-sync-alt"
                        class="audit-btn"
                        :data-slug="$card['slug']"
                    />
                </div>
                @if($card['auditedAtFormatted'])
                    <span class="text-xs text-gray-500 dark:text-gray-400 leading-none">{{ __('admin/settings/plugins/show.last_scanned_at', ['date' => $card['auditedAtFormatted']]) }}</span>
                @endif
            </div>

            {{-- 未スキャン時のメッセージ --}}
            @if(empty($card['auditedAt']))
                <div class="mb-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-center">
                    <i class="fas fa-info-circle text-2xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/plugins/show.scan.not_scanned_message') }}</p>
                </div>
            @endif

            {{-- スキャン結果（統一順序: 健全性 → 署名 → 権限定義 → CSP → 拡張 → 権限情報） --}}
            @if($card['permissionSummary'] || $card['healthScore'] !== null)
                @include('admin.settings.plugins.partials.scan-details', ['card' => $card])
            @endif
    </section>

    {{-- 戻るボタン --}}
    <div class="mt-6">
        <a href="{{ route('admin.settings.plugins.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>{{ __('admin/settings/plugins/show.back_to_list') }}
        </a>
    </div>
</div>

{{-- スキャン関連のモーダル・設定スクリプト（一覧ページと共有） --}}
@include('admin.settings.plugins.partials.audit-script')
@endsection
