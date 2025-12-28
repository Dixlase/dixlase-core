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

@php
    $isModel = is_object($plugin);
    $pluginId = $isModel ? $plugin->id : ($plugin['directory'] ?? '');
    $pluginName = $isModel ? $plugin->translated_name : ($plugin['name'] ?? '');
    $pluginDescription = $isModel ? $plugin->translated_description : ($plugin['description'] ?? '');
    $pluginVersion = $isModel ? $plugin->version : ($plugin['version'] ?? '1.0.0');
    $pluginLicense = $isModel ? $plugin->license : ($plugin['license'] ?? '');
    $pluginDirectory = $isModel ? $plugin->directory : ($plugin['directory'] ?? '');
    $pluginSlug = $isModel ? $plugin->slug : ($plugin['slug'] ?? '');
    
    // 作者情報
    $authorRaw = $isModel ? $plugin->author : ($plugin['author'] ?? null);
    $authorEmail = $isModel ? $plugin->email : ($plugin['email'] ?? null);
    $authorUrl = $isModel ? $plugin->web : ($plugin['url'] ?? null);
    
    if (is_array($authorRaw)) {
        $authorEmail = $authorRaw['email'] ?? $authorEmail;
        $authorUrl = $authorRaw['url'] ?? $authorRaw['homepage'] ?? $authorUrl;
        $authorName = $authorRaw['name'] ?? null;
    } else {
        $authorName = $authorRaw;
    }
    
    // ステータス
    $isInstalled = $isModel;
    $isEnabled = $isModel && $plugin->isEnabled();
    
    // サムネイル
    $thumbnailPath = "plugins/{$pluginDirectory}/thumbnail.png";
    $thumbnailUrl = file_exists(base_path($thumbnailPath)) 
        ? asset("assets/plugins/{$pluginDirectory}/thumbnail.png")
        : asset('assets/images/plugin-default.svg');
    
    // 権限情報
    $permissionSummary = $isModel 
        ? ($plugin->permission_summary ?? null)
        : ($plugin['permission_summary'] ?? null);
    
    $hasPermissions = $permissionSummary['has_permissions'] ?? false;
    $riskLevel = $permissionSummary['risk_level'] ?? 'unknown';
    $signature = $permissionSummary['signature'] ?? ['status' => 'unsigned'];
    $signatureStatus = $signature['status'] ?? 'unsigned';
    $signatureType = $signature['type'] ?? null;
    $auditResult = $permissionSummary['audit'] ?? [];
    $hasMismatches = $auditResult['has_mismatches'] ?? false;
    
    // バッジ設定
    if ($signatureStatus === 'valid' || $signatureStatus === 'pending_verification') {
        $badgeColors = [
            'official' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'verified' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'partner' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        ];
        $badgeIcons = [
            'official' => 'fas fa-crown',
            'verified' => 'fas fa-check-circle',
            'partner' => 'fas fa-handshake',
        ];
        $badgeLabels = [
            'official' => __('admin/settings/plugins/index.permissions.signature_official'),
            'verified' => __('admin/settings/plugins/index.permissions.signature_verified'),
            'partner' => __('admin/settings/plugins/index.permissions.signature_partner'),
        ];
        $badgeColor = $badgeColors[$signatureType] ?? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        $badgeIcon = $badgeIcons[$signatureType] ?? 'fas fa-check-circle';
        $badgeLabel = $badgeLabels[$signatureType] ?? __('admin/settings/plugins/index.permissions.signature_signed');
    } elseif ($signatureStatus === 'invalid') {
        $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        $badgeIcon = 'fas fa-times-circle';
        $badgeLabel = __('admin/settings/plugins/index.permissions.signature_invalid');
    } elseif ($hasPermissions) {
        $healthColors = [
            'low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        ];
        $healthIcons = [
            'low' => 'fas fa-check-circle',
            'medium' => 'fas fa-info-circle',
            'high' => 'fas fa-exclamation-circle',
        ];
        $healthLabels = [
            'low' => 'health_healthy',
            'medium' => 'health_warning',
            'high' => 'health_needs_attention',
        ];
        $badgeColor = $healthColors[$riskLevel] ?? $healthColors['low'];
        $badgeIcon = $healthIcons[$riskLevel] ?? $healthIcons['low'];
        $badgeLabel = __('admin/settings/plugins/index.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy'));
    } else {
        $badgeColor = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
        $badgeIcon = 'fas fa-exclamation-triangle';
        $badgeLabel = __('admin/settings/plugins/index.permissions.unknown');
    }
    
    $permissionModalId = 'permissionModal-' . ($isModel ? $plugin->id : $pluginDirectory);
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all duration-200 flex flex-col group">
    {{-- サムネイル --}}
    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
        <img 
            src="{{ $thumbnailUrl }}" 
            alt="{{ $pluginName }}" 
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            onerror="this.src='{{ asset('assets/images/plugin-default.svg') }}'"
        >
        {{-- ステータスバッジ（オーバーレイ） --}}
        <div class="absolute top-3 right-3">
            @if($isInstalled)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shadow-sm {{ $isEnabled ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                    <i class="fas {{ $isEnabled ? 'fa-check-circle' : 'fa-pause-circle' }} mr-1"></i>
                    {{ $isEnabled ? __('common.enabled') : __('common.disabled') }}
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shadow-sm bg-yellow-500 text-white">
                    <i class="fas fa-download mr-1"></i>
                    {{ __('common.not_installed') }}
                </span>
            @endif
        </div>
        {{-- ID表示（インストール済みのみ） --}}
        @if($isInstalled)
        <div class="absolute top-3 left-3">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-black/50 text-white">
                #{{ $pluginId }}
            </span>
        </div>
        @endif
    </div>

    {{-- コンテンツ --}}
    <div class="p-4 flex-1 flex flex-col">
        {{-- タイトルとバージョン --}}
        <div class="flex items-start justify-between gap-2 mb-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white line-clamp-1" title="{{ $pluginName }}">{{ $pluginName }}</h3>
            <span class="flex-shrink-0 inline-block font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                v{{ $pluginVersion }}
            </span>
        </div>

        {{-- 説明 --}}
        @if($pluginDescription)
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">{{ $pluginDescription }}</p>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-3">{{ __('common.no_description') }}</p>
        @endif

        {{-- バッジ類 --}}
        @if($permissionSummary)
        <div class="mb-3 pt-3 border-t border-gray-100 dark:border-gray-700 space-y-2">
            {{-- 健全性/署名 --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0">{{ __('admin/settings/plugins/index.badge_labels.health') }}</span>
                <button type="button" 
                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }} cursor-pointer hover:opacity-80 transition-opacity"
                        onclick="openModal('{{ $permissionModalId }}')">
                    <i class="{{ $badgeIcon }} mr-1"></i>
                    {{ $badgeLabel }}
                    <i class="fas fa-info-circle ml-1 text-xs opacity-60"></i>
                </button>
            </div>
            
            {{-- 署名ステータス --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0">{{ __('admin/settings/plugins/index.badge_labels.signature') }}</span>
                @if($signatureStatus === 'valid' || $signatureStatus === 'pending_verification')
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <i class="fas fa-check-circle mr-1"></i>
                        {{ __('admin/settings/plugins/index.verification.signature_valid') }}
                    </span>
                @elseif($signatureStatus === 'invalid')
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        <i class="fas fa-times-circle mr-1"></i>
                        {{ __('admin/settings/plugins/index.verification.signature_invalid') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <i class="fas fa-file-signature mr-1"></i>
                        {{ __('admin/settings/plugins/index.verification.signature_unsigned') }}
                    </span>
                @endif
            </div>
            
            {{-- 権限定義 --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0">{{ __('admin/settings/plugins/index.badge_labels.permission') }}</span>
                @if($hasPermissions)
                    @if($hasMismatches)
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" title="{{ __('admin/settings/plugins/index.permissions.audit_mismatch_warning') }}">
                            <i class="fas fa-code-branch mr-1"></i>
                            {{ __('admin/settings/plugins/index.verification.permission_mismatch') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <i class="fas fa-check-circle mr-1"></i>
                            {{ __('admin/settings/plugins/index.verification.permission_ok') }}
                        </span>
                    @endif
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <i class="fas fa-question-circle mr-1"></i>
                        {{ __('admin/settings/plugins/index.verification.permission_undefined') }}
                    </span>
                @endif
            </div>
            
            {{-- CSP互換性 --}}
            @php
                $cspLoader = app(\App\Services\Csp\CspExtensionLoader::class);
                $cspCompatibility = $cspLoader->getCspCompatibility('plugin', $pluginSlug);
            @endphp
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0">{{ __('admin/settings/plugins/index.badge_labels.csp') }}</span>
                @if($cspCompatibility['status'] === 'csp_ready' || $cspCompatibility['status'] === 'compatible')
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200" title="{{ __('admin/settings/plugins/index.csp.ready_tooltip') }}">
                        <i class="fas fa-shield-alt mr-1"></i>
                        {{ __('admin/settings/plugins/index.verification.csp_ready') }}
                    </span>
                @elseif($cspCompatibility['requires_inline_js'])
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200" title="{{ __('admin/settings/plugins/index.csp.inline_required_tooltip') }}">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        {{ __('admin/settings/plugins/index.verification.csp_inline_required') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <i class="fas fa-question mr-1"></i>
                        {{ __('admin/settings/plugins/index.verification.csp_not_checked') }}
                    </span>
                @endif
            </div>
            
            {{-- スキャンボタン --}}
            @php
                $auditedAt = $auditResult['audited_at'] ?? null;
            @endphp
            <div class="flex justify-center items-center gap-2 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                <x-form.button
                    type="button"
                    :label="$auditedAt ? __('admin/settings/plugins/index.permissions.audit_button_rescan') : __('admin/settings/plugins/index.permissions.audit_button')"
                    :variant="$auditedAt ? 'secondary' : 'warning'"
                    size="sm"
                    icon="fas fa-search"
                    class="audit-btn"
                    :data-slug="$pluginSlug"
                    :title="$auditedAt ? __('admin/settings/plugins/index.permissions.audit_last_scanned') . ': ' . \Carbon\Carbon::parse($auditedAt)->format('Y/m/d H:i') : __('admin/settings/plugins/index.permissions.audit_not_scanned')"
                />
            </div>
        </div>
        @endif

        {{-- 作者情報 --}}
        <div class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                <i class="fas fa-user mr-2 text-gray-400"></i>
                @if($authorName)
                    <span>{{ $authorName }}</span>
                @else
                    <span class="text-gray-400 italic">{{ __('common.unknown') }}</span>
                @endif
            </div>
            @if($pluginLicense)
                <div class="flex items-center text-xs text-gray-400 dark:text-gray-500 mt-1">
                    <i class="fas fa-balance-scale mr-2"></i>
                    <span>{{ $pluginLicense }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- アクションボタン --}}
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
        <div class="flex flex-wrap gap-2 justify-center">
            @if($isInstalled)
                {{-- インストール済みプラグインのアクション --}}
                @include('admin.settings.plugins.partials.installed-actions', ['plugin' => $plugin])
            @else
                {{-- 未インストールプラグインのアクション --}}
                @include('admin.settings.plugins.partials.uninstalled-actions', ['plugin' => $plugin])
            @endif
        </div>
    </div>
</div>

{{-- 権限詳細モーダル --}}
@include('admin.settings.plugins.partials.permission-modal', [
    'plugin' => $plugin,
    'isModel' => $isModel,
    'permissionModalId' => $permissionModalId,
    'permissionSummary' => $permissionSummary,
    'badgeColor' => $badgeColor,
    'badgeIcon' => $badgeIcon,
    'badgeLabel' => $badgeLabel,
    'signatureStatus' => $signatureStatus,
    'signature' => $signature,
    'hasPermissions' => $hasPermissions,
    'riskLevel' => $riskLevel,
    'hasMismatches' => $hasMismatches,
    'auditResult' => $auditResult,
])
