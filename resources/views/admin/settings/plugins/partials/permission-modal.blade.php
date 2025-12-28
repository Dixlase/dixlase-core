{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

プラグイン権限詳細モーダル
--}}

@php
    $pluginName = $isModel ? $plugin->translated_name : ($plugin['name'] ?? '');
    $categories = $permissionSummary['categories'] ?? [];
    $attentionReasons = $permissionSummary['risk_reasons'] ?? [];
    $cspDiagnostic = $isModel ? ($plugin->csp_diagnostic ?? null) : ($plugin['csp_diagnostic'] ?? null);
@endphp

<x-modal
    :id="$permissionModalId"
    :title="__('admin/settings/plugins/index.permissions.details_title') . ' - ' . $pluginName"
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
                    {{ __('admin/settings/plugins/index.permissions.audit_mismatch_title') }}
                </h5>
                <p class="text-xs text-red-700 dark:text-red-300 mb-2">{{ __('admin/settings/plugins/index.permissions.audit_mismatch_warning') }}</p>
                <ul class="text-xs text-red-600 dark:text-red-400 space-y-1 ml-4 list-disc">
                    @foreach(array_slice($auditResult['mismatches'] ?? [], 0, 5) as $mismatch)
                        <li>
                            <code class="bg-red-100 dark:bg-red-800 px-1 rounded">{{ $mismatch['permission'] }}</code>
                            @if($mismatch['type'] === 'undeclared_usage')
                                - {{ __('admin/settings/plugins/index.permissions.audit_undeclared_usage') }}
                            @else
                                - {{ __('admin/settings/plugins/index.permissions.audit_unused_declaration') }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        {{-- CSP診断警告 --}}
        @if($cspDiagnostic && !($cspDiagnostic['compliant'] ?? true))
            <div class="mb-4 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                <h5 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-2">
                    <i class="fas fa-shield-alt mr-1"></i>
                    {{ __('admin/settings/plugins/index.permissions.csp_warning_title') }}
                </h5>
                <p class="text-xs text-orange-700 dark:text-orange-300 mb-2">{{ __('admin/settings/plugins/index.permissions.csp_warning_message') }}</p>
                <div class="text-xs text-orange-600 dark:text-orange-400 space-y-1">
                    <p><i class="fas fa-code mr-1"></i> {{ __('admin/settings/plugins/index.permissions.csp_inline_scripts') }}: {{ $cspDiagnostic['summary']['inline_scripts'] ?? 0 }}</p>
                    <p><i class="fas fa-paint-brush mr-1"></i> {{ __('admin/settings/plugins/index.permissions.csp_inline_styles') }}: {{ $cspDiagnostic['summary']['inline_styles'] ?? 0 }}</p>
                </div>
                <p class="text-xs text-orange-600 dark:text-orange-400 mt-2 italic">{{ __('admin/settings/plugins/index.permissions.csp_fix_suggestion') }}</p>
            </div>
        @endif
        
        {{-- 署名ステータス --}}
        <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin/settings/plugins/index.permissions.signature_status') }}</h4>
            @if($signatureStatus === 'valid' || $signatureStatus === 'pending_verification')
                <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }}">
                        <i class="{{ $badgeIcon }} mr-1"></i>
                        {{ $badgeLabel }}
                    </span>
                </div>
                @if($signature['signed_by'] ?? null)
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('admin/settings/plugins/index.permissions.signed_by') }}: {{ $signature['signed_by'] }}
                    </p>
                @endif
            @elseif($signatureStatus === 'invalid')
                <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        <i class="fas fa-times-circle mr-1"></i>
                        {{ __('admin/settings/plugins/index.permissions.signature_invalid') }}
                    </span>
                </div>
                <p class="text-sm text-red-600 dark:text-red-400">
                    {{ __('admin/settings/plugins/index.permissions.signature_invalid_warning') }}
                </p>
            @else
                <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <i class="fas fa-file-signature mr-1"></i>
                        {{ __('admin/settings/plugins/index.permissions.signature_unsigned') }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin/settings/plugins/index.permissions.signature_unsigned_info') }}
                </p>
            @endif
        </div>
        
        {{-- 権限情報 --}}
        <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin/settings/plugins/index.permissions.permission_info') }}</h4>
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
                    <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">{{ __('admin/settings/plugins/index.permissions.health_status') }}:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $healthColors[$riskLevel] ?? $healthColors['low'] }}">
                        <i class="{{ $healthIcons[$riskLevel] ?? $healthIcons['low'] }} mr-1"></i>
                        {{ __('admin/settings/plugins/index.permissions.' . ($healthLabels[$riskLevel] ?? 'health_healthy')) }}
                    </span>
                </div>
                
                {{-- 確認が必要な理由 --}}
                @if(!empty($attentionReasons))
                    <div class="mb-4 p-3 rounded-lg {{ $riskLevel === 'high' ? 'bg-orange-50 dark:bg-orange-900/20' : ($riskLevel === 'medium' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800') }}">
                        <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            {{ __('admin/settings/plugins/index.permissions.attention_reasons_title') }}
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
                                    <span>{{ __('admin/settings/plugins/index.permissions.attention_reason_' . $reasonKey) }}</span>
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
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin/settings/plugins/index.permissions.category_' . $category) }}:</span>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach($permissions as $perm)
                                        <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-xs">
                                            {{ __('admin/settings/plugins/index.permissions.perm_' . $perm) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/index.permissions.no_special_permissions') }}</p>
                @endif
            @else
                {{-- 権限未定義 → 警告 --}}
                <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-orange-500 dark:text-orange-400 mr-2 mt-0.5"></i>
                        <p class="text-sm text-orange-700 dark:text-orange-300">{{ __('admin/settings/plugins/index.permissions.unknown_warning') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-modal>
