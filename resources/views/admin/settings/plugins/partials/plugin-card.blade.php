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

<div x-data class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all duration-200 flex flex-col group">
    {{-- サムネイル --}}
    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
        <img
            src="{{ $card['thumbnailUrl'] }}"
            alt="{{ $card['name'] }}"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            x-on:error="$el.src='{{ asset('assets/images/plugin-default.svg') }}'"
        >
        {{-- ステータスバッジ（オーバーレイ） --}}
        <div class="absolute top-3 right-3">
            @if($card['isInstalled'])
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shadow-sm {{ $card['isEnabled'] ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                    <i class="fas {{ $card['isEnabled'] ? 'fa-check-circle' : 'fa-pause-circle' }} mr-1"></i>
                    {{ $card['isEnabled'] ? __('common.enabled') : __('common.disabled') }}
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shadow-sm bg-yellow-500 text-white">
                    <i class="fas fa-download mr-1"></i>
                    {{ __('common.not_installed') }}
                </span>
            @endif
        </div>
        {{-- ID表示（インストール済みのみ） --}}
        @if($card['isInstalled'])
        <div class="absolute top-3 left-3">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-black/50 text-white">
                #{{ $card['id'] }}
            </span>
        </div>
        @endif
    </div>

    {{-- コンテンツ --}}
    <div class="p-4 flex-1 flex flex-col">
        {{-- タイトルとバージョン --}}
        <div class="flex items-start justify-between gap-2 mb-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white line-clamp-1" title="{{ $card['name'] }}">{{ $card['name'] }}</h3>
            <span class="flex-shrink-0 inline-block font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                v{{ $card['version'] }}
            </span>
        </div>

        {{-- アップデート通知（クリックで統合アップデート管理ページへ） --}}
        @if($card['hasUpdateAvailable'] ?? false)
            <a href="{{ route('admin.settings.systems.updates.index', ['target' => 'plugin:' . $card['slug']]) }}"
               class="flex items-center justify-between mb-2 px-2 py-1 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded text-xs text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                <span class="flex items-center gap-1.5">
                    <i class="fas fa-arrow-up"></i>
                    {{ __('admin/settings/plugins/index.update_available', ['version' => $card['availableVersion']]) }}
                </span>
                <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
        @endif

        {{-- 説明 --}}
        @if($card['description'])
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">{{ $card['description'] }}</p>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-3">{{ __('common.no_description') }}</p>
        @endif

        {{-- 詳細ボタン --}}
        <div class="mb-3 flex justify-center">
            <x-form-button
                type="link"
                :href="route('admin.settings.plugins.show', $card['slug'])"
                :label="__('admin/settings/plugins/index.view_details')"
                variant="secondary"
                size="xs"
                icon="fas fa-info-circle"
                class="py-2 px-3"
            />
        </div>

        {{-- バッジ類 --}}
        @if($card['permissionSummary'])
        <div class="mb-3 pt-3 border-t border-gray-100 dark:border-gray-700 space-y-2"
             data-scan-data="{{ json_encode($card['scanData'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
             data-plugin-name="{{ $card['name'] }}">

            @if($isSimpleMode ?? false)
                {{-- === 簡単モード: 健全 + 動作の2行のみ === --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="{{ $card['simpleHealthIcon'] }} mr-1 {{ $card['simpleHealthIconColor'] }}"></i>{{ __('admin/settings/plugins/index.badge_labels.health') }}</span>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $card['simpleHealthBadgeColor'] }}">
                        {{ $card['simpleHealthLabel'] }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="{{ $card['simpleOperationIcon'] }} mr-1 {{ $card['simpleOperationIconColor'] }}"></i>{{ __('admin/settings/plugins/index.badge_labels.operation') }}</span>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $card['simpleOperationBadgeColor'] }}">
                        {{ $card['simpleOperationLabel'] }}
                    </span>
                </div>
            @else
                {{-- === 通常モード: 全7行 === --}}

                {{-- 健全性 --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="{{ $card['badgeIcon'] }} mr-1 {{ $card['healthIconColor'] }}"></i>{{ __('admin/settings/plugins/index.badge_labels.health') }}</span>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $card['badgeColor'] }}">
                        {{ $card['badgeLabel'] }}
                    </span>
                </div>

                {{-- 署名ステータス --}}
                <div class="flex items-center gap-2">
                    @if(!$card['auditedAt'])
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-file-signature mr-1 text-gray-400"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                            {{ __('admin/settings/plugins/index.verification.signature_not_scanned') }}
                        </span>
                    @elseif($card['signatureStatus'] === 'valid')
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-check-circle mr-1 text-green-500"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            {{ __('admin/settings/plugins/index.verification.signature_valid') }}
                        </span>
                    @elseif($card['signatureStatus'] === 'pending_verification')
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-clock mr-1 text-orange-500"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                            {{ __('admin/settings/plugins/index.verification.signature_pending_verification') }}
                        </span>
                    @elseif(in_array($card['signatureStatus'], ['unknown_key', 'expired', 'error'], true))
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-exclamation-triangle mr-1 text-orange-500"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                            {{ __('admin/settings/plugins/index.permissions.signature_' . $card['signatureStatus']) }}
                        </span>
                    @elseif($card['signatureStatus'] === 'invalid')
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-times-circle mr-1 text-red-500"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            {{ __('admin/settings/plugins/index.verification.signature_invalid') }}
                        </span>
                    @else
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-file-signature mr-1 text-yellow-500"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            {{ __('admin/settings/plugins/index.verification.signature_unsigned') }}
                        </span>
                    @endif
                </div>

                {{-- 権限定義 --}}
                <div class="flex items-center gap-2">
                    @if(!$card['auditedAt'])
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-key mr-1 text-gray-400"></i>{{ __('admin/settings/plugins/index.badge_labels.permission') }}</span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                            {{ __('admin/settings/plugins/index.verification.permission_not_scanned') }}
                        </span>
                    @elseif($card['hasPermissions'])
                        @if($card['hasMismatches'])
                            <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-exclamation-circle mr-1 text-red-500"></i>{{ __('admin/settings/plugins/index.badge_labels.permission') }}</span>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
                                  title="{{ __('admin/settings/plugins/index.permissions.audit_mismatch_warning') }}">
                                {{ __('admin/settings/plugins/index.verification.permission_mismatch') }}
                            </span>
                        @else
                            <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-check-circle mr-1 text-green-500"></i>{{ __('admin/settings/plugins/index.badge_labels.permission') }}</span>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                {{ __('admin/settings/plugins/index.verification.permission_ok') }}
                            </span>
                        @endif
                    @else
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-key mr-1 text-gray-400"></i>{{ __('admin/settings/plugins/index.badge_labels.permission') }}</span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                            {{ __('admin/settings/plugins/index.verification.permission_undefined') }}
                        </span>
                    @endif
                </div>

                {{-- CSPモード別互換性 --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-shield-alt mr-1 {{ $card['cspTierIconColor'] }}"></i>{{ __('admin/settings/plugins/index.badge_labels.csp') }}</span>
                    <x-ui-barometer :items="$card['cspBarometerItems']" />
                </div>

                {{-- セキュリティプリセット互換性 --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-sliders-h mr-1 {{ $card['presetTierIconColor'] }}"></i>{{ __('admin/settings/plugins/index.badge_labels.preset') }}</span>
                    <x-ui-barometer :items="$card['presetBarometerItems']" />
                </div>

                {{-- 動作判定（信号機） --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-power-off mr-1 {{ $card['opIconColor'] }}"></i>{{ __('admin/settings/plugins/index.badge_labels.operation') }}</span>
                    <div class="inline-flex items-center gap-1">
                        <span class="inline-block w-3 h-3 rounded-full {{ $card['operationStatus']['status'] === 'ok' ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                        <span class="inline-block w-3 h-3 rounded-full {{ $card['operationStatus']['status'] === 'caution' ? 'bg-yellow-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                        <span class="inline-block w-3 h-3 rounded-full {{ $card['operationStatus']['status'] === 'blocked' ? 'bg-red-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                        <span class="text-xs text-gray-600 dark:text-gray-300 ml-1">{{ $card['operationStatus']['label'] }}</span>
                    </div>
                </div>

                {{-- スキャン鮮度バッジ --}}
                @if(! empty($card['scanFreshness']) && in_array($card['scanFreshness']['state'], ['unscanned', 'expired', 'files_changed'], true))
                    @php($freshness = $card['scanFreshness'])
                    <div class="flex justify-center mt-2">
                        <div class="inline-flex items-center justify-center gap-2 px-2 py-1 rounded text-xs whitespace-nowrap
                            @if($freshness['state'] === 'unscanned') bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300
                            @elseif($freshness['state'] === 'files_changed') bg-orange-50 text-orange-800 border border-orange-200 dark:bg-orange-900/20 dark:text-orange-200 dark:border-orange-800
                            @else bg-yellow-50 text-yellow-800 border border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-200 dark:border-yellow-800
                            @endif">
                            <i class="
                                @if($freshness['state'] === 'unscanned') fas fa-question-circle
                                @elseif($freshness['state'] === 'files_changed') fas fa-code-branch
                                @else fas fa-history
                                @endif
                            "></i>
                            <span>
                                @if($freshness['state'] === 'unscanned')
                                    {{ __('admin/settings/plugins/index.scan_status.unscanned') }}
                                @elseif($freshness['state'] === 'files_changed')
                                    {{ __('admin/settings/plugins/index.scan_status.files_changed') }}
                                @else
                                    {{ __('admin/settings/plugins/index.scan_status.expired', ['age' => $freshness['ageDays'] ?? '?', 'max' => $freshness['maxAgeDays']]) }}
                                @endif
                            </span>
                        </div>
                    </div>
                @endif

                {{-- スキャンボタン --}}
                <div class="flex justify-center items-center gap-2 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <x-form-button
                        type="button"
                        :label="$card['auditedAt'] ? __('admin/settings/plugins/index.permissions.audit_button_rescan') : __('admin/settings/plugins/index.permissions.audit_button')"
                        :variant="$card['auditedAt'] ? 'secondary' : 'warning'"
                        size="sm"
                        icon="fas fa-search"
                        class="audit-btn"
                        :data-slug="$card['slug']"
                        :title="$card['auditedAt'] ? __('admin/settings/plugins/index.permissions.audit_last_scanned') . ': ' . $card['auditedAtFormatted'] : __('admin/settings/plugins/index.permissions.audit_not_scanned')"
                    />
                </div>
            @endif
        </div>
        @endif

        {{-- 作者情報 --}}
        <div class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                <i class="fas fa-user mr-2 text-gray-400"></i>
                @if($card['authorName'])
                    <span>{{ $card['authorName'] }}</span>
                @else
                    <span class="text-gray-400 italic">{{ __('common.unknown') }}</span>
                @endif
            </div>
            @if($card['license'])
                <div class="flex items-center text-xs text-gray-400 dark:text-gray-500 mt-1">
                    <i class="fas fa-balance-scale mr-2"></i>
                    <span>{{ $card['license'] }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- アクションボタン --}}
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
        <div class="flex flex-wrap gap-2 justify-center">
            @if($card['isInstalled'])
                @include('admin.settings.plugins.partials.installed-actions', ['card' => $card])
            @else
                @include('admin.settings.plugins.partials.uninstalled-actions', ['card' => $card])
            @endif
        </div>
    </div>
</div>
