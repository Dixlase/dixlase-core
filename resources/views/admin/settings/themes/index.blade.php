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
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
    <!-- インストール済みテーマ一覧セクション -->
    <section>
        <h2>{{ __('admin.settings.themes.index.installed_heading') }}</h2>

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.themes.index.table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('admin.settings.themes.index.table.name') }}</th>
                        <th>{{ __('common.details') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($themes as $theme)
                        <tr>
                            <td data-label="{{ __('common.id') }}">
                                <span class="text-sm font-mono px-2 py-1 rounded">{{ $theme->id }}</span>
                            </td>
                            <td data-label="{{ __('admin.settings.themes.index.table.name') }}">
                                <div>
                                    <strong class="text-lg">{{ $theme->name }}</strong>
                                    @if($theme->description)
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $theme->description }}</div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="{{ __('common.details') }}">
                                <div class="text-sm space-y-1">
                                    <!-- 作者情報 -->
                                    <div class="mb-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('common.author') }}:</span>
                                        @if($theme->author)
                                            <span>{{ $theme->author }}</span>
                                            @if($theme->email)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $theme->email }}</div>
                                            @endif
                                            @if($theme->web)
                                                <div class="text-xs">
                                                    <a href="{{ $theme->web }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $theme->web }}</a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">{{ __('common.unknown') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- バージョン情報 -->
                                    <div class="inline-block font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $theme->version }}</div>
                                    <!-- ライセンス情報 -->
                                    <div>
                                        @if($theme->license)
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">{{ $theme->license }}</div>
                                        @else
                                            <div class="inline-block text-gray-400">{{ __('common.unknown') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('common.status') }}">
                                <div class="space-y-2">
                                    @if($theme->id === $activeThemeId)
                                        <span class="status-badge status-badge--enabled">
                                            {{ __('common.enabled') }}
                                        </span>
                                    @else
                                        <span class="status-badge status-badge--disabled">
                                            {{ __('common.disabled') }}
                                        </span>
                                    @endif
                                    
                                    {{-- 権限・署名ステータスバッジ（モーダル表示） --}}
                                    @if(isset($theme->permission_summary))
                                        @php
                                            $hasPermissions = $theme->permission_summary['has_permissions'];
                                            $riskLevel = $theme->permission_summary['risk_level'];
                                            $categories = $theme->permission_summary['categories'] ?? [];
                                            $signature = $theme->permission_summary['signature'] ?? ['status' => 'unsigned'];
                                            $permissionModalId = 'permissionModal-' . $theme->id;
                                            
                                            // 署名ステータスに基づくバッジ設定
                                            $signatureStatus = $signature['status'] ?? 'unsigned';
                                            $signatureType = $signature['type'] ?? null;
                                            
                                            // バッジの色とアイコンを決定
                                            if ($signatureStatus === 'valid' || $signatureStatus === 'pending_verification') {
                                                // 署名あり（検証OK or 検証待ち）
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
                                                    'official' => __('admin.settings.themes.permissions.signature_official'),
                                                    'verified' => __('admin.settings.themes.permissions.signature_verified'),
                                                    'partner' => __('admin.settings.themes.permissions.signature_partner'),
                                                ];
                                                $badgeColor = $badgeColors[$signatureType] ?? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                                $badgeIcon = $badgeIcons[$signatureType] ?? 'fas fa-check-circle';
                                                $badgeLabel = $badgeLabels[$signatureType] ?? __('admin.settings.themes.permissions.signature_signed');
                                            } elseif ($signatureStatus === 'invalid') {
                                                // 署名無効
                                                $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                                $badgeIcon = 'fas fa-times-circle';
                                                $badgeLabel = __('admin.settings.themes.permissions.signature_invalid');
                                            } elseif ($hasPermissions) {
                                                // 未署名 + 権限定義あり
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
                                                $badgeLabel = __('admin.settings.themes.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy'));
                                            } else {
                                                // 未署名 + 権限未定義 → 警告表示
                                                $badgeColor = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 border border-orange-300 dark:border-orange-700';
                                                $badgeIcon = 'fas fa-exclamation-triangle';
                                                $badgeLabel = __('admin.settings.themes.permissions.unknown');
                                            }
                                            
                                            // 監査結果
                                            $auditResult = $theme->permission_summary['audit'] ?? [];
                                            $hasMismatches = $auditResult['has_mismatches'] ?? false;
                                        @endphp
                                        <div class="mt-1">
                                            <div class="flex items-center gap-1 flex-wrap">
                                                <button type="button" 
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }} cursor-pointer hover:opacity-80 transition-opacity"
                                                        onclick="openModal('{{ $permissionModalId }}')">
                                                    <i class="{{ $badgeIcon }} mr-1"></i>
                                                    {{ $badgeLabel }}
                                                    <i class="fas fa-info-circle ml-1 text-xs opacity-60"></i>
                                                </button>
                                                @if($hasMismatches)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" title="{{ __('admin.settings.themes.permissions.audit_mismatch_warning') }}">
                                                        <i class="fas fa-code-branch mr-1"></i>
                                                        {{ __('admin.settings.themes.permissions.audit_mismatch_badge') }}
                                                    </span>
                                                @endif
                                                {{-- CSP互換性バッジ --}}
                                                @php
                                                    $cspLoader = app(\App\Services\Csp\CspExtensionLoader::class);
                                                    $cspCompatibility = $cspLoader->getCspCompatibility('theme', $theme->slug);
                                                @endphp
                                                @if($cspCompatibility['status'] === 'csp_ready' || $cspCompatibility['status'] === 'compatible')
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200" title="{{ __('admin.settings.security.csp.badge_csp_ready_tooltip') }}">
                                                        <i class="fas fa-shield-alt mr-1"></i>
                                                        {{ __('admin.settings.security.csp.badge_csp_ready') }}
                                                    </span>
                                                @elseif($cspCompatibility['requires_inline_js'])
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200" title="{{ __('admin.settings.security.csp.badge_inline_required_tooltip') }}">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                        {{ __('admin.settings.security.csp.badge_inline_required') }}
                                                    </span>
                                                @endif
                                                {{-- CSP診断バッジ --}}
                                                @if(isset($theme->csp_diagnostic) && !$theme->csp_diagnostic['compliant'])
                                                    @php
                                                        $cspIssues = $theme->csp_diagnostic['summary']['total_issues'] ?? 0;
                                                    @endphp
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200" title="{{ __('admin.settings.plugins.permissions.csp_warning_message') }}">
                                                        <i class="fas fa-shield-alt mr-1"></i>
                                                        CSP {{ __('admin.settings.plugins.permissions.csp_issues_found', ['count' => $cspIssues]) }}
                                                    </span>
                                                @endif
                                                {{-- スキャンボタン --}}
                                                <button type="button"
                                                        class="theme-audit-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                                        data-slug="{{ $theme->slug }}"
                                                        title="{{ $auditResult['audited_at'] ? __('admin.settings.themes.permissions.audit_last_scanned') . ': ' . $auditResult['audited_at'] : __('admin.settings.themes.permissions.audit_not_scanned') }}">
                                                    <i class="fas fa-search mr-1"></i>
                                                    <span class="audit-btn-text">{{ $auditResult['audited_at'] ? __('admin.settings.themes.permissions.audit_button_rescan') : __('admin.settings.themes.permissions.audit_button') }}</span>
                                                </button>
                                            </div>
                                            
                                            {{-- 権限・署名詳細モーダル --}}
                                            <x-modal
                                                :id="$permissionModalId"
                                                :title="__('admin.settings.themes.permissions.details_title') . ' - ' . $theme->name"
                                                icon_type="info"
                                                :close_only="true"
                                                :close_label="__('common.close')"
                                            >
                                                <div class="text-left">
                                                    {{-- 監査警告 --}}
                                                    @if($hasMismatches)
                                                        <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                                                            <h5 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
                                                                <i class="fas fa-code-branch mr-1"></i>
                                                                {{ __('admin.settings.themes.permissions.audit_mismatch_title') }}
                                                            </h5>
                                                            <p class="text-xs text-red-700 dark:text-red-300 mb-2">{{ __('admin.settings.themes.permissions.audit_mismatch_warning') }}</p>
                                                            <ul class="text-xs text-red-600 dark:text-red-400 space-y-1 ml-4 list-disc">
                                                                @foreach(array_slice($auditResult['mismatches'] ?? [], 0, 5) as $mismatch)
                                                                    <li>
                                                                        <code class="bg-red-100 dark:bg-red-800 px-1 rounded">{{ $mismatch['permission'] }}</code>
                                                                        @if($mismatch['type'] === 'undeclared_usage')
                                                                            - {{ __('admin.settings.themes.permissions.audit_undeclared_usage') }}
                                                                        @else
                                                                            - {{ __('admin.settings.themes.permissions.audit_unused_declaration') }}
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                    
                                                    {{-- CSP診断警告 --}}
                                                    @if(isset($theme->csp_diagnostic) && !$theme->csp_diagnostic['compliant'])
                                                        <div class="mb-4 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                                                            <h5 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-2">
                                                                <i class="fas fa-shield-alt mr-1"></i>
                                                                {{ __('admin.settings.plugins.permissions.csp_warning_title') }}
                                                            </h5>
                                                            <p class="text-xs text-orange-700 dark:text-orange-300 mb-2">{{ __('admin.settings.plugins.permissions.csp_warning_message') }}</p>
                                                            <div class="text-xs text-orange-600 dark:text-orange-400 space-y-1">
                                                                <p><i class="fas fa-code mr-1"></i> {{ __('admin.settings.plugins.permissions.csp_inline_scripts') }}: {{ $theme->csp_diagnostic['summary']['inline_scripts'] ?? 0 }}</p>
                                                                <p><i class="fas fa-paint-brush mr-1"></i> {{ __('admin.settings.plugins.permissions.csp_inline_styles') }}: {{ $theme->csp_diagnostic['summary']['inline_styles'] ?? 0 }}</p>
                                                            </div>
                                                            <p class="text-xs text-orange-600 dark:text-orange-400 mt-2 italic">{{ __('admin.settings.plugins.permissions.csp_fix_suggestion') }}</p>
                                                        </div>
                                                    @endif
                                                    
                                                    {{-- 署名ステータス --}}
                                                    <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.themes.permissions.signature_status') }}</h4>
                                                        @if($signatureStatus === 'valid' || $signatureStatus === 'pending_verification')
                                                            <div class="flex items-center mb-2">
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }}">
                                                                    <i class="{{ $badgeIcon }} mr-1"></i>
                                                                    {{ $badgeLabel }}
                                                                </span>
                                                            </div>
                                                            @if($signature['signed_by'])
                                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                                    {{ __('admin.settings.themes.permissions.signed_by') }}: {{ $signature['signed_by'] }}
                                                                </p>
                                                            @endif
                                                        @elseif($signatureStatus === 'invalid')
                                                            <div class="flex items-center mb-2">
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                                    <i class="fas fa-times-circle mr-1"></i>
                                                                    {{ __('admin.settings.themes.permissions.signature_invalid') }}
                                                                </span>
                                                            </div>
                                                            <p class="text-sm text-red-600 dark:text-red-400">
                                                                {{ __('admin.settings.themes.permissions.signature_invalid_warning') }}
                                                            </p>
                                                        @else
                                                            <div class="flex items-center mb-2">
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                                    <i class="fas fa-file-signature mr-1"></i>
                                                                    {{ __('admin.settings.themes.permissions.signature_unsigned') }}
                                                                </span>
                                                            </div>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                                {{ __('admin.settings.themes.permissions.signature_unsigned_info') }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    
                                                    {{-- 権限情報 --}}
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.themes.permissions.permission_info') }}</h4>
                                                        @if($hasPermissions)
                                                            {{-- 健全性レベル表示 --}}
                                                            @php
                                                                $attentionReasons = $theme->permission_summary['risk_reasons'] ?? [];
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
                                                            @endphp
                                                            <div class="mb-3 flex items-center">
                                                                <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">{{ __('admin.settings.themes.permissions.health_status') }}:</span>
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $healthColors[$riskLevel] ?? $healthColors['low'] }}">
                                                                    <i class="{{ $healthIcons[$riskLevel] ?? $healthIcons['low'] }} mr-1"></i>
                                                                    {{ __('admin.settings.themes.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy')) }}
                                                                </span>
                                                            </div>
                                                            
                                                            {{-- 確認が必要な理由 --}}
                                                            @if(!empty($attentionReasons))
                                                                <div class="mb-4 p-3 rounded-lg {{ $riskLevel === 'high' ? 'bg-orange-50 dark:bg-orange-900/20' : ($riskLevel === 'medium' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800') }}">
                                                                    <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                                        <i class="fas fa-info-circle mr-1"></i>
                                                                        {{ __('admin.settings.themes.permissions.attention_reasons_title') }}
                                                                    </h5>
                                                                    <ul class="space-y-1">
                                                                        @foreach($attentionReasons as $reason)
                                                                            @php
                                                                                $reasonKey = str_replace('.', '_', $reason['key']);
                                                                                $severityColor = $reason['severity'] === 'high' 
                                                                                    ? 'text-orange-600 dark:text-orange-400' 
                                                                                    : 'text-yellow-600 dark:text-yellow-400';
                                                                                $severityIcon = $reason['severity'] === 'high' 
                                                                                    ? 'fas fa-exclamation-circle' 
                                                                                    : 'fas fa-info-circle';
                                                                            @endphp
                                                                            <li class="flex items-start text-xs {{ $severityColor }}">
                                                                                <i class="{{ $severityIcon }} mr-2 mt-0.5 flex-shrink-0"></i>
                                                                                <span>{{ __('admin.settings.themes.permissions.attention_reason_' . $reasonKey) }}</span>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            @endif
                                                            
                                                            {{-- 権限カテゴリ一覧 --}}
                                                            @if(!empty($categories))
                                                                <div class="space-y-3">
                                                                    @foreach($categories as $category => $permissions)
                                                                        <div class="border-b border-gray-200 dark:border-gray-700 pb-2 last:border-0">
                                                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.themes.permissions.category_' . $category) }}:</span>
                                                                            <div class="mt-1 flex flex-wrap gap-1">
                                                                                @foreach($permissions as $perm)
                                                                                    <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-xs">
                                                                                        {{ __('admin.settings.themes.permissions.perm_' . $perm) }}
                                                                                    </span>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.themes.permissions.no_special_permissions') }}</p>
                                                            @endif
                                                        @else
                                                            {{-- 権限未定義 → 警告 --}}
                                                            <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                                                                <div class="flex items-start">
                                                                    <i class="fas fa-exclamation-triangle text-orange-500 dark:text-orange-400 mr-2 mt-0.5"></i>
                                                                    <p class="text-sm text-orange-700 dark:text-orange-300">{{ __('admin.settings.themes.permissions.unknown_warning') }}</p>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </x-modal>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="{{ __('common.actions') }}">
                                <div class="action-buttons">
                                    @if($theme->id === $activeThemeId)
                                        <!-- 有効化中のテーマ：設定ボタンのみ（ルートが存在する場合） -->
                                        @if(($theme->has_settings ?? false) && Route::has('admin.settings.themes.settings'))
                                            <a href="{{ route('admin.settings.themes.settings') }}" class="inline-block">
                                                <x-form.button
                                                    type="button"
                                                    label="設定"
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fas fa-cog"
                                                />
                                            </a>
                                        @endif
                                    @else
                                        <!-- 有効化ボタン -->
                                        @php
                                            // 有効化時の警告条件を判定
                                            $enableWarnings = [];
                                            $signatureStatus = $theme->permission_summary['signature']['status'] ?? 'unsigned';
                                            $riskLevel = $theme->permission_summary['risk_level'] ?? 'low';
                                            $hasPermissions = $theme->permission_summary['has_permissions'] ?? false;
                                            $hasMismatchesForEnable = $theme->permission_summary['audit']['has_mismatches'] ?? false;
                                            $auditedAtForEnable = $theme->permission_summary['audit']['audited_at'] ?? null;
                                            
                                            // 署名無効
                                            if ($signatureStatus === 'invalid') {
                                                $enableWarnings[] = __('admin.settings.themes.permissions.enable_warning_invalid_signature');
                                            }
                                            // 未署名
                                            if ($signatureStatus === 'unsigned' || $signatureStatus === 'none') {
                                                $enableWarnings[] = __('admin.settings.themes.permissions.install_warning_unsigned');
                                            }
                                            // 権限未定義
                                            if (!$hasPermissions) {
                                                $enableWarnings[] = __('admin.settings.themes.permissions.install_warning_undefined');
                                            }
                                            // 要確認
                                            if ($riskLevel === 'high') {
                                                $enableWarnings[] = __('admin.settings.themes.permissions.enable_warning_needs_attention');
                                            }
                                            // 注意
                                            if ($riskLevel === 'medium') {
                                                $enableWarnings[] = __('admin.settings.themes.permissions.health_warning');
                                            }
                                            // 不一致
                                            if ($hasMismatchesForEnable) {
                                                $enableWarnings[] = __('admin.settings.themes.permissions.install_warning_mismatch');
                                            }
                                            // 未スキャン
                                            if (!$auditedAtForEnable) {
                                                $enableWarnings[] = __('admin.settings.themes.permissions.warning_not_scanned');
                                            }
                                            
                                            $hasEnableWarnings = !empty($enableWarnings);
                                            $enableModalId = 'enableThemeModal-' . $theme->id;
                                        @endphp
                                        
                                        <form action="{{ route('admin.settings.themes.switch', $theme->id) }}" method="POST" class="inline-block" id="enableThemeForm-{{ $theme->id }}">
                                            @csrf
                                            @if($hasEnableWarnings)
                                                <x-form.button
                                                    type="button"
                                                    :label="__('common.enable')"
                                                    variant="success"
                                                    size="sm"
                                                    icon="fas fa-check"
                                                    onclick="openModal('{{ $enableModalId }}')"
                                                />
                                                
                                                <!-- 有効化確認モーダル -->
                                                <x-modal
                                                    :id="$enableModalId"
                                                    :title="__('admin.settings.themes.permissions.enable_warning_title')"
                                                    icon_type="warning"
                                                    :confirm_label="__('common.enable')"
                                                    :cancel_label="__('common.cancel')"
                                                    form="enableThemeForm-{{ $theme->id }}"
                                                    confirm_color="yellow">
                                                    <div class="text-left">
                                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                                            {{ str_replace('{name}', $theme->name, __('admin.settings.themes.index.switch.confirm_message')) }}
                                                        </p>
                                                        <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                                                            <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                {{ __('admin.settings.themes.permissions.enable_warning_message', ['name' => $theme->name]) }}
                                                            </p>
                                                            <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-4 list-disc">
                                                                @foreach($enableWarnings as $warning)
                                                                    <li>{{ $warning }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                                            {{ __('admin.settings.themes.permissions.enable_warning_confirm') }}
                                                        </p>
                                                    </div>
                                                </x-modal>
                                            @else
                                                <x-form.button
                                                    type="submit"
                                                    :label="__('common.enable')"
                                                    variant="success"
                                                    size="sm"
                                                    icon="fas fa-check"
                                                />
                                            @endif
                                        </form>

                                        <!-- アンインストールボタン（デフォルトテーマ以外） -->
                                        @php
                                            $defaultThemeSlug = config('themes.default_theme_slug', 'dixlase-default-theme');
                                        @endphp
                                        @if($theme->slug !== $defaultThemeSlug)
                                            <form action="{{ route('admin.settings.themes.uninstall', $theme->id) }}" method="POST" class="inline-block" id="uninstallThemeForm-{{ $theme->id }}">
                                                @csrf
                                                <x-form.button
                                                    type="button"
                                                    :label="__('common.uninstall')"
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fas fa-times"
                                                    onclick="openModal('uninstallThemeModal-{{ $theme->id }}')"
                                                />

                                                <!-- 確認画面のモーダル -->
                                                <x-modal
                                                    id="uninstallThemeModal-{{ $theme->id }}"
                                                    :title="__('admin.settings.themes.index.uninstall.confirm_title')"
                                                    :message="str_replace('{name}', $theme->name, __('admin.settings.themes.index.uninstall.confirm_message'))"
                                                    :confirm_label="__('common.uninstall')"
                                                    :cancel_label="__('common.cancel')"
                                                    :checkbox="true"
                                                    checkbox_name="remove_db_data"
                                                    checkbox_label="{!! __('admin.settings.themes.index.uninstall.remove_data_checkbox') !!}"
                                                    form="uninstallThemeForm-{{ $theme->id }}"
                                                    icon_type="danger"
                                                    confirm_color="red"
                                                />
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8">
                                <div class="empty-state">
                                    <i class="fas fa-palette text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500">{{ __('admin.settings.themes.index.no_themes') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- アンインストール済みテーマ一覧セクション -->
    @if(count($uninstalledThemes) > 0)
    <section class="mt-8">
        <h2>{{ __('admin.settings.themes.index.uninstalled_heading') }}</h2>
        

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.themes.index.uninstalled_table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('admin.settings.themes.index.table.name') }}</th>
                        <th>{{ __('common.details') }}</th>
                        <th>{{ __('admin.settings.themes.permissions.health_status') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($uninstalledThemes as $theme)
                        <tr>
                            <td data-label="{{ __('admin.settings.themes.index.table.name') }}">
                                <div>
                                    <strong class="text-lg">{{ $theme['name'] }}</strong>
                                    @if($theme['description'])
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $theme['description'] }}</div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="{{ __('common.details') }}">
                                <div class="text-sm space-y-1">
                                    <!-- 作者情報 -->
                                    <div class="mb-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('common.author') }}:</span>
                                        @if($theme['author'])
                                            <span>{{ $theme['author'] }}</span>
                                            @if($theme['email'])
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $theme['email'] }}</div>
                                            @endif
                                            @if($theme['url'])
                                                <div class="text-xs">
                                                    <a href="{{ $theme['url'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $theme['url'] }}</a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">{{ __('common.unknown') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- バージョン情報 -->
                                    <div class="inline-block font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $theme['version'] }}</div>
                                    <!-- ライセンス情報 -->
                                    <div>
                                        @if($theme['license'])
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">{{ $theme['license'] }}</div>
                                        @else
                                            <div class="inline-block text-gray-400">{{ __('common.unknown') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('admin.settings.themes.permissions.health_status') }}">
                                {{-- 権限・署名ステータスバッジ（モーダル表示） --}}
                                @if(isset($theme['permission_summary']))
                                    @php
                                        $hasPermissions = $theme['permission_summary']['has_permissions'];
                                        $riskLevel = $theme['permission_summary']['risk_level'];
                                        $categories = $theme['permission_summary']['categories'] ?? [];
                                        $signature = $theme['permission_summary']['signature'] ?? ['status' => 'unsigned'];
                                        $permissionModalId = 'permissionModal-uninstalled-' . $theme['directory'];
                                        
                                        // 署名ステータスに基づくバッジ設定
                                        $signatureStatus = $signature['status'] ?? 'unsigned';
                                        $signatureType = $signature['type'] ?? null;
                                        
                                        // バッジの色とアイコンを決定
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
                                                'official' => __('admin.settings.themes.permissions.signature_official'),
                                                'verified' => __('admin.settings.themes.permissions.signature_verified'),
                                                'partner' => __('admin.settings.themes.permissions.signature_partner'),
                                            ];
                                            $badgeColor = $badgeColors[$signatureType] ?? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                            $badgeIcon = $badgeIcons[$signatureType] ?? 'fas fa-check-circle';
                                            $badgeLabel = $badgeLabels[$signatureType] ?? __('admin.settings.themes.permissions.signature_signed');
                                        } elseif ($signatureStatus === 'invalid') {
                                            $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                            $badgeIcon = 'fas fa-times-circle';
                                            $badgeLabel = __('admin.settings.themes.permissions.signature_invalid');
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
                                            $badgeLabel = __('admin.settings.themes.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy'));
                                        } else {
                                            // 未署名 + 権限未定義 → 警告表示
                                            $badgeColor = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 border border-orange-300 dark:border-orange-700';
                                            $badgeIcon = 'fas fa-exclamation-triangle';
                                            $badgeLabel = __('admin.settings.themes.permissions.unknown');
                                        }
                                        
                                        // 監査結果
                                        $auditResult = $theme['permission_summary']['audit'] ?? [];
                                        $hasMismatches = $auditResult['has_mismatches'] ?? false;
                                    @endphp
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <button type="button" 
                                                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }} cursor-pointer hover:opacity-80 transition-opacity"
                                                onclick="openModal('{{ $permissionModalId }}')">
                                            <i class="{{ $badgeIcon }} mr-1"></i>
                                            {{ $badgeLabel }}
                                            <i class="fas fa-info-circle ml-1 text-xs opacity-60"></i>
                                        </button>
                                        @if($hasMismatches)
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" title="{{ __('admin.settings.themes.permissions.audit_mismatch_warning') }}">
                                                <i class="fas fa-code-branch mr-1"></i>
                                                {{ __('admin.settings.themes.permissions.audit_mismatch_badge') }}
                                            </span>
                                        @endif
                                        {{-- CSP診断バッジ --}}
                                        @if(isset($theme['csp_diagnostic']) && !$theme['csp_diagnostic']['compliant'])
                                            @php
                                                $cspIssuesUninstalled = $theme['csp_diagnostic']['summary']['total_issues'] ?? 0;
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200" title="{{ __('admin.settings.plugins.permissions.csp_warning_message') }}">
                                                <i class="fas fa-shield-alt mr-1"></i>
                                                CSP {{ __('admin.settings.plugins.permissions.csp_issues_found', ['count' => $cspIssuesUninstalled]) }}
                                            </span>
                                        @endif
                                        {{-- スキャンボタン --}}
                                        <button type="button"
                                                class="theme-audit-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                                data-slug="{{ $theme['slug'] }}"
                                                title="{{ ($auditResult['audited_at'] ?? null) ? __('admin.settings.themes.permissions.audit_last_scanned') . ': ' . $auditResult['audited_at'] : __('admin.settings.themes.permissions.audit_not_scanned') }}">
                                            <i class="fas fa-search mr-1"></i>
                                            <span class="audit-btn-text">{{ ($auditResult['audited_at'] ?? null) ? __('admin.settings.themes.permissions.audit_button_rescan') : __('admin.settings.themes.permissions.audit_button') }}</span>
                                        </button>
                                    </div>
                                    
                                    {{-- 権限・署名詳細モーダル --}}
                                    <x-modal
                                        :id="$permissionModalId"
                                        :title="__('admin.settings.themes.permissions.details_title') . ' - ' . $theme['name']"
                                        icon_type="info"
                                        :close_only="true"
                                        :close_label="__('common.close')"
                                    >
                                        <div class="text-left">
                                            {{-- 監査警告 --}}
                                            @if($hasMismatches)
                                                <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                                                    <h5 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
                                                        <i class="fas fa-code-branch mr-1"></i>
                                                        {{ __('admin.settings.themes.permissions.audit_mismatch_title') }}
                                                    </h5>
                                                    <p class="text-xs text-red-700 dark:text-red-300 mb-2">{{ __('admin.settings.themes.permissions.audit_mismatch_warning') }}</p>
                                                    <ul class="text-xs text-red-600 dark:text-red-400 space-y-1 ml-4 list-disc">
                                                        @foreach(array_slice($auditResult['mismatches'] ?? [], 0, 5) as $mismatch)
                                                            <li>
                                                                <code class="bg-red-100 dark:bg-red-800 px-1 rounded">{{ $mismatch['permission'] }}</code>
                                                                @if($mismatch['type'] === 'undeclared_usage')
                                                                    - {{ __('admin.settings.themes.permissions.audit_undeclared_usage') }}
                                                                @else
                                                                    - {{ __('admin.settings.themes.permissions.audit_unused_declaration') }}
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                            
                                            {{-- CSP診断警告 --}}
                                            @if(isset($theme['csp_diagnostic']) && !$theme['csp_diagnostic']['compliant'])
                                                <div class="mb-4 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                                                    <h5 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-2">
                                                        <i class="fas fa-shield-alt mr-1"></i>
                                                        {{ __('admin.settings.plugins.permissions.csp_warning_title') }}
                                                    </h5>
                                                    <p class="text-xs text-orange-700 dark:text-orange-300 mb-2">{{ __('admin.settings.plugins.permissions.csp_warning_message') }}</p>
                                                    <div class="text-xs text-orange-600 dark:text-orange-400 space-y-1">
                                                        <p><i class="fas fa-code mr-1"></i> {{ __('admin.settings.plugins.permissions.csp_inline_scripts') }}: {{ $theme['csp_diagnostic']['summary']['inline_scripts'] ?? 0 }}</p>
                                                        <p><i class="fas fa-paint-brush mr-1"></i> {{ __('admin.settings.plugins.permissions.csp_inline_styles') }}: {{ $theme['csp_diagnostic']['summary']['inline_styles'] ?? 0 }}</p>
                                                    </div>
                                                    <p class="text-xs text-orange-600 dark:text-orange-400 mt-2 italic">{{ __('admin.settings.plugins.permissions.csp_fix_suggestion') }}</p>
                                                </div>
                                            @endif
                                            
                                            {{-- 署名ステータス --}}
                                            <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.themes.permissions.signature_status') }}</h4>
                                                @if($signatureStatus === 'valid' || $signatureStatus === 'pending_verification')
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }}">
                                                            <i class="{{ $badgeIcon }} mr-1"></i>
                                                            {{ $badgeLabel }}
                                                        </span>
                                                    </div>
                                                    @if($signature['signed_by'] ?? null)
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                                            {{ __('admin.settings.themes.permissions.signed_by') }}: {{ $signature['signed_by'] }}
                                                        </p>
                                                    @endif
                                                @elseif($signatureStatus === 'invalid')
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                            <i class="fas fa-times-circle mr-1"></i>
                                                            {{ __('admin.settings.themes.permissions.signature_invalid') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-red-600 dark:text-red-400">
                                                        {{ __('admin.settings.themes.permissions.signature_invalid_warning') }}
                                                    </p>
                                                @else
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                            <i class="fas fa-file-signature mr-1"></i>
                                                            {{ __('admin.settings.themes.permissions.signature_unsigned') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ __('admin.settings.themes.permissions.signature_unsigned_info') }}
                                                    </p>
                                                @endif
                                            </div>
                                            
                                            {{-- 権限情報 --}}
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.themes.permissions.permission_info') }}</h4>
                                                @if($hasPermissions)
                                                    {{-- 健全性レベル表示 --}}
                                                    @php
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
                                                    @endphp
                                                    <div class="mb-3 flex items-center">
                                                        <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">{{ __('admin.settings.themes.permissions.health_status') }}:</span>
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $healthColors[$riskLevel] ?? $healthColors['low'] }}">
                                                            <i class="{{ $healthIcons[$riskLevel] ?? $healthIcons['low'] }} mr-1"></i>
                                                            {{ __('admin.settings.themes.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy')) }}
                                                        </span>
                                                    </div>
                                                    
                                                    {{-- カテゴリ別権限 --}}
                                                    @if(!empty($categories))
                                                        <div class="space-y-2">
                                                            @foreach($categories as $category => $permissions)
                                                                @if(!empty($permissions))
                                                                    <div class="p-2 bg-gray-50 dark:bg-gray-800 rounded">
                                                                        <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                                                            {{ __('admin.settings.themes.permissions.category_' . $category) }}
                                                                        </h5>
                                                                        <div class="flex flex-wrap gap-1">
                                                                            @foreach($permissions as $perm)
                                                                                <span class="inline-block px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs">
                                                                                    {{ __('admin.settings.themes.permissions.perm_' . $perm) }}
                                                                                </span>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ __('admin.settings.themes.permissions.no_special_permissions') }}
                                                        </p>
                                                    @endif
                                                @else
                                                    <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                                                        <p class="text-sm text-orange-700 dark:text-orange-300">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                                            {{ __('admin.settings.themes.permissions.no_permissions_defined') }}
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </x-modal>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            <td data-label="{{ __('common.actions') }}">
                                <div class="action-buttons">
                                    <!-- インストールボタン -->
                                    @php
                                        $installAudit = $theme['permission_summary']['audit'] ?? [];
                                        $installHasMismatches = $installAudit['has_mismatches'] ?? false;
                                        $installIsNotScanned = empty($installAudit['audited_at'] ?? null);
                                        $installIsUnsigned = ($theme['permission_summary']['signature']['status'] ?? 'unsigned') === 'unsigned';
                                        $installIsUndefined = !($theme['permission_summary']['has_permissions'] ?? false);
                                        $installRiskLevel = $theme['permission_summary']['risk_level'] ?? 'unknown';
                                        $installHasWarnings = $installHasMismatches || $installIsUnsigned || $installIsUndefined || $installIsNotScanned || in_array($installRiskLevel, ['medium', 'high']);
                                    @endphp
                                    <form action="{{ route('admin.settings.themes.install') }}" method="POST" class="inline-block" id="installThemeForm-{{ $theme['directory'] }}">
                                        @csrf
                                        <input type="hidden" name="directory" value="{{ $theme['directory'] }}">
                                        <x-form.button
                                            type="button"
                                            :label="__('common.install')"
                                            variant="success"
                                            size="sm"
                                            icon="fas fa-download"
                                            onclick="openModal('installThemeModal-{{ $theme['directory'] }}')"
                                        />

                                        <!-- インストール確認モーダル（警告付き） -->
                                        <x-modal
                                            id="installThemeModal-{{ $theme['directory'] }}"
                                            :title="$installHasWarnings ? __('admin.settings.themes.permissions.install_warning_title') : __('admin.settings.themes.index.install.confirm_title')"
                                            :confirm_label="__('common.install')"
                                            :cancel_label="__('common.cancel')"
                                            form="installThemeForm-{{ $theme['directory'] }}"
                                            :icon_type="$installHasWarnings ? 'warning' : 'info'"
                                            :confirm_color="$installHasWarnings ? 'yellow' : 'green'"
                                        >
                                            @if($installHasWarnings)
                                                <div class="text-left">
                                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                                        {{ str_replace('{name}', $theme['name'], __('admin.settings.themes.index.install.confirm_message')) }}
                                                    </p>
                                                    <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                                                        <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                                            {{ __('admin.settings.themes.permissions.install_warning_risk') }}
                                                        </p>
                                                        <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-5 list-disc">
                                                            @if($installIsUndefined)
                                                                <li>{{ __('admin.settings.themes.permissions.install_warning_undefined') }}</li>
                                                            @endif
                                                            @if($installIsUnsigned)
                                                                <li>{{ __('admin.settings.themes.permissions.install_warning_unsigned') }}</li>
                                                            @endif
                                                            @if($installHasMismatches)
                                                                <li>{{ __('admin.settings.themes.permissions.install_warning_mismatch') }}</li>
                                                            @endif
                                                            @if($installIsNotScanned)
                                                                <li>{{ __('admin.settings.themes.permissions.warning_not_scanned') }}</li>
                                                            @endif
                                                            @if(in_array($installRiskLevel, ['medium', 'high']))
                                                                <li>{{ __('admin.settings.themes.permissions.risk_' . $installRiskLevel) }}</li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ __('admin.settings.themes.permissions.install_warning_confirm') }}
                                                    </p>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                                    {{ str_replace('{name}', $theme['name'], __('admin.settings.themes.index.install.confirm_message')) }}
                                                </p>
                                            @endif
                                        </x-modal>
                                    </form>

                                    <!-- 削除ボタン（デフォルトテーマ以外） -->
                                    @php
                                        $defaultThemeSlug = config('themes.default_theme_slug', 'dixlase-default-theme');
                                    @endphp
                                    @if($theme['slug'] !== $defaultThemeSlug)
                                        <form action="{{ route('admin.settings.themes.delete') }}" method="POST" class="inline-block" id="deleteThemeForm-{{ $theme['directory'] }}">
                                            @csrf
                                            <input type="hidden" name="directory" value="{{ $theme['directory'] }}">
                                            <x-form.button
                                                type="button"
                                                :label="__('common.delete')"
                                                variant="danger"
                                                size="sm"
                                                icon="fas fa-trash"
                                                onclick="openModal('deleteThemeModal-{{ $theme['directory'] }}')"
                                            />

                                            <!-- 削除確認モーダル -->
                                            <x-modal
                                                id="deleteThemeModal-{{ $theme['directory'] }}"
                                                :title="__('admin.settings.themes.index.delete.confirm_title')"
                                                :message="str_replace('{name}', $theme['name'], __('admin.settings.themes.index.delete.confirm_message'))"
                                                :confirm_label="__('common.delete')"
                                                :cancel_label="__('common.cancel')"
                                                form="deleteThemeForm-{{ $theme['directory'] }}"
                                                icon_type="danger"
                                                confirm_color="red"
                                            />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 監査メッセージ（翻訳対応）
    const auditMessages = {
        scanning: @json(__('admin.settings.themes.permissions.audit_scanning')),
        rescan: @json(__('admin.settings.themes.permissions.audit_button_rescan')),
        failed: @json(__('admin.settings.themes.audit.failed')),
        resultTitle: @json(__('admin.settings.themes.permissions.audit_result_title')),
        mismatchFound: @json(__('admin.settings.themes.permissions.audit_mismatch_found')),
        undeclaredUsage: @json(__('admin.settings.themes.permissions.audit_undeclared_usage')),
        unusedDeclaration: @json(__('admin.settings.themes.permissions.audit_unused_declaration')),
        noIssues: @json(__('admin.settings.themes.permissions.audit_no_issues')),
        close: @json(__('common.close')),
    };
    
    // 監査結果モーダルを表示
    function showAuditResultModal(slug, audit) {
        // 既存のモーダルがあれば削除
        const modalId = 'auditResultModal-' + slug;
        const existingModal = document.getElementById(modalId);
        if (existingModal) {
            existingModal.remove();
        }
        
        // モーダルHTML作成
        let contentHtml = '';
        if (audit.has_mismatches && audit.mismatches && audit.mismatches.length > 0) {
            contentHtml = `
                <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        ${auditMessages.mismatchFound}
                    </p>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-5 list-disc">
                        ${audit.mismatches.slice(0, 10).map(m => `
                            <li>
                                <code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">${m.permission}</code>
                                - ${m.type === 'undeclared_usage' ? auditMessages.undeclaredUsage : auditMessages.unusedDeclaration}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        } else {
            contentHtml = `
                <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                    <p class="text-sm text-green-700 dark:text-green-300">
                        <i class="fas fa-check-circle mr-1"></i>
                        ${auditMessages.noIssues}
                    </p>
                </div>
            `;
        }
        
        // 統計情報
        contentHtml += `
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                {{ __('admin.settings.themes.permissions.audit_stats') }}: ${audit.total_checked || 0} / {{ __('admin.settings.themes.permissions.audit_matches') }}: ${audit.matches_count || 0} / {{ __('admin.settings.themes.permissions.audit_mismatches') }}: ${(audit.mismatches || []).length}
            </div>
        `;
        
        const modalHtml = `
            <div id="${modalId}" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(0,0,0,0.5);">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            <i class="fas fa-search mr-2"></i>${auditMessages.resultTitle}
                        </h3>
                        <button type="button" onclick="window.location.reload()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        ${contentHtml}
                    </div>
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <button type="button" onclick="window.location.reload()" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700">
                            ${auditMessages.close}
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    // テーマ監査ボタンのイベントリスナー
    document.querySelectorAll('.theme-audit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const slug = this.dataset.slug;
            const btnText = this.querySelector('.audit-btn-text');
            const originalText = btnText.textContent;
            const icon = this.querySelector('i');
            const originalIcon = icon.className;
            const button = this;
            
            // ボタンを無効化してスピナー表示
            button.disabled = true;
            icon.className = 'fas fa-spinner fa-spin mr-1';
            btnText.textContent = auditMessages.scanning;
            
            // AJAX リクエスト
            fetch('{{ route('admin.settings.themes.audit') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ slug: slug })
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 419) {
                        throw new Error('CSRF token mismatch. Please reload the page.');
                    }
                    throw new Error('HTTP error ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Theme audit response:', data);
                
                // ボタンを元に戻す
                button.disabled = false;
                btnText.textContent = auditMessages.rescan;
                icon.className = originalIcon;
                
                if (data.success) {
                    // 結果をモーダルで表示
                    showAuditResultModal(slug, data.audit);
                } else {
                    alert(data.message || auditMessages.failed);
                }
            })
            .catch(error => {
                console.error('Theme audit error:', error);
                alert(error.message || auditMessages.failed);
                // ボタンを元に戻す
                button.disabled = false;
                btnText.textContent = originalText;
                icon.className = originalIcon;
            });
        });
    });
});
</script>
@endpush
