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

<div class="text-left space-y-4">
    {{-- 1. 健全性スコア --}}
    @if($card['healthScore'] !== null)
        <div class="p-4 rounded-lg {{ $card['healthStatusColors'][$card['healthStatus']] ?? 'bg-gray-100 dark:bg-gray-700' }}">
            <div class="flex items-center gap-3">
                <div class="text-3xl font-bold">{{ $card['healthScore'] }}<span class="text-sm font-normal">/100</span></div>
                <div>
                    <div class="font-medium">
                        <i class="fas {{ $card['healthStatusIcons'][$card['healthStatus']] ?? 'fa-question-circle' }} mr-1"></i>
                        {{ __('admin/settings/plugins/index.permissions.'.($card['healthStatusLabelKeys'][$card['healthStatus']] ?? 'health_status_not_verified')) }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 2. 健全性の指摘事項（減点理由） --}}
    @if(! empty($card['healthIssues']))
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('admin/settings/plugins/show.scan.issues') }}</h4>
            <ul class="space-y-2">
                @foreach($card['healthIssues'] as $issue)
                    <li class="flex items-start gap-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded text-sm">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
                        <div class="flex-1">
                            <span class="text-gray-700 dark:text-gray-200">{{ $issue['description'] ?? ($issue['type'] ?? '') }}</span>
                            @if(! empty($issue['deduction']))
                                <span class="text-xs text-yellow-700 dark:text-yellow-300 ml-1">({{ $issue['deduction'] }})</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 3. 監査警告（不一致検出時） --}}
    @if($card['hasMismatches'])
        <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
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

    {{-- 4. CSP診断警告（非準拠時） --}}
    @if($card['cspDiagnostic'] && !($card['cspDiagnostic']['compliant'] ?? true))
        <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
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

    {{-- 5. 署名ステータス --}}
    <div>
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin/settings/plugins/index.permissions.signature_status') }}</h4>
        @if(empty($card['auditedAt']))
            <div class="flex items-center mb-2">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                    <i class="fas fa-question-circle mr-1"></i>
                    {{ __('admin/settings/plugins/index.verification.signature_not_scanned') }}
                </span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin/settings/plugins/index.permissions.scan_recommendation') }}
            </p>
        @elseif($card['signatureStatus'] === 'valid')
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
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                    <i class="fas fa-clock mr-1"></i>
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

    {{-- 6. 権限定義の整合性 --}}
    <div>
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin/settings/plugins/index.permissions.permission_consistency_title') }}</h4>
        @if($card['hasMismatches'])
            <div class="flex items-center">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    <i class="fas fa-times-circle mr-1"></i>
                    {{ __('admin/settings/plugins/index.verification.permission_mismatch') }}
                </span>
            </div>
        @elseif(! $card['hasPermissions'])
            <div class="flex items-center">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ __('admin/settings/plugins/index.verification.permission_undefined') }}
                </span>
            </div>
        @elseif(empty($card['auditedAt']))
            <div class="flex items-center">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <i class="fas fa-question-circle mr-1"></i>
                    {{ __('admin/settings/plugins/index.verification.permission_not_scanned') }}
                </span>
            </div>
        @else
            <div class="flex items-center">
                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    <i class="fas fa-check-circle mr-1"></i>
                    {{ __('admin/settings/plugins/index.verification.permission_ok') }}
                </span>
                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/index.modal.healthy_body') }}</span>
            </div>
        @endif
    </div>

    {{-- 7. CSP モード互換性バロメータ --}}
    @if(! empty($card['cspBarometerItems']))
        <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                <i class="fas fa-shield-alt mr-1 {{ $card['cspTierIconColor'] }}"></i>
                {{ __('admin/settings/plugins/show.sections.csp_compatibility') }}
            </h4>
            <x-ui-barometer :items="$card['cspBarometerItems']" />
        </div>
    @endif

    {{-- 8. セキュリティプリセット互換性バロメータ --}}
    @if(! empty($card['presetBarometerItems']))
        <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                <i class="fas fa-layer-group mr-1 {{ $card['presetTierIconColor'] }}"></i>
                {{ __('admin/settings/plugins/show.sections.preset_compatibility') }}
            </h4>
            <x-ui-barometer :items="$card['presetBarometerItems']" />
        </div>
    @endif

    {{-- 9. データベーステーブル（所有テーブル + 他プラグインへの書き込み先） --}}
    @if(! empty($card['ownedTablesData']) && ($card['ownedTablesData']['has_migrations'] || ! empty($card['ownedTablesData']['tables']) || ! empty($card['ownedTablesData']['writes_to_other_plugin_tables'])))
        <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                <i class="fas fa-database mr-1 text-blue-500 dark:text-blue-400"></i>
                {{ __('admin/settings/plugins/index.permissions.database_owned_tables_label') }}
            </h4>

            @if(! empty($card['ownedTablesData']['tables']))
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    {{ __('admin/settings/plugins/index.permissions.database_owned_tables_description') }}
                </p>
                <ul class="space-y-1 mb-2">
                    @foreach($card['ownedTablesData']['tables'] as $tableName)
                        <li class="flex items-center gap-2 text-xs">
                            <i class="fas fa-table text-gray-400"></i>
                            <code class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-2 py-0.5 rounded font-mono">{{ $tableName }}</code>
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs text-gray-400 dark:text-gray-500 italic">
                    @if($card['ownedTablesData']['tables_source'] === 'declared')
                        {{ __('admin/settings/plugins/index.permissions.database_owned_tables_source_declared') }}
                    @else
                        {{ __('admin/settings/plugins/index.permissions.database_owned_tables_source_detected') }}
                    @endif
                </p>
            @else
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('admin/settings/plugins/index.permissions.database_owned_tables_empty') }}
                </p>
            @endif

            @if(! empty($card['ownedTablesData']['writes_to_other_plugin_tables']))
                <div class="mt-3">
                    <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fas fa-arrow-right text-orange-500 dark:text-orange-400 mr-1"></i>
                        {{ __('admin/settings/plugins/index.permissions.database_writes_to_other_label') }}
                    </h5>
                    <ul class="space-y-1">
                        @foreach($card['ownedTablesData']['writes_to_other_plugin_tables'] as $targetSlug => $targetTables)
                            <li class="text-xs">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('admin/settings/plugins/index.permissions.database_writes_to_other_target', ['slug' => $targetSlug]) }}</span>
                                <div class="mt-1 ml-4 flex flex-wrap gap-1">
                                    @foreach((array) $targetTables as $targetTable)
                                        <code class="bg-orange-50 dark:bg-orange-900/30 text-orange-800 dark:text-orange-200 px-2 py-0.5 rounded font-mono">{{ $targetTable }}</code>
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- 10. 提供機能（capabilities 宣言） --}}
    @if(! empty($card['capabilities']))
        <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                <i class="fas fa-cubes mr-1 text-blue-500 dark:text-blue-400"></i>
                {{ __('admin/settings/plugins/index.capabilities.title') }}
            </h4>
            <div class="flex flex-wrap gap-1">
                @foreach($card['capabilities'] as $capability)
                    <span class="inline-block bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-2 py-1 rounded text-xs font-medium">
                        {{ $capability }}
                    </span>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/index.capabilities.description') }}</p>
        </div>
    @endif

    {{-- 11. 権限情報（カテゴリ一覧 + 確認理由） --}}
    <div>
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin/settings/plugins/index.permissions.permission_info') }}</h4>
        @if($card['hasPermissions'])
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
