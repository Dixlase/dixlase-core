{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

テーマカードコンポーネント
インストール済み・未インストールの両方で使用
--}}

@php
    $isModel = is_object($theme);
    $themeId = $isModel ? $theme->id : ($theme['directory'] ?? '');
    $themeName = $isModel ? $theme->name : ($theme['name'] ?? '');
    $themeDescription = $isModel ? $theme->description : ($theme['description'] ?? '');
    $themeVersion = $isModel ? $theme->version : ($theme['version'] ?? '1.0.0');
    $themeLicense = $isModel ? $theme->license : ($theme['license'] ?? '');
    $themeDirectory = $isModel ? $theme->directory : ($theme['directory'] ?? '');
    $themeSlug = $isModel ? $theme->slug : ($theme['slug'] ?? '');
    
    // 作者情報
    $authorRaw = $isModel ? $theme->author : ($theme['author'] ?? null);
    $authorEmail = $isModel ? $theme->email : ($theme['email'] ?? null);
    $authorUrl = $isModel ? $theme->web : ($theme['url'] ?? null);
    
    if (is_array($authorRaw)) {
        $authorEmail = $authorRaw['email'] ?? $authorEmail;
        $authorUrl = $authorRaw['url'] ?? $authorRaw['homepage'] ?? $authorUrl;
        $authorName = $authorRaw['name'] ?? null;
    } else {
        $authorName = $authorRaw;
    }
    
    // ステータス
    $isInstalled = $isModel;
    $isEnabled = $isModel && $theme->id === $activeThemeId;
    
    // サムネイル
    $thumbnailPath = "themes/{$themeDirectory}/thumbnail.png";
    $thumbnailUrl = file_exists(base_path($thumbnailPath)) 
        ? asset("assets/themes/{$themeDirectory}/thumbnail.png")
        : asset('assets/images/theme-default.svg');
    
    // 権限情報
    $permissionSummary = $isModel 
        ? ($theme->permission_summary ?? null)
        : ($theme['permission_summary'] ?? null);
    
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
            'official' => __('admin/settings/themes/index.permissions.signature_official'),
            'verified' => __('admin/settings/themes/index.permissions.signature_verified'),
            'partner' => __('admin/settings/themes/index.permissions.signature_partner'),
        ];
        $badgeColor = $badgeColors[$signatureType] ?? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        $badgeIcon = $badgeIcons[$signatureType] ?? 'fas fa-check-circle';
        $badgeLabel = $badgeLabels[$signatureType] ?? __('admin/settings/themes/index.permissions.signature_signed');
    } elseif ($signatureStatus === 'invalid') {
        $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        $badgeIcon = 'fas fa-times-circle';
        $badgeLabel = __('admin/settings/themes/index.permissions.signature_invalid');
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
        $badgeLabel = __('admin/settings/themes/index.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy'));
    } else {
        $badgeColor = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
        $badgeIcon = 'fas fa-exclamation-triangle';
        $badgeLabel = __('admin/settings/themes/index.permissions.unknown');
    }
    
    $permissionModalId = 'permissionModal-theme-' . ($isModel ? $theme->id : $themeDirectory);
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all duration-200 flex flex-col group {{ $isEnabled ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}">
    {{-- サムネイル --}}
    <div class="relative aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
        <img 
            src="{{ $thumbnailUrl }}" 
            alt="{{ $themeName }}" 
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            onerror="this.src='{{ asset('assets/images/theme-default.svg') }}'"
        >
        {{-- ステータスバッジ（オーバーレイ） --}}
        <div class="absolute top-3 right-3">
            @if($isInstalled)
                @if($isEnabled)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shadow-sm bg-green-500 text-white">
                        <i class="fas fa-star mr-1"></i>
                        {{ __('common.active') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium shadow-sm bg-gray-500 text-white">
                        <i class="fas fa-pause-circle mr-1"></i>
                        {{ __('common.inactive') }}
                    </span>
                @endif
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
                #{{ $themeId }}
            </span>
        </div>
        @endif
    </div>

    {{-- コンテンツ --}}
    <div class="p-4 flex-1 flex flex-col">
        {{-- タイトルとバージョン --}}
        <div class="flex items-start justify-between gap-2 mb-2">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white line-clamp-1" title="{{ $themeName }}">{{ $themeName }}</h3>
            <span class="flex-shrink-0 inline-block font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                v{{ $themeVersion }}
            </span>
        </div>

        {{-- 説明 --}}
        @if($themeDescription)
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">{{ $themeDescription }}</p>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-3">{{ __('common.no_description') }}</p>
        @endif

        {{-- バッジ類 --}}
        @if($permissionSummary)
        <div class="mb-3 pt-3 border-t border-gray-100 dark:border-gray-700 space-y-2">
            {{-- 健全性 --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0">{{ __('admin/settings/themes/index.badge_labels.health') }}</span>
                <button type="button" 
                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }} cursor-pointer hover:opacity-80 transition-opacity"
                        @click="openModal('{{ $permissionModalId }}')">
                    <i class="{{ $badgeIcon }} mr-1"></i>
                    {{ $badgeLabel }}
                    <i class="fas fa-info-circle ml-1 text-xs opacity-60"></i>
                </button>
            </div>
            
            {{-- 署名ステータス --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0">{{ __('admin/settings/themes/index.badge_labels.signature') }}</span>
                @if($signatureStatus === 'valid' || $signatureStatus === 'pending_verification')
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <i class="fas fa-check-circle mr-1"></i>
                        {{ __('admin/settings/themes/index.verification.signature_valid') }}
                    </span>
                @elseif($signatureStatus === 'invalid')
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        <i class="fas fa-times-circle mr-1"></i>
                        {{ __('admin/settings/themes/index.verification.signature_invalid') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <i class="fas fa-file-signature mr-1"></i>
                        {{ __('admin/settings/themes/index.verification.signature_unsigned') }}
                    </span>
                @endif
            </div>
            
            {{-- 権限定義 --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0">{{ __('admin/settings/themes/index.badge_labels.permission') }}</span>
                @if($hasPermissions)
                    @if($hasMismatches)
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" title="{{ __('admin/settings/themes/index.permissions.audit_mismatch_warning') }}">
                            <i class="fas fa-code-branch mr-1"></i>
                            {{ __('admin/settings/themes/index.verification.permission_mismatch') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <i class="fas fa-check-circle mr-1"></i>
                            {{ __('admin/settings/themes/index.verification.permission_ok') }}
                        </span>
                    @endif
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <i class="fas fa-question-circle mr-1"></i>
                        {{ __('admin/settings/themes/index.verification.permission_undefined') }}
                    </span>
                @endif
            </div>
            
            {{-- CSP互換性 --}}
            @php
                $cspLoader = app(\App\Services\Csp\CspExtensionLoader::class);
                $cspCompatibility = $cspLoader->getCspCompatibility('theme', $themeSlug);
            @endphp
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400 w-12 flex-shrink-0">{{ __('admin/settings/themes/index.badge_labels.csp') }}</span>
                @if($cspCompatibility['status'] === 'csp_ready' || $cspCompatibility['status'] === 'compatible')
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200" title="{{ __('admin/settings/themes/index.csp.ready_tooltip') }}">
                        <i class="fas fa-shield-alt mr-1"></i>
                        {{ __('admin/settings/themes/index.verification.csp_ready') }}
                    </span>
                @elseif($cspCompatibility['requires_inline_js'])
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200" title="{{ __('admin/settings/themes/index.csp.inline_required_tooltip') }}">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        {{ __('admin/settings/themes/index.verification.csp_inline_required') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <i class="fas fa-question mr-1"></i>
                        {{ __('admin/settings/themes/index.verification.csp_not_checked') }}
                    </span>
                @endif
            </div>
            
            {{-- スキャンボタン --}}
            @php
                $auditedAt = $auditResult['audited_at'] ?? null;
            @endphp
            <div class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                <x-form-button
                    type="button"
                    :label="$auditedAt ? __('admin/settings/themes/index.permissions.audit_button_rescan') : __('admin/settings/themes/index.permissions.audit_button')"
                    :variant="$auditedAt ? 'tertiary' : 'warning'"
                    size="xs"
                    icon="fas fa-search"
                    class="theme-audit-btn w-full"
                    :data-slug="$themeSlug"
                    :title="$auditedAt ? __('admin/settings/themes/index.permissions.audit_last_scanned') . ': ' . \Carbon\Carbon::parse($auditedAt)->format('Y/m/d H:i') : __('admin/settings/themes/index.permissions.audit_not_scanned')"
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
            @if($themeLicense)
                <div class="flex items-center text-xs text-gray-400 dark:text-gray-500 mt-1">
                    <i class="fas fa-balance-scale mr-2"></i>
                    <span>{{ $themeLicense }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- アクションボタン --}}
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
        <div class="flex flex-wrap gap-2 justify-center">
            @if($isInstalled)
                {{-- インストール済みテーマのアクション --}}
                @include('admin.settings.themes.partials.installed-actions', ['theme' => $theme, 'activeThemeId' => $activeThemeId])
            @else
                {{-- 未インストールテーマのアクション --}}
                @include('admin.settings.themes.partials.uninstalled-actions', ['theme' => $theme])
            @endif
        </div>
    </div>
</div>

{{-- 権限詳細モーダル --}}
@include('admin.settings.themes.partials.permission-modal', [
    'theme' => $theme,
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
