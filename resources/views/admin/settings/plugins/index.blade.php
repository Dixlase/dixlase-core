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
    <!-- インストール済みプラグイン一覧セクション -->
    <section>
        <h2>{{ __('admin.settings.plugins.index.installed_heading') }}</h2>

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.plugins.index.table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('admin.settings.plugins.index.table.name') }}</th>
                        <th>{{ __('common.details') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plugins as $plugin)
                        <tr>
                            <td data-label="{{ __('admin.settings.plugins.index.table.id') }}">
                                <span class="text-sm font-mono px-2 py-1 rounded">{{ $plugin->id }}</span>
                            </td>
                            <td data-label="{{ __('admin.settings.plugins.index.table.name') }}">
                                <div>
                                    <strong class="text-lg">{{ $plugin->translated_name }}</strong>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $plugin->translated_description }}</div>
                                </div>
                            </td>
                            <td data-label="{{ __('common.details') }}">
                                <div class="text-sm space-y-1">
                                    <!-- 作者情報 -->
                                    <div class="mb-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('common.author') }}:</span>
                                        @if($plugin->author)
                                            <span>{{ $plugin->author }}</span>
                                            @if($plugin->email)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $plugin->email }}</div>
                                            @endif
                                            @if($plugin->web)
                                                <div class="text-xs">
                                                    <a href="{{ $plugin->web }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $plugin->web }}</a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">{{ __('common.unknown') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- バージョン情報 -->
                                    <div class="inline-block font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $plugin->version }}</div>
                                    <!-- ライセンス情報 -->
                                    <div>
                                        @if($plugin->license)
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">{{ $plugin->license }}</div>
                                        @else
                                            <div class="inline-block text-gray-400">{{ __('common.unknown') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('common.status') }}">
                                <div class="space-y-2">
                                    <span class="status-badge status-badge--{{ $plugin->isEnabled() ? 'enabled' : 'disabled' }}">
                                        {{ $plugin->isEnabled() ? __('common.enabled') : __('common.disabled') }}
                                    </span>
                                    
                                    {{-- 権限・署名ステータスバッジ（モーダル表示） --}}
                                    @if(isset($plugin->permission_summary))
                                        @php
                                            $hasPermissions = $plugin->permission_summary['has_permissions'];
                                            $riskLevel = $plugin->permission_summary['risk_level'];
                                            $categories = $plugin->permission_summary['categories'] ?? [];
                                            $signature = $plugin->permission_summary['signature'] ?? ['status' => 'unsigned'];
                                            $permissionModalId = 'permissionModal-' . $plugin->id;
                                            
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
                                                    'official' => __('admin.settings.plugins.permissions.signature_official'),
                                                    'verified' => __('admin.settings.plugins.permissions.signature_verified'),
                                                    'partner' => __('admin.settings.plugins.permissions.signature_partner'),
                                                ];
                                                $badgeColor = $badgeColors[$signatureType] ?? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                                $badgeIcon = $badgeIcons[$signatureType] ?? 'fas fa-check-circle';
                                                $badgeLabel = $badgeLabels[$signatureType] ?? __('admin.settings.plugins.permissions.signature_signed');
                                            } elseif ($signatureStatus === 'invalid') {
                                                // 署名無効
                                                $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                                $badgeIcon = 'fas fa-times-circle';
                                                $badgeLabel = __('admin.settings.plugins.permissions.signature_invalid');
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
                                                $badgeLabel = __('admin.settings.plugins.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy'));
                                            } else {
                                                // 未署名 + 権限未定義 → 警告表示
                                                $badgeColor = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 border border-orange-300 dark:border-orange-700';
                                                $badgeIcon = 'fas fa-exclamation-triangle';
                                                $badgeLabel = __('admin.settings.plugins.permissions.unknown');
                                            }
                                            
                                            // 監査結果
                                            $auditResult = $plugin->permission_summary['audit'] ?? [];
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
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" title="{{ __('admin.settings.plugins.permissions.audit_mismatch_warning') }}">
                                                        <i class="fas fa-code-branch mr-1"></i>
                                                        {{ __('admin.settings.plugins.permissions.audit_mismatch_badge') }}
                                                    </span>
                                                @endif
                                                {{-- CSP診断バッジ --}}
                                                @if(isset($plugin->csp_diagnostic) && !$plugin->csp_diagnostic['compliant'])
                                                    @php
                                                        $cspIssues = $plugin->csp_diagnostic['summary']['total_issues'] ?? 0;
                                                    @endphp
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200" title="{{ __('admin.settings.plugins.permissions.csp_warning_message') }}">
                                                        <i class="fas fa-shield-alt mr-1"></i>
                                                        CSP {{ __('admin.settings.plugins.permissions.csp_issues_found', ['count' => $cspIssues]) }}
                                                    </span>
                                                @endif
                                                {{-- スキャンボタン --}}
                                                <button type="button"
                                                        class="audit-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                                        data-slug="{{ $plugin->slug }}"
                                                        title="{{ $auditResult['audited_at'] ? __('admin.settings.plugins.permissions.audit_last_scanned') . ': ' . $auditResult['audited_at'] : __('admin.settings.plugins.permissions.audit_not_scanned') }}">
                                                    <i class="fas fa-search mr-1"></i>
                                                    <span class="audit-btn-text">{{ $auditResult['audited_at'] ? __('admin.settings.plugins.permissions.audit_button_rescan') : __('admin.settings.plugins.permissions.audit_button') }}</span>
                                                </button>
                                            </div>
                                            
                                            {{-- 権限・署名詳細モーダル --}}
                                            <x-modal
                                                :id="$permissionModalId"
                                                :title="__('admin.settings.plugins.permissions.details_title') . ' - ' . $plugin->translated_name"
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
                                                                {{ __('admin.settings.plugins.permissions.audit_mismatch_title') }}
                                                            </h5>
                                                            <p class="text-xs text-red-700 dark:text-red-300 mb-2">{{ __('admin.settings.plugins.permissions.audit_mismatch_warning') }}</p>
                                                            <ul class="text-xs text-red-600 dark:text-red-400 space-y-1 ml-4 list-disc">
                                                                @foreach(array_slice($auditResult['mismatches'] ?? [], 0, 5) as $mismatch)
                                                                    <li>
                                                                        <code class="bg-red-100 dark:bg-red-800 px-1 rounded">{{ $mismatch['permission'] }}</code>
                                                                        @if($mismatch['type'] === 'undeclared_usage')
                                                                            - {{ __('admin.settings.plugins.permissions.audit_undeclared_usage') }}
                                                                        @else
                                                                            - {{ __('admin.settings.plugins.permissions.audit_unused_declaration') }}
                                                                        @endif
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                    
                                                    {{-- CSP診断警告 --}}
                                                    @if(isset($plugin->csp_diagnostic) && !$plugin->csp_diagnostic['compliant'])
                                                        <div class="mb-4 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                                                            <h5 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-2">
                                                                <i class="fas fa-shield-alt mr-1"></i>
                                                                {{ __('admin.settings.plugins.permissions.csp_warning_title') }}
                                                            </h5>
                                                            <p class="text-xs text-orange-700 dark:text-orange-300 mb-2">{{ __('admin.settings.plugins.permissions.csp_warning_message') }}</p>
                                                            <div class="text-xs text-orange-600 dark:text-orange-400 space-y-1">
                                                                <p><i class="fas fa-code mr-1"></i> {{ __('admin.settings.plugins.permissions.csp_inline_scripts') }}: {{ $plugin->csp_diagnostic['summary']['inline_scripts'] ?? 0 }}</p>
                                                                <p><i class="fas fa-paint-brush mr-1"></i> {{ __('admin.settings.plugins.permissions.csp_inline_styles') }}: {{ $plugin->csp_diagnostic['summary']['inline_styles'] ?? 0 }}</p>
                                                            </div>
                                                            <p class="text-xs text-orange-600 dark:text-orange-400 mt-2 italic">{{ __('admin.settings.plugins.permissions.csp_fix_suggestion') }}</p>
                                                        </div>
                                                    @endif
                                                    
                                                    {{-- 署名ステータス --}}
                                                    <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.plugins.permissions.signature_status') }}</h4>
                                                        @if($signatureStatus === 'valid' || $signatureStatus === 'pending_verification')
                                                            <div class="flex items-center mb-2">
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }}">
                                                                    <i class="{{ $badgeIcon }} mr-1"></i>
                                                                    {{ $badgeLabel }}
                                                                </span>
                                                            </div>
                                                            @if($signature['signed_by'])
                                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                                    {{ __('admin.settings.plugins.permissions.signed_by') }}: {{ $signature['signed_by'] }}
                                                                </p>
                                                            @endif
                                                        @elseif($signatureStatus === 'invalid')
                                                            <div class="flex items-center mb-2">
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                                    <i class="fas fa-times-circle mr-1"></i>
                                                                    {{ __('admin.settings.plugins.permissions.signature_invalid') }}
                                                                </span>
                                                            </div>
                                                            <p class="text-sm text-red-600 dark:text-red-400">
                                                                {{ __('admin.settings.plugins.permissions.signature_invalid_warning') }}
                                                            </p>
                                                        @else
                                                            <div class="flex items-center mb-2">
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                                    <i class="fas fa-file-signature mr-1"></i>
                                                                    {{ __('admin.settings.plugins.permissions.signature_unsigned') }}
                                                                </span>
                                                            </div>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                                {{ __('admin.settings.plugins.permissions.signature_unsigned_info') }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    
                                                    {{-- 権限情報 --}}
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.plugins.permissions.permission_info') }}</h4>
                                                        @if($hasPermissions)
                                                            {{-- 健全性レベル表示 --}}
                                                            @php
                                                                $attentionReasons = $plugin->permission_summary['risk_reasons'] ?? [];
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
                                                                <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">{{ __('admin.settings.plugins.permissions.health_status') }}:</span>
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $healthColors[$riskLevel] ?? $healthColors['low'] }}">
                                                                    <i class="{{ $healthIcons[$riskLevel] ?? $healthIcons['low'] }} mr-1"></i>
                                                                    {{ __('admin.settings.plugins.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy')) }}
                                                                </span>
                                                            </div>
                                                            
                                                            {{-- 確認が必要な理由 --}}
                                                            @if(!empty($attentionReasons))
                                                                <div class="mb-4 p-3 rounded-lg {{ $riskLevel === 'high' ? 'bg-orange-50 dark:bg-orange-900/20' : ($riskLevel === 'medium' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800') }}">
                                                                    <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                                        <i class="fas fa-info-circle mr-1"></i>
                                                                        {{ __('admin.settings.plugins.permissions.attention_reasons_title') }}
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
                                                                                <span>{{ __('admin.settings.plugins.permissions.attention_reason_' . $reasonKey) }}</span>
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
                                                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.plugins.permissions.category_' . $category) }}:</span>
                                                                            <div class="mt-1 flex flex-wrap gap-1">
                                                                                @foreach($permissions as $perm)
                                                                                    <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-xs">
                                                                                        {{ __('admin.settings.plugins.permissions.perm_' . $perm) }}
                                                                                    </span>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.plugins.permissions.no_special_permissions') }}</p>
                                                            @endif
                                                        @else
                                                            {{-- 権限未定義 → 警告 --}}
                                                            <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                                                                <div class="flex items-start">
                                                                    <i class="fas fa-exclamation-triangle text-orange-500 dark:text-orange-400 mr-2 mt-0.5"></i>
                                                                    <p class="text-sm text-orange-700 dark:text-orange-300">{{ __('admin.settings.plugins.permissions.unknown_warning') }}</p>
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
                                    <!-- 設定画面リンク -->
                                    @if ($plugin->isEnabled() && isset($plugin->has_settings) && $plugin->has_settings)
                                        @php
                                            $settingsUrl = app('App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController')->getPluginSettingsUrl($plugin);
                                        @endphp
                                        @if ($settingsUrl)
                                            <a href="{{ $settingsUrl }}" class="inline-block">
                                                <x-form.button
                                                    type="button"
                                                    label="設定"
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fas fa-cog"
                                                />
                                            </a>
                                        @endif
                                    @endif

                                    @if ($plugin->isEnabled())
                                        <!-- 有効化中：無効化ボタンのみ -->
                                        <form action="{{ route('admin.settings.plugins.disable', $plugin->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            <x-form.button
                                                type="submit"
                                                :label="__('common.disable')"
                                                variant="warning"
                                                size="sm"
                                                icon="fas fa-pause"
                                            />
                                        </form>
                                    @else
                                        <!-- 無効化中：有効化とアンインストールボタン -->
                                        @php
                                            // 有効化時の警告条件を判定
                                            $enableWarnings = [];
                                            $signatureStatus = $plugin->permission_summary['signature']['status'] ?? 'unsigned';
                                            $riskLevel = $plugin->permission_summary['risk_level'] ?? 'low';
                                            $hasPermissions = !empty($plugin->permission_summary['permissions'] ?? []);
                                            $hasMismatchesForEnable = $plugin->permission_summary['audit']['has_mismatches'] ?? false;
                                            $auditedAtForEnable = $plugin->permission_summary['audit']['audited_at'] ?? null;
                                            
                                            // 署名無効
                                            if ($signatureStatus === 'invalid') {
                                                $enableWarnings[] = __('admin.settings.plugins.permissions.enable_warning_invalid_signature');
                                            }
                                            // 未署名
                                            if ($signatureStatus === 'unsigned' || $signatureStatus === 'none') {
                                                $enableWarnings[] = __('admin.settings.plugins.permissions.install_warning_unsigned');
                                            }
                                            // 権限未定義
                                            if (!$hasPermissions) {
                                                $enableWarnings[] = __('admin.settings.plugins.permissions.install_warning_undefined');
                                            }
                                            // 高リスク
                                            if ($riskLevel === 'high') {
                                                $enableWarnings[] = __('admin.settings.plugins.permissions.enable_warning_high_risk');
                                            }
                                            // 不一致
                                            if ($hasMismatchesForEnable) {
                                                $enableWarnings[] = __('admin.settings.plugins.permissions.install_warning_mismatch');
                                            }
                                            // 未スキャン
                                            if (!$auditedAtForEnable) {
                                                $enableWarnings[] = __('admin.settings.plugins.permissions.warning_not_scanned');
                                            }
                                            
                                            $hasEnableWarnings = !empty($enableWarnings);
                                            $enableModalId = 'enableModal-' . $plugin->id;
                                        @endphp
                                        
                                        <form action="{{ route('admin.settings.plugins.enable', $plugin->id) }}" method="POST" class="inline-block" id="enableForm-{{ $plugin->id }}">
                                            @csrf
                                            @if($hasEnableWarnings)
                                                <x-form.button
                                                    type="button"
                                                    :label="__('common.enable')"
                                                    variant="success"
                                                    size="sm"
                                                    icon="fas fa-play"
                                                    onclick="openModal('{{ $enableModalId }}')"
                                                />
                                                
                                                <!-- 有効化確認モーダル -->
                                                <x-modal
                                                    :id="$enableModalId"
                                                    :title="__('admin.settings.plugins.permissions.enable_warning_title')"
                                                    icon_type="warning"
                                                    :confirm_label="__('common.enable')"
                                                    :cancel_label="__('common.cancel')"
                                                    form="enableForm-{{ $plugin->id }}"
                                                    confirm_color="yellow">
                                                    <div class="text-left">
                                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                                            {{ str_replace('{name}', $plugin['name'], __('admin.settings.plugins.index.enabled.confirm_message')) }}
                                                        </p>
                                                        <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                                                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                                                {{ __('admin.settings.plugins.permissions.enable_warning_message', ['name' => $plugin->translated_name]) }}
                                                            </p>
                                                            <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-4 list-disc">
                                                                @foreach($enableWarnings as $warning)
                                                                    <li>{{ $warning }}</li>
                                                                @endforeach
                                                            </ul>
                                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                                                                {{ __('admin.settings.plugins.permissions.enable_warning_confirm') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </x-modal>
                                            @else
                                                <x-form.button
                                                    type="submit"
                                                    :label="__('common.enable')"
                                                    variant="success"
                                                    size="sm"
                                                    icon="fas fa-play"
                                                />
                                            @endif
                                        </form>

                                        <form action="{{ route('admin.settings.plugins.uninstall', $plugin->id) }}" method="POST" class="inline-block" id="uninstallForm-{{ $plugin->id }}">
                                            @csrf
                                            <x-form.button
                                                type="button"
                                                :label="__('common.uninstall')"
                                                variant="danger"
                                                size="sm"
                                                icon="fas fa-trash"
                                                onclick="openModal('uninstallModal-{{ $plugin->id }}')"
                                            />

                                            <!-- 確認画面のモーダル -->
                                            <x-modal
                                                id="uninstallModal-{{ $plugin->id }}"
                                                :title="__('admin.settings.plugins.index.uninstall.confirm_title')"
                                                :message="str_replace('{name}', $plugin->name, __('admin.settings.plugins.index.uninstall.confirm_message'))"
                                                :confirm_label="__('common.uninstall')"
                                                :cancel_label="__('common.cancel')"
                                                :checkbox="true"
                                                checkbox_name="remove_db_data"
                                                checkbox_label="{!! __('admin.settings.plugins.index.uninstall.remove_data_checkbox') !!}"
                                                form="uninstallForm-{{ $plugin->id }}"
                                                icon_type="danger"
                                                confirm_color="red"
                                            />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8">
                                <div class="empty-state">
                                    <i class="fas fa-puzzle-piece text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500">{{ __('admin.settings.plugins.index.no_plugins') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- アンインストール済みプラグイン一覧セクション -->
    @if(count($uninstalledPlugins) > 0)
    <section class="mt-8">
        <h2>{{ __('admin.settings.plugins.index.uninstalled_heading') }}</h2>

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.plugins.index.uninstalled_table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('admin.settings.plugins.index.table.name') }}</th>
                        <th>{{ __('common.details') }}</th>
                        <th>{{ __('admin.settings.plugins.permissions.health_status') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($uninstalledPlugins as $plugin)
                        <tr>
                            <td data-label="{{ __('admin.settings.plugins.index.table.name') }}">
                                <div>
                                    <strong class="text-lg">{{ $plugin['name'] }}</strong>
                                    @if($plugin['description'])
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $plugin['description'] }}</div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="{{ __('common.details') }}">
                                <div class="text-sm space-y-1">
                                    <!-- 作者情報 -->
                                    <div class="mb-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('common.author') }}:</span>
                                        @if($plugin['author'])
                                            <span>{{ $plugin['author'] }}</span>
                                            @if($plugin['email'])
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $plugin['email'] }}</div>
                                            @endif
                                            @if($plugin['url'])
                                                <div class="text-xs">
                                                    <a href="{{ $plugin['url'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $plugin['url'] }}</a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">{{ __('common.unknown') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- バージョン情報 -->
                                    <div class="inline-block font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $plugin['version'] }}</div>
                                    <!-- ライセンス情報 -->
                                    <div>
                                        @if($plugin['license'])
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">{{ $plugin['license'] }}</div>
                                        @else
                                            <div class="inline-block text-gray-400">{{ __('common.unknown') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('admin.settings.plugins.permissions.health_status') }}">
                                {{-- 権限・署名ステータスバッジ（モーダル表示） --}}
                                @if(isset($plugin['permission_summary']))
                                    @php
                                        $hasPermissions = $plugin['permission_summary']['has_permissions'];
                                        $riskLevel = $plugin['permission_summary']['risk_level'];
                                        $categories = $plugin['permission_summary']['categories'] ?? [];
                                        $signature = $plugin['permission_summary']['signature'] ?? ['status' => 'unsigned'];
                                        $permissionModalId = 'permissionModal-uninstalled-' . $plugin['directory'];
                                        
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
                                                'official' => __('admin.settings.plugins.permissions.signature_official'),
                                                'verified' => __('admin.settings.plugins.permissions.signature_verified'),
                                                'partner' => __('admin.settings.plugins.permissions.signature_partner'),
                                            ];
                                            $badgeColor = $badgeColors[$signatureType] ?? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                            $badgeIcon = $badgeIcons[$signatureType] ?? 'fas fa-check-circle';
                                            $badgeLabel = $badgeLabels[$signatureType] ?? __('admin.settings.plugins.permissions.signature_signed');
                                        } elseif ($signatureStatus === 'invalid') {
                                            $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                            $badgeIcon = 'fas fa-times-circle';
                                            $badgeLabel = __('admin.settings.plugins.permissions.signature_invalid');
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
                                            $badgeLabel = __('admin.settings.plugins.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy'));
                                        } else {
                                            // 未署名 + 権限未定義 → 警告表示
                                            $badgeColor = 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 border border-orange-300 dark:border-orange-700';
                                            $badgeIcon = 'fas fa-exclamation-triangle';
                                            $badgeLabel = __('admin.settings.plugins.permissions.unknown');
                                        }
                                        
                                        // 監査結果
                                        $auditResult = $plugin['permission_summary']['audit'] ?? [];
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
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200" title="{{ __('admin.settings.plugins.permissions.audit_mismatch_warning') }}">
                                                <i class="fas fa-code-branch mr-1"></i>
                                                {{ __('admin.settings.plugins.permissions.audit_mismatch_badge') }}
                                            </span>
                                        @endif
                                        {{-- CSP診断バッジ --}}
                                        @if(isset($plugin['csp_diagnostic']) && !$plugin['csp_diagnostic']['compliant'])
                                            @php
                                                $cspIssuesUninstalled = $plugin['csp_diagnostic']['summary']['total_issues'] ?? 0;
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200" title="{{ __('admin.settings.plugins.permissions.csp_warning_message') }}">
                                                <i class="fas fa-shield-alt mr-1"></i>
                                                CSP {{ __('admin.settings.plugins.permissions.csp_issues_found', ['count' => $cspIssuesUninstalled]) }}
                                            </span>
                                        @endif
                                        {{-- スキャンボタン --}}
                                        <button type="button"
                                                class="audit-btn inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                                data-slug="{{ $plugin['slug'] }}"
                                                title="{{ ($auditResult['audited_at'] ?? null) ? __('admin.settings.plugins.permissions.audit_last_scanned') . ': ' . $auditResult['audited_at'] : __('admin.settings.plugins.permissions.audit_not_scanned') }}">
                                            <i class="fas fa-search mr-1"></i>
                                            <span class="audit-btn-text">{{ ($auditResult['audited_at'] ?? null) ? __('admin.settings.plugins.permissions.audit_button_rescan') : __('admin.settings.plugins.permissions.audit_button') }}</span>
                                        </button>
                                    </div>
                                    
                                    {{-- 権限・署名詳細モーダル --}}
                                    <x-modal
                                        :id="$permissionModalId"
                                        :title="__('admin.settings.plugins.permissions.details_title') . ' - ' . $plugin['name']"
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
                                                        {{ __('admin.settings.plugins.permissions.audit_mismatch_title') }}
                                                    </h5>
                                                    <p class="text-xs text-red-700 dark:text-red-300 mb-2">{{ __('admin.settings.plugins.permissions.audit_mismatch_warning') }}</p>
                                                    <ul class="text-xs text-red-600 dark:text-red-400 space-y-1 ml-4 list-disc">
                                                        @foreach(array_slice($auditResult['mismatches'] ?? [], 0, 5) as $mismatch)
                                                            <li>
                                                                <code class="bg-red-100 dark:bg-red-800 px-1 rounded">{{ $mismatch['permission'] }}</code>
                                                                @if($mismatch['type'] === 'undeclared_usage')
                                                                    - {{ __('admin.settings.plugins.permissions.audit_undeclared_usage') }}
                                                                @else
                                                                    - {{ __('admin.settings.plugins.permissions.audit_unused_declaration') }}
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                            
                                            {{-- CSP診断警告 --}}
                                            @if(isset($plugin['csp_diagnostic']) && !$plugin['csp_diagnostic']['compliant'])
                                                <div class="mb-4 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                                                    <h5 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-2">
                                                        <i class="fas fa-shield-alt mr-1"></i>
                                                        {{ __('admin.settings.plugins.permissions.csp_warning_title') }}
                                                    </h5>
                                                    <p class="text-xs text-orange-700 dark:text-orange-300 mb-2">{{ __('admin.settings.plugins.permissions.csp_warning_message') }}</p>
                                                    <div class="text-xs text-orange-600 dark:text-orange-400 space-y-1">
                                                        <p><i class="fas fa-code mr-1"></i> {{ __('admin.settings.plugins.permissions.csp_inline_scripts') }}: {{ $plugin['csp_diagnostic']['summary']['inline_scripts'] ?? 0 }}</p>
                                                        <p><i class="fas fa-paint-brush mr-1"></i> {{ __('admin.settings.plugins.permissions.csp_inline_styles') }}: {{ $plugin['csp_diagnostic']['summary']['inline_styles'] ?? 0 }}</p>
                                                    </div>
                                                    <p class="text-xs text-orange-600 dark:text-orange-400 mt-2 italic">{{ __('admin.settings.plugins.permissions.csp_fix_suggestion') }}</p>
                                                </div>
                                            @endif
                                            
                                            {{-- 署名ステータス --}}
                                            <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.plugins.permissions.signature_status') }}</h4>
                                                @if($signatureStatus === 'valid' || $signatureStatus === 'pending_verification')
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }}">
                                                            <i class="{{ $badgeIcon }} mr-1"></i>
                                                            {{ $badgeLabel }}
                                                        </span>
                                                    </div>
                                                    @if($signature['signed_by'] ?? null)
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                                            {{ __('admin.settings.plugins.permissions.signed_by') }}: {{ $signature['signed_by'] }}
                                                        </p>
                                                    @endif
                                                @elseif($signatureStatus === 'invalid')
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                            <i class="fas fa-times-circle mr-1"></i>
                                                            {{ __('admin.settings.plugins.permissions.signature_invalid') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-red-600 dark:text-red-400">
                                                        {{ __('admin.settings.plugins.permissions.signature_invalid_warning') }}
                                                    </p>
                                                @else
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                            <i class="fas fa-file-signature mr-1"></i>
                                                            {{ __('admin.settings.plugins.permissions.signature_unsigned') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ __('admin.settings.plugins.permissions.signature_unsigned_info') }}
                                                    </p>
                                                @endif
                                            </div>
                                            
                                            {{-- 権限情報 --}}
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.plugins.permissions.permission_info') }}</h4>
                                                @if($hasPermissions)
                                                    {{-- 健全性レベル表示 --}}
                                                    @php
                                                        $attentionReasons = $plugin['permission_summary']['risk_reasons'] ?? [];
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
                                                        <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">{{ __('admin.settings.plugins.permissions.health_status') }}:</span>
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $healthColors[$riskLevel] ?? $healthColors['low'] }}">
                                                            <i class="{{ $healthIcons[$riskLevel] ?? $healthIcons['low'] }} mr-1"></i>
                                                            {{ __('admin.settings.plugins.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy')) }}
                                                        </span>
                                                    </div>
                                                    
                                                    {{-- 確認が必要な理由 --}}
                                                    @if(!empty($attentionReasons))
                                                        <div class="mb-4 p-3 rounded-lg {{ $riskLevel === 'high' ? 'bg-orange-50 dark:bg-orange-900/20' : ($riskLevel === 'medium' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800') }}">
                                                            <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                                <i class="fas fa-info-circle mr-1"></i>
                                                                {{ __('admin.settings.plugins.permissions.attention_reasons_title') }}
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
                                                                        <span>{{ __('admin.settings.plugins.permissions.attention_reason_' . $reasonKey) }}</span>
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
                                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.plugins.permissions.category_' . $category) }}:</span>
                                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                                        @foreach($permissions as $perm)
                                                                            <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-xs">
                                                                                {{ __('admin.settings.plugins.permissions.perm_' . $perm) }}
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.plugins.permissions.no_special_permissions') }}</p>
                                                    @endif
                                                @else
                                                    {{-- 権限未定義 → 警告 --}}
                                                    <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                                                        <div class="flex items-start">
                                                            <i class="fas fa-exclamation-triangle text-orange-500 dark:text-orange-400 mr-2 mt-0.5"></i>
                                                            <p class="text-sm text-orange-700 dark:text-orange-300">{{ __('admin.settings.plugins.permissions.unknown_warning') }}</p>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </x-modal>
                                @endif
                            </td>
                            <td data-label="{{ __('common.actions') }}">
                                <div class="action-buttons">
                                    <!-- インストールボタン -->
                                    @php
                                        $audit = $plugin['permission_summary']['audit'] ?? [];
                                        $hasMismatches = $audit['has_mismatches'] ?? false;
                                        $isNotScanned = empty($audit['audited_at'] ?? null);
                                        $isUnsigned = ($plugin['permission_summary']['signature']['status'] ?? 'unsigned') === 'unsigned';
                                        $isUndefined = !($plugin['permission_summary']['has_permissions'] ?? false);
                                        $riskLevel = $plugin['permission_summary']['risk_level'] ?? 'unknown';
                                        $hasWarnings = $hasMismatches || $isUnsigned || $isUndefined || $isNotScanned || in_array($riskLevel, ['medium', 'high']);
                                    @endphp
                                    <form action="{{ route('admin.settings.plugins.install') }}" method="POST" class="inline-block" id="installForm-{{ $plugin['directory'] }}">
                                        @csrf
                                        <input type="hidden" name="directory" value="{{ $plugin['directory'] }}">
                                        <x-form.button
                                            type="button"
                                            :label="__('common.install')"
                                            variant="success"
                                            size="sm"
                                            icon="fas fa-download"
                                            onclick="openModal('installModal-{{ $plugin['directory'] }}')"
                                        />

                                        <!-- インストール確認モーダル（警告付き） -->
                                        <x-modal
                                            id="installModal-{{ $plugin['directory'] }}"
                                            :title="$hasWarnings ? __('admin.settings.plugins.permissions.install_warning_title') : __('admin.settings.plugins.index.install.confirm_title')"
                                            :confirm_label="__('common.install')"
                                            :cancel_label="__('common.cancel')"
                                            form="installForm-{{ $plugin['directory'] }}"
                                            :icon_type="$hasWarnings ? 'warning' : 'info'"
                                            :confirm_color="$hasWarnings ? 'yellow' : 'green'"
                                        >
                                            @if($hasWarnings)
                                                <div class="text-left">
                                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                                        {{ str_replace('{name}', $plugin['name'], __('admin.settings.plugins.index.install.confirm_message')) }}
                                                    </p>
                                                    <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                                                        <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                                            {{ __('admin.settings.plugins.permissions.install_warning_risk') }}
                                                        </p>
                                                        <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-5 list-disc">
                                                            @if($isUndefined)
                                                                <li>{{ __('admin.settings.plugins.permissions.install_warning_undefined') }}</li>
                                                            @endif
                                                            @if($isUnsigned)
                                                                <li>{{ __('admin.settings.plugins.permissions.install_warning_unsigned') }}</li>
                                                            @endif
                                                            @if($hasMismatches)
                                                                <li>{{ __('admin.settings.plugins.permissions.install_warning_mismatch') }}</li>
                                                            @endif
                                                            @if($isNotScanned)
                                                                <li>{{ __('admin.settings.plugins.permissions.warning_not_scanned') }}</li>
                                                            @endif
                                                            @if(in_array($riskLevel, ['medium', 'high']))
                                                                <li>{{ __('admin.settings.plugins.permissions.risk_' . $riskLevel) }}</li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ __('admin.settings.plugins.permissions.install_warning_confirm') }}
                                                    </p>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                                    {{ str_replace('{name}', $plugin['name'], __('admin.settings.plugins.index.install.confirm_message')) }}
                                                </p>
                                            @endif
                                        </x-modal>
                                    </form>

                                    <!-- 削除ボタン -->
                                    <form action="{{ route('admin.settings.plugins.delete') }}" method="POST" class="inline-block" id="deleteForm-{{ $plugin['directory'] }}">
                                        @csrf
                                        <input type="hidden" name="directory" value="{{ $plugin['directory'] }}">
                                        <x-form.button
                                            type="button"
                                            :label="__('common.delete')"
                                            variant="danger"
                                            size="sm"
                                            icon="fas fa-trash"
                                            onclick="openModal('deleteModal-{{ $plugin['directory'] }}')"
                                        />

                                        <!-- 削除確認モーダル -->
                                        <x-modal
                                            id="deleteModal-{{ $plugin['directory'] }}"
                                            :title="__('admin.settings.plugins.index.delete.confirm_title')"
                                            :message="str_replace('{name}', $plugin['name'], __('admin.settings.plugins.index.delete.confirm_message'))"
                                            :confirm_label="__('common.delete')"
                                            :cancel_label="__('common.cancel')"
                                            form="deleteForm-{{ $plugin['directory'] }}"
                                            icon_type="danger"
                                            confirm_color="red"
                                        />
                                    </form>
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
    const auditMessages = {
        scanning: @json(__('admin.settings.plugins.permissions.audit_scanning')),
        rescan: @json(__('admin.settings.plugins.permissions.audit_button_rescan')),
        completed: @json(__('admin.settings.plugins.index.audit.completed')),
        failed: @json(__('admin.settings.plugins.index.audit.failed')),
        resultTitle: @json(__('admin.settings.plugins.permissions.audit_result_title') ?? 'スキャン結果'),
        noIssues: @json(__('admin.settings.plugins.permissions.audit_no_issues') ?? '問題は検出されませんでした'),
        mismatchFound: @json(__('admin.settings.plugins.permissions.audit_mismatch_title')),
        undeclaredUsage: @json(__('admin.settings.plugins.permissions.audit_undeclared_usage')),
        unusedDeclaration: @json(__('admin.settings.plugins.permissions.audit_unused_declaration')),
        close: @json(__('common.close')),
    };
    
    // 結果モーダルを作成
    function showAuditResultModal(slug, audit) {
        const modalId = 'auditResultModal';
        let modal = document.getElementById(modalId);
        
        // 既存のモーダルを削除
        if (modal) {
            modal.remove();
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
                チェック項目: ${audit.total_checked || 0} / 一致: ${audit.matches_count || 0} / 不一致: ${(audit.mismatches || []).length}
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
    
    document.querySelectorAll('.audit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const slug = this.dataset.slug;
            const btnText = this.querySelector('.audit-btn-text');
            const icon = this.querySelector('i');
            const originalText = btnText.textContent;
            const originalIcon = icon.className;
            const button = this;
            
            // ボタンを無効化してスピナー表示
            button.disabled = true;
            btnText.textContent = auditMessages.scanning;
            icon.className = 'fas fa-spinner fa-spin mr-1';
            
            fetch('{{ route("admin.settings.plugins.audit") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ slug: slug }),
            })
            .then(response => response.json())
            .then(data => {
                console.log('Audit response:', data);
                
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
                console.error('Audit error:', error);
                alert(auditMessages.failed);
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
