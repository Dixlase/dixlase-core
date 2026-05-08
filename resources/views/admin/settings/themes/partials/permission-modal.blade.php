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

<x-ui-modal
    :id="$card['permissionModalId']"
    :title="__('admin/settings/themes/index.permissions.details_title') . ' - ' . $card['name']"
    icon_type="info"
    :close_only="true"
    :close_label="__('common.close')"
>
    <div class="text-left">
        {{-- Audit warning --}}
        @if($card['hasMismatches'])
            <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <h5 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
                    <i class="fas fa-code-branch mr-1"></i>
                    {{ __('admin/settings/themes/index.permissions.audit_mismatch_title') }}
                </h5>
                <p class="text-xs text-red-700 dark:text-red-300 mb-2">{{ __('admin/settings/themes/index.permissions.audit_mismatch_warning') }}</p>
                <ul class="text-xs text-red-600 dark:text-red-400 space-y-1 ml-4 list-disc">
                    @foreach(array_slice($card['auditResult']['mismatches'] ?? [], 0, 5) as $mismatch)
                        <li>
                            <code class="bg-red-100 dark:bg-red-800 px-1 rounded">{{ $mismatch['permission'] }}</code>
                            @if($mismatch['type'] === 'undeclared_usage')
                                - {{ __('admin/settings/themes/index.permissions.audit_undeclared_usage') }}
                            @else
                                - {{ __('admin/settings/themes/index.permissions.audit_unused_declaration') }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- CSP diagnostic warning --}}
        @if($card['cspDiagnostic'] && !($card['cspDiagnostic']['compliant'] ?? true))
            <div class="mb-4 p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                <h5 class="text-sm font-semibold text-orange-800 dark:text-orange-200 mb-2">
                    <i class="fas fa-shield-alt mr-1"></i>
                    {{ __('admin/settings/themes/index.permissions.csp_warning_title') }}
                </h5>
                <p class="text-xs text-orange-700 dark:text-orange-300 mb-2">{{ __('admin/settings/themes/index.permissions.csp_warning_message') }}</p>
                <div class="text-xs text-orange-600 dark:text-orange-400 space-y-1">
                    <p><i class="fas fa-code mr-1"></i> {{ __('admin/settings/themes/index.permissions.csp_inline_scripts') }}: {{ $card['cspDiagnostic']['summary']['inline_scripts'] ?? 0 }}</p>
                    <p><i class="fas fa-paint-brush mr-1"></i> {{ __('admin/settings/themes/index.permissions.csp_inline_styles') }}: {{ $card['cspDiagnostic']['summary']['inline_styles'] ?? 0 }}</p>
                </div>
                <p class="text-xs text-orange-600 dark:text-orange-400 mt-2 italic">{{ __('admin/settings/themes/index.permissions.csp_fix_suggestion') }}</p>
            </div>
        @endif

        {{-- Signature status --}}
        <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin/settings/themes/index.permissions.signature_status') }}</h4>
            @if($card['signatureStatus'] === 'valid' || $card['signatureStatus'] === 'pending_verification')
                <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $card['badgeColor'] }}">
                        <i class="{{ $card['badgeIcon'] }} mr-1"></i>
                        {{ $card['badgeLabel'] }}
                    </span>
                </div>
                @if($card['signature']['signed_by'] ?? null)
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('admin/settings/themes/index.permissions.signed_by') }}: {{ $card['signature']['signed_by'] }}
                    </p>
                @endif
            @elseif($card['signatureStatus'] === 'invalid')
                <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        <i class="fas fa-times-circle mr-1"></i>
                        {{ __('admin/settings/themes/index.permissions.signature_invalid') }}
                    </span>
                </div>
                <p class="text-sm text-red-600 dark:text-red-400">
                    {{ __('admin/settings/themes/index.permissions.signature_invalid_warning') }}
                </p>
            @else
                <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                        <i class="fas fa-file-signature mr-1"></i>
                        {{ __('admin/settings/themes/index.permissions.signature_unsigned') }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin/settings/themes/index.permissions.signature_unsigned_info') }}
                </p>
            @endif
        </div>

        {{-- Permission information --}}
        <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin/settings/themes/index.permissions.permission_info') }}</h4>
            @if($card['hasPermissions'])
                <div class="mb-3 flex items-center">
                    <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">{{ __('admin/settings/themes/index.permissions.health_status') }}:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $card['healthStatusColors'][$card['riskLevel']] ?? $card['healthStatusColors']['healthy'] }}">
                        <i class="{{ $card['healthStatusIcons'][$card['riskLevel']] ?? $card['healthStatusIcons']['healthy'] }} mr-1"></i>
                        {{ __('admin/settings/themes/index.permissions.' . ($card['healthStatusLabelKeys'][$card['riskLevel']] ?? 'health_status_healthy')) }}
                    </span>
                </div>

                @if(!empty($card['attentionReasons']))
                    <div class="mb-4 p-3 rounded-lg {{ $card['riskLevel'] === 'high' ? 'bg-orange-50 dark:bg-orange-900/20' : ($card['riskLevel'] === 'medium' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800') }}">
                        <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            {{ __('admin/settings/themes/index.permissions.attention_reasons_title') }}
                        </h5>
                        <ul class="space-y-1">
                            @foreach(\App\Presenters\Admin\ExtensionCardPresenter::formatAttentionReasons($card['attentionReasons'], 'admin/settings/themes/index') as $reason)
                                <li class="flex items-start text-xs {{ $reason['color'] }}">
                                    <i class="{{ $reason['icon'] }} mr-2 mt-0.5 flex-shrink-0"></i>
                                    <span>{{ $reason['text'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($card['categories']))
                    <div class="space-y-3">
                        @foreach($card['categories'] as $category => $permissions)
                            <div class="border-b border-gray-200 dark:border-gray-700 pb-2 last:border-0">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin/settings/themes/index.permissions.category_' . $category) }}:</span>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach($permissions as $perm)
                                        <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-xs">
                                            {{ __('admin/settings/themes/index.permissions.perm_' . $perm) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/index.permissions.no_special_permissions') }}</p>
                @endif
            @else
                <div class="p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-orange-500 dark:text-orange-400 mr-2 mt-0.5"></i>
                        <p class="text-sm text-orange-700 dark:text-orange-300">{{ __('admin/settings/themes/index.permissions.unknown_warning') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-ui-modal>
