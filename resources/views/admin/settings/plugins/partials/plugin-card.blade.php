{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
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

プラグインカードコンポーネント
インストール済み・未インストールの両方で使用
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

        {{-- 説明 --}}
        @if($card['description'])
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">{{ $card['description'] }}</p>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-3">{{ __('common.no_description') }}</p>
        @endif

        {{-- バッジ類 --}}
        @if($card['permissionSummary'])
        <div class="mb-3 pt-3 border-t border-gray-100 dark:border-gray-700 space-y-2"
             data-scan-data="{{ json_encode($card['scanData'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
             data-plugin-name="{{ $card['name'] }}">
            @php
                $tierIconColor = fn(string $tier) => match($tier) {
                    'strict' => 'text-green-500',
                    'standard' => 'text-yellow-500',
                    'development' => 'text-red-500',
                    default => 'text-gray-400',
                };
                $opIconColor = match($card['operationStatus']['status']) {
                    'ok' => 'text-green-500',
                    'caution' => 'text-yellow-500',
                    'blocked' => 'text-red-500',
                    default => 'text-gray-400',
                };
                $healthIconColor = ($card['healthStatus'] ?? 'not_verified') === 'healthy' ? 'text-green-500' : 'text-red-500';
            @endphp

            {{-- 健全性 --}}
            <div class="flex items-center gap-2">
                <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="{{ $card['badgeIcon'] }} mr-1 {{ $healthIconColor }}"></i>{{ __('admin/settings/plugins/index.badge_labels.health') }}</button>
                <button type="button"
                        class="badge-detail-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $card['badgeColor'] }} cursor-pointer hover:opacity-80 transition-opacity">
                    {{ $card['badgeLabel'] }}
                </button>
            </div>

            {{-- 署名ステータス --}}
            <div class="flex items-center gap-2">
                @if(!$card['auditedAt'])
                    <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0"><i class="fas fa-file-signature mr-1 text-gray-400"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</span>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        {{ __('admin/settings/plugins/index.verification.signature_not_scanned') }}
                    </span>
                @elseif($card['signatureStatus'] === 'valid' || $card['signatureStatus'] === 'pending_verification')
                    <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="fas fa-check-circle mr-1 text-green-500"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</button>
                    <button type="button"
                            class="badge-detail-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 cursor-pointer hover:opacity-80 transition-opacity">
                        {{ __('admin/settings/plugins/index.verification.signature_valid') }}
                    </button>
                @elseif($card['signatureStatus'] === 'invalid')
                    <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="fas fa-times-circle mr-1 text-red-500"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</button>
                    <button type="button"
                            class="badge-detail-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 cursor-pointer hover:opacity-80 transition-opacity">
                        {{ __('admin/settings/plugins/index.verification.signature_invalid') }}
                    </button>
                @else
                    <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="fas fa-file-signature mr-1 text-yellow-500"></i>{{ __('admin/settings/plugins/index.badge_labels.signature') }}</button>
                    <button type="button"
                            class="badge-detail-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 cursor-pointer hover:opacity-80 transition-opacity">
                        {{ __('admin/settings/plugins/index.verification.signature_unsigned') }}
                    </button>
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
                        <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="fas fa-exclamation-circle mr-1 text-red-500"></i>{{ __('admin/settings/plugins/index.badge_labels.permission') }}</button>
                        <button type="button"
                                class="badge-detail-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 cursor-pointer hover:opacity-80 transition-opacity"
                                title="{{ __('admin/settings/plugins/index.permissions.audit_mismatch_warning') }}">
                            {{ __('admin/settings/plugins/index.verification.permission_mismatch') }}
                        </button>
                    @else
                        <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="fas fa-check-circle mr-1 text-green-500"></i>{{ __('admin/settings/plugins/index.badge_labels.permission') }}</button>
                        <button type="button"
                                class="badge-detail-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 cursor-pointer hover:opacity-80 transition-opacity">
                            {{ __('admin/settings/plugins/index.verification.permission_ok') }}
                        </button>
                    @endif
                @else
                    <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="fas fa-key mr-1 text-gray-400"></i>{{ __('admin/settings/plugins/index.badge_labels.permission') }}</button>
                    <button type="button"
                            class="badge-detail-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 cursor-pointer hover:opacity-80 transition-opacity">
                        {{ __('admin/settings/plugins/index.verification.permission_undefined') }}
                    </button>
                @endif
            </div>

            {{-- CSPモード別互換性 --}}
            <div class="flex items-center gap-2">
                <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="fas fa-shield-alt mr-1 {{ $tierIconColor($card['cspMaxTier']) }}"></i>{{ __('admin/settings/plugins/index.badge_labels.csp') }}</button>
                <x-ui-barometer :items="$card['cspBarometerItems']" />
            </div>

            {{-- セキュリティプリセット互換性 --}}
            <div class="flex items-center gap-2">
                <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="fas fa-sliders-h mr-1 {{ $tierIconColor($card['presetMaxTier']) }}"></i>{{ __('admin/settings/plugins/index.badge_labels.preset') }}</button>
                <x-ui-barometer :items="$card['presetBarometerItems']" />
            </div>

            {{-- 動作判定（信号機） --}}
            <div class="flex items-center gap-2">
                <button type="button" class="badge-detail-btn text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0 text-left cursor-pointer hover:opacity-70 transition-opacity"><i class="fas fa-power-off mr-1 {{ $opIconColor }}"></i>{{ __('admin/settings/plugins/index.badge_labels.operation') }}</button>
                <div class="inline-flex items-center gap-1">
                    @php
                        $opStatus = $card['operationStatus']['status'];
                    @endphp
                    <span class="inline-block w-3 h-3 rounded-full {{ $opStatus === 'ok' ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                    <span class="inline-block w-3 h-3 rounded-full {{ $opStatus === 'caution' ? 'bg-yellow-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                    <span class="inline-block w-3 h-3 rounded-full {{ $opStatus === 'blocked' ? 'bg-red-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-300 ml-1">{{ $card['operationStatus']['label'] }}</span>
                </div>
            </div>

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
