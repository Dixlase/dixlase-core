{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

スキャン詳細（バッジモーダル互換）パーシャル
permission-modal と詳細ページで共有
--}}

<div class="text-left">
    {{-- 監査警告 --}}
    @if($card['hasMismatches'])
        <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
            <h5 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
                <i class="fas fa-code-branch mr-1"></i>
                {{ __('admin/settings/plugins/index.permissions.audit_mismatch_title') }}
            </h5>
            <p class="text-xs text-red-700 dark:text-red-300 mb-2">{{ __('admin/settings/plugins/index.permissions.audit_mismatch_warning') }}</p>
            <ul class="text-xs text-red-600 dark:text-red-400 space-y-1 ml-4 list-disc">
                @foreach(array_slice($card['auditResult']['mismatches'] ?? [], 0, 5) as $mismatch)
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
    @if($card['cspDiagnostic'] && !($card['cspDiagnostic']['compliant'] ?? true))
        <div class="mb-4 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
            <h5 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-2">
                <i class="fas fa-shield-alt mr-1"></i>
                {{ __('admin/settings/plugins/index.permissions.csp_warning_title') }}
            </h5>
            <p class="text-xs text-orange-700 dark:text-orange-300 mb-2">{{ __('admin/settings/plugins/index.permissions.csp_warning_message') }}</p>
            <div class="text-xs text-orange-600 dark:text-orange-400 space-y-1">
                <p><i class="fas fa-code mr-1"></i> {{ __('admin/settings/plugins/index.permissions.csp_inline_scripts') }}: {{ $card['cspDiagnostic']['summary']['inline_scripts'] ?? 0 }}</p>
                <p><i class="fas fa-paint-brush mr-1"></i> {{ __('admin/settings/plugins/index.permissions.csp_inline_styles') }}: {{ $card['cspDiagnostic']['summary']['inline_styles'] ?? 0 }}</p>
            </div>
            <p class="text-xs text-orange-600 dark:text-orange-400 mt-2 italic">{{ __('admin/settings/plugins/index.permissions.csp_fix_suggestion') }}</p>
        </div>
    @endif

    {{-- 署名ステータス --}}
    <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin/settings/plugins/index.permissions.signature_status') }}</h4>
        @if($card['signatureStatus'] === 'valid')
            <div class="flex items-center mb-2">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    <i class="fas fa-check-circle mr-1"></i>
                    {{ __('admin/settings/plugins/index.permissions.signature_valid') }}
                </span>
            </div>
            @if($card['signature']['signed_by'] ?? null)
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin/settings/plugins/index.permissions.signed_by') }}: {{ $card['signature']['signed_by'] }}
                </p>
            @endif
        @elseif($card['signatureStatus'] === 'pending_verification')
            <div class="flex items-center mb-2">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    <i class="fas fa-hourglass-half mr-1"></i>
                    {{ __('admin/settings/plugins/index.permissions.signature_pending_verification') }}
                </span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin/settings/plugins/index.permissions.signature_pending_verification_info') }}
            </p>
        @elseif($card['signatureStatus'] === 'invalid')
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
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
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
        @if($card['hasPermissions'])
            {{-- 健全性レベル表示 --}}
            <div class="mb-3 flex items-center">
                <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">{{ __('admin/settings/plugins/index.permissions.health_status') }}:</span>
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $card['healthStatusColors'][$card['healthStatus'] ?? 'not_verified'] ?? $card['healthStatusColors']['not_verified'] }}">
                    <i class="{{ $card['healthStatusIcons'][$card['healthStatus'] ?? 'not_verified'] ?? $card['healthStatusIcons']['not_verified'] }} mr-1"></i>
                    {{ __('admin/settings/plugins/index.permissions.' . ($card['healthStatusLabelKeys'][$card['healthStatus'] ?? 'not_verified'] ?? 'health_status_not_verified')) }}
                </span>
            </div>

            {{-- 確認が必要な理由 --}}
            @if(! empty($card['attentionReasons']))
                <div class="mb-4 p-3 rounded-lg {{ ($card['healthStatus'] ?? 'not_verified') === 'needs_attention' ? 'bg-orange-50 dark:bg-orange-900/20' : (($card['healthStatus'] ?? 'not_verified') === 'advisory' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800') }}">
                    <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ __('admin/settings/plugins/index.permissions.attention_reasons_title') }}
                    </h5>
                    <ul class="space-y-1">
                        @foreach(\App\Presenters\Admin\ExtensionCardPresenter::formatAttentionReasons($card['attentionReasons'], 'admin/settings/plugins/index') as $reason)
                            <li class="flex items-start text-xs {{ $reason['color'] }}">
                                <i class="{{ $reason['icon'] }} mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>{{ $reason['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 権限カテゴリ一覧 --}}
            @if(! empty($card['categories']))
                <div class="space-y-3">
                    @foreach($card['categories'] as $category => $permissions)
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
            {{-- 権限未定義 --}}
            <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-orange-500 dark:text-orange-400 mr-2 mt-0.5"></i>
                    <p class="text-sm text-orange-700 dark:text-orange-300">{{ __('admin/settings/plugins/index.permissions.unknown_warning') }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
