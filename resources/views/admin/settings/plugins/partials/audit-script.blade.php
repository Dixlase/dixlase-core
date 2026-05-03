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

@push('scripts')
<script id="plugin-audit-config" type="application/json">
    <?php echo json_encode([
        'isSimpleMode' => $isSimpleMode ?? false,
        'auditUrl' => route('admin.settings.plugins.audit'),
        'messages' => [
            'scanning' => __('admin/settings/plugins/index.permissions.audit_scanning'),
            'scanningDescription' => __('admin/settings/plugins/index.permissions.audit_scanning_description'),
            'rescan' => __('admin/settings/plugins/index.permissions.audit_button_rescan'),
            'completed' => __('admin/settings/plugins/index.audit.completed'),
            'failed' => __('admin/settings/plugins/index.audit.failed'),
            'resultTitle' => __('admin/settings/plugins/index.permissions.audit_result_title') ?? 'スキャン結果',
            'noIssues' => __('admin/settings/plugins/index.permissions.audit_no_issues') ?? '問題は検出されませんでした',
            'mismatchFound' => __('admin/settings/plugins/index.permissions.audit_mismatch_title'),
            'undeclaredUsage' => __('admin/settings/plugins/index.permissions.audit_undeclared_usage'),
            'unusedDeclaration' => __('admin/settings/plugins/index.permissions.audit_unused_declaration'),
            'close' => __('common.close'),
        ],
        'healthLabels' => [
            'low' => __('admin/settings/plugins/index.permissions.health_healthy'),
            'medium' => __('admin/settings/plugins/index.permissions.health_warning'),
            'high' => __('admin/settings/plugins/index.permissions.health_needs_attention'),
            'unknown' => __('admin/settings/plugins/index.permissions.health_not_verified'),
        ],
        'totalEvaluationLabel' => __('admin/settings/plugins/index.permissions.total_evaluation'),
        'healthScoreDisplay' => __('admin/settings/plugins/index.permissions.health_score_display'),
        'signatureDeduction' => __('admin/settings/plugins/index.permissions.signature_deduction'),
        'healthStatusLabels' => [
            'healthy' => __('admin/settings/plugins/index.permissions.health_status_healthy'),
            'advisory' => __('admin/settings/plugins/index.permissions.health_status_advisory'),
            'needs_attention' => __('admin/settings/plugins/index.permissions.health_status_needs_attention'),
            'not_verified' => __('admin/settings/plugins/index.permissions.health_status_not_verified'),
        ],
        'signatureSectionLabel' => __('admin/settings/plugins/index.permissions.signature_section_label'),
        'attentionReasonsTitle' => __('admin/settings/plugins/index.permissions.attention_reasons_title'),
        'permissionConsistencyTitle' => __('admin/settings/plugins/index.permissions.permission_consistency_title'),
        'totalRiskScoreLabel' => __('admin/settings/plugins/index.permissions.total_risk_score'),
        'healthBadgeLabel' => __('admin/settings/plugins/index.badge_labels.health_full'),
        'statsLabel' => __('admin/settings/plugins/index.permissions.audit_stats'),
        'matchesLabel' => __('admin/settings/plugins/index.permissions.audit_matches'),
        'mismatchesLabel' => __('admin/settings/plugins/index.permissions.audit_mismatches'),
        'cspCompatibilityLabel' => __('admin/settings/plugins/show.sections.csp_compatibility'),
        'presetCompatibilityLabel' => __('admin/settings/plugins/show.sections.preset_compatibility'),
        'healthIssueTypeLabels' => [
            'signature_unsigned' => __('admin/settings/plugins/index.permissions.health_issue_signature_unsigned'),
            'signature_invalid' => __('admin/settings/plugins/index.permissions.health_issue_signature_invalid'),
            'permission_undefined' => __('admin/settings/plugins/index.permissions.health_issue_permission_undefined'),
            'permission_undeclared_minor' => __('admin/settings/plugins/index.permissions.health_issue_permission_undeclared_minor'),
            'permission_undeclared_major' => __('admin/settings/plugins/index.permissions.health_issue_permission_undeclared_major'),
            'permission_unused' => __('admin/settings/plugins/index.permissions.health_issue_permission_unused'),
            'csp_inline_css_required' => __('admin/settings/plugins/index.permissions.health_issue_csp_inline_css_required'),
            'csp_inline_js_required' => __('admin/settings/plugins/index.permissions.health_issue_csp_inline_js_required'),
            'csp_violation_strict' => __('admin/settings/plugins/index.permissions.health_issue_csp_violation_strict'),
            'csp_violation_standard' => __('admin/settings/plugins/index.permissions.health_issue_csp_violation_standard'),
            'dangerous_api_exec' => __('admin/settings/plugins/index.permissions.health_issue_dangerous_api_exec'),
            'scan_not_performed' => __('admin/settings/plugins/index.permissions.health_issue_scan_not_performed'),
            'scan_outdated' => __('admin/settings/plugins/index.permissions.health_issue_scan_outdated'),
        ],
        'scanRequired' => $scanRequired ?? false,
        'twoStage' => [
            'stage1ScanRequiredTitle' => __('admin/settings/plugins/index.two_stage.stage1_scan_required_title'),
            'stage1ScanRequiredMessage' => __('admin/settings/plugins/index.two_stage.stage1_scan_required_message'),
            'stage1ScanOptionalTitle' => __('admin/settings/plugins/index.two_stage.stage1_scan_optional_title'),
            'stage1ScanOptionalMessage' => __('admin/settings/plugins/index.two_stage.stage1_scan_optional_message'),
            'stage1Scanning' => __('admin/settings/plugins/index.two_stage.stage1_scanning'),
            'stage1ScanningDescription' => __('admin/settings/plugins/index.two_stage.stage1_scanning_description'),
            'stage1SkipScan' => __('admin/settings/plugins/index.two_stage.stage1_skip_scan'),
            'stage1StartScan' => __('admin/settings/plugins/index.two_stage.stage1_start_scan'),
            'stage2ConfirmInstall' => __('admin/settings/plugins/index.two_stage.stage2_confirm_install'),
            'stage2ConfirmEnable' => __('admin/settings/plugins/index.two_stage.stage2_confirm_enable'),
            'stage2BlockedTitle' => __('admin/settings/plugins/index.two_stage.stage2_blocked_title'),
            'stage2BlockedMessage' => __('admin/settings/plugins/index.two_stage.stage2_blocked_message'),
            'stage2ScanResultHeading' => __('admin/settings/plugins/index.two_stage.stage2_scan_result_heading'),
            'stage2WarningMessage' => __('admin/settings/plugins/index.two_stage.stage2_warning_message'),
            'actionInstall' => __('admin/settings/plugins/index.two_stage.action_install'),
            'actionEnable' => __('admin/settings/plugins/index.two_stage.action_enable'),
            'actionInstalled' => __('admin/settings/plugins/index.two_stage.action_installed'),
            'actionEnabled' => __('admin/settings/plugins/index.two_stage.action_enabled'),
            'installBlocked' => __('admin/settings/plugins/index.two_stage.install_blocked'),
            'install' => __('common.install'),
            'enable' => __('common.enable'),
            'cancel' => __('common.cancel'),
            'close' => __('common.close'),
            'stage2ConfirmActionMessage' => __('admin/settings/plugins/index.two_stage.stage2_confirm_action_message'),
            'processingInstall' => __('admin/settings/plugins/index.two_stage.processing_install'),
            'processingEnable' => __('admin/settings/plugins/index.two_stage.processing_enable'),
            'processingInstallDescription' => __('admin/settings/plugins/index.two_stage.processing_install_description'),
            'processingEnableDescription' => __('admin/settings/plugins/index.two_stage.processing_enable_description'),
        ],
        'signatureLabels' => [
            'title' => __('admin/settings/plugins/index.permissions.signature_status'),
            'official' => __('admin/settings/plugins/index.permissions.signature_official'),
            'verified' => __('admin/settings/plugins/index.permissions.signature_verified'),
            'partner' => __('admin/settings/plugins/index.permissions.signature_partner'),
            // 'valid' = CoreSignatureVerifier が返すステータス。'signed' と同じ表示文言。
            'valid' => __('admin/settings/plugins/index.permissions.signature_valid'),
            'signed' => __('admin/settings/plugins/index.permissions.signature_signed'),
            'invalid' => __('admin/settings/plugins/index.permissions.signature_invalid'),
            'unsigned' => __('admin/settings/plugins/index.permissions.signature_unsigned'),
            'pending_verification' => __('admin/settings/plugins/index.permissions.signature_pending_verification'),
            'unknown_key' => __('admin/settings/plugins/index.permissions.signature_unknown_key'),
            'expired' => __('admin/settings/plugins/index.permissions.signature_expired'),
            'error' => __('admin/settings/plugins/index.permissions.signature_error'),
            'invalidWarning' => __('admin/settings/plugins/index.permissions.signature_invalid_warning'),
            'unsignedInfo' => __('admin/settings/plugins/index.permissions.signature_unsigned_info'),
            'pendingVerificationInfo' => __('admin/settings/plugins/index.permissions.signature_pending_verification_info'),
            'unknownKeyInfo' => __('admin/settings/plugins/index.permissions.signature_unknown_key_info'),
            'expiredInfo' => __('admin/settings/plugins/index.permissions.signature_expired_info'),
            'errorInfo' => __('admin/settings/plugins/index.permissions.signature_error_info'),
            'signedBy' => __('admin/settings/plugins/index.permissions.signed_by'),
        ],
        'cspLabels' => [
            'title' => __('admin/settings/plugins/index.permissions.csp_status'),
            'sectionLabel' => __('admin/settings/plugins/index.permissions.csp_section_label'),
            'compliant' => __('admin/settings/plugins/index.permissions.csp_compliant'),
            'notCompliant' => __('admin/settings/plugins/index.permissions.csp_not_compliant'),
            'inlineScripts' => __('admin/settings/plugins/index.permissions.csp_inline_scripts'),
            'inlineStyles' => __('admin/settings/plugins/index.permissions.csp_inline_styles'),
            'eventHandlers' => __('admin/settings/plugins/index.permissions.csp_event_handlers'),
            'javascriptUrls' => __('admin/settings/plugins/index.permissions.csp_javascript_urls'),
            'violationTypes' => [
                'inline_script' => __('admin/settings/plugins/index.permissions.csp_violation_inline_script'),
                'inline_style' => __('admin/settings/plugins/index.permissions.csp_violation_inline_style'),
                'event_handler' => __('admin/settings/plugins/index.permissions.csp_violation_event_handler'),
                'javascript_url' => __('admin/settings/plugins/index.permissions.csp_violation_javascript_url'),
            ],
        ],
        'permissionCategoriesTitle' => __('admin/settings/plugins/index.permissions.permission_info'),
        'categoryLabels' => [
            'database' => __('admin/settings/plugins/index.permissions.category_database'),
            'storage' => __('admin/settings/plugins/index.permissions.category_storage'),
            'settings' => __('admin/settings/plugins/index.permissions.category_settings'),
            'members' => __('admin/settings/plugins/index.permissions.category_members'),
            'mail' => __('admin/settings/plugins/index.permissions.category_mail'),
            'content' => __('admin/settings/plugins/index.permissions.category_content'),
            'system' => __('admin/settings/plugins/index.permissions.category_system'),
        ],
        'permissionLabels' => [
            'own_tables' => __('admin/settings/plugins/index.permissions.perm_own_tables'),
            'core_tables_read' => __('admin/settings/plugins/index.permissions.perm_core_tables_read'),
            'core_tables_write' => __('admin/settings/plugins/index.permissions.perm_core_tables_write'),
            'own_directory' => __('admin/settings/plugins/index.permissions.perm_own_directory'),
            'public_uploads' => __('admin/settings/plugins/index.permissions.perm_public_uploads'),
            'temp_files' => __('admin/settings/plugins/index.permissions.perm_temp_files'),
            'read_core' => __('admin/settings/plugins/index.permissions.perm_read_core'),
            'write_own' => __('admin/settings/plugins/index.permissions.perm_write_own'),
            'read' => __('admin/settings/plugins/index.permissions.perm_read'),
            'write' => __('admin/settings/plugins/index.permissions.perm_write'),
            'create' => __('admin/settings/plugins/index.permissions.perm_create'),
            'delete' => __('admin/settings/plugins/index.permissions.perm_delete'),
            'send' => __('admin/settings/plugins/index.permissions.perm_send'),
            'bulk_send' => __('admin/settings/plugins/index.permissions.perm_bulk_send'),
            'read_other_plugins' => __('admin/settings/plugins/index.permissions.perm_read_other_plugins'),
            'write_other_plugins' => __('admin/settings/plugins/index.permissions.perm_write_other_plugins'),
            'register_shortcodes' => __('admin/settings/plugins/index.permissions.perm_register_shortcodes'),
            'register_middleware' => __('admin/settings/plugins/index.permissions.perm_register_middleware'),
            'register_commands' => __('admin/settings/plugins/index.permissions.perm_register_commands'),
            'register_blade_directives' => __('admin/settings/plugins/index.permissions.perm_register_blade_directives'),
            'modify_routes' => __('admin/settings/plugins/index.permissions.perm_modify_routes'),
        ],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
</script>
@endpush

@push('modals')
    {{-- スキャン中モーダル --}}
    <x-ui-modal
        id="pluginAuditScanningModal"
        :title="__('admin/settings/plugins/index.permissions.audit_scanning_title')"
        message=""
        iconType="info"
        :dismissible="false"
        :closeOnly="true"
    >
        <p class="text-sm text-gray-700 dark:text-gray-300 text-center">{!! __('admin/settings/plugins/index.permissions.audit_scanning_description') !!}</p>
        <x-slot:footer>
            <div class="flex items-center justify-center w-full py-1">
                <i class="fas fa-spinner fa-spin text-indigo-500 text-xl"></i>
            </div>
        </x-slot:footer>
    </x-ui-modal>

    {{-- スキャン結果モーダル --}}
    <x-ui-modal
        id="pluginAuditResultModal"
        :title="__('admin/settings/plugins/index.permissions.audit_result_title')"
        message=""
        iconType="info"
        :dismissible="false"
        :closeOnly="true"
    >
        <div id="pluginAuditResultContent" class="text-left"></div>
        <x-slot:footer>
            <x-form-button
                type="button"
                variant="primary"
                id="pluginAuditResultCloseBtn"
            >
                {{ __('common.close') }}
            </x-form-button>
        </x-slot:footer>
    </x-ui-modal>

    {{-- 2段階モーダル: Stage 1（スキャン判定/進捗） --}}
    <x-ui-modal
        id="pluginActionStage1Modal"
        :title="__('admin/settings/plugins/index.two_stage.stage1_scan_required_title')"
        message=""
        iconType="info"
        :dismissible="false"
        :closeOnly="true"
    >
        <div id="pluginActionStage1Content" class="text-center">
            <p id="pluginActionStage1Message" class="text-sm text-gray-700 dark:text-gray-300"></p>
        </div>
        <x-slot:footer>
            <div id="pluginActionStage1Spinner" class="hidden flex items-center justify-center w-full py-1">
                <i class="fas fa-spinner fa-spin text-indigo-500 text-xl"></i>
            </div>
            <div id="pluginActionStage1Buttons" class="flex gap-2">
                <x-form-button
                    type="button"
                    :label="__('common.cancel')"
                    variant="secondary"
                    id="pluginActionStage1CancelBtn"
                />
                <div id="pluginActionStage1SkipBtn" class="hidden">
                    <x-form-button
                        type="button"
                        :label="__('admin/settings/plugins/index.two_stage.stage1_skip_scan')"
                        variant="secondary"
                        class="stage1-skip-btn"
                    />
                </div>
                <x-form-button
                    type="button"
                    :label="__('admin/settings/plugins/index.two_stage.stage1_start_scan')"
                    variant="primary"
                    icon="fas fa-search"
                    id="pluginActionStage1ScanBtn"
                />
            </div>
        </x-slot:footer>
    </x-ui-modal>

    {{-- 2段階モーダル: Stage 2（アクション確認またはブロック） --}}
    <x-ui-modal
        id="pluginActionStage2Modal"
        title=""
        message=""
        iconType="info"
        :dismissible="false"
        :closeOnly="true"
    >
        <div id="pluginActionStage2Content" class="text-left"></div>
        <p id="pluginActionStage2ConfirmMessage" class="text-sm text-gray-700 dark:text-gray-300 mt-3 mb-4 text-center font-medium"></p>
        <x-slot:footer>
            <div id="pluginActionStage2Buttons" class="flex gap-2">
                <x-form-button
                    type="button"
                    :label="__('common.cancel')"
                    variant="secondary"
                    id="pluginActionStage2CancelBtn"
                />
                <div id="pluginActionStage2ConfirmBtn">
                    <x-form-button
                        type="button"
                        label=""
                        variant="success"
                    >
                        <span id="pluginActionStage2ConfirmLabel"></span>
                    </x-form-button>
                </div>
            </div>
        </x-slot:footer>
    </x-ui-modal>

    {{-- 処理中モーダル（インストール/有効化） --}}
    <x-ui-modal
        id="pluginActionProcessingModal"
        title=""
        message=""
        iconType="info"
        :dismissible="false"
        :closeOnly="true"
    >
        <p id="pluginActionProcessingMessage" class="text-sm text-gray-700 dark:text-gray-300 text-center"></p>
        <x-slot:footer>
            <div class="flex items-center justify-center w-full py-1">
                <i class="fas fa-spinner fa-spin text-indigo-500 text-xl"></i>
            </div>
        </x-slot:footer>
    </x-ui-modal>
@endpush
