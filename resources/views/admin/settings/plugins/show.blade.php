{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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

@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-5xl">

    {{-- 戻るリンク（上部） --}}
    <div class="mb-4">
        <a href="{{ route('admin.settings.plugins.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>{{ __('admin/settings/plugins/show.back_to_list') }}
        </a>
    </div>

    {{-- ヘッダー（拡張機能詳細カード共通コンポーネント） --}}
    <x-admin.extension-detail
        :title="$card['name']"
        :version="$card['version']"
        :description="$card['description']"
        :thumbnail-url="$card['thumbnailUrl']"
        :fallback-thumbnail-url="asset('assets/images/plugin-default.svg')"
    >
        <x-slot:badges>
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
                <a href="{{ route('admin.settings.systems.updates.index', ['target' => 'plugin:' . $card['slug']]) }}"
                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                    <i class="fas fa-arrow-up"></i>v{{ $card['availableVersion'] }} {{ __('admin/settings/plugins/show.update_available') }}
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            @endif
        </x-slot:badges>

        <x-slot:metadata>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                {{-- 左カラム: 作者 → ライセンス → Email → URL --}}
                <div class="space-y-2">
                    @if(! empty($card['authorName']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.author') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $card['authorName'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($card['license']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.license') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $card['license'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($rawData['email']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.email') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                                <a href="mailto:{{ $rawData['email'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $rawData['email'] }}</a>
                            </dd>
                        </div>
                    @endif

                    @if(! empty($rawData['url']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.url') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                                <a href="{{ $rawData['url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $rawData['url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            </dd>
                        </div>
                    @endif
                </div>

                {{-- 右カラム: 名前空間 → スラッグ → パッケージ名 → ディレクトリ --}}
                <div class="space-y-2">
                    @if(! empty($rawData['namespace']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.namespace') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $rawData['namespace'] }}</dd>
                        </div>
                    @endif

                    <div class="flex items-start gap-3">
                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.slug') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $card['slug'] }}</dd>
                    </div>

                    @if(! empty($rawData['package_name']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.package_name') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $rawData['package_name'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($card['directory']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.directory') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $card['directory'] }}</dd>
                        </div>
                    @endif
                </div>
            </dl>
        </x-slot:metadata>

        <x-slot:actions>
            {{-- 更新は統合アップデート管理ページに集約（個別ボタンは廃止） --}}

            {{-- アクションボタン（一覧ページと同じ2段階モーダルフローを使用） --}}
            @if($isInstalled)
                @include('admin.settings.plugins.partials.installed-actions', ['card' => $card])
            @else
                @include('admin.settings.plugins.partials.uninstalled-actions', ['card' => $card])
            @endif
        </x-slot:actions>
    </x-admin.extension-detail>

    {{-- バッジモーダル（2段階フローでスキャン結果を表示する際に使用） --}}
    @if($card['permissionSummary'])
        @include('admin.settings.plugins.partials.permission-modal', ['card' => $card])
    @endif

    {{-- スキャン結果（常に表示、未スキャン時もスキャンボタンを提供） --}}
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin/settings/plugins/show.sections.scan_result') }}</span>
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
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.last_scanned_at', ['date' => $card['auditedAtFormatted']]) }}</span>
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
