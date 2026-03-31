{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

テーマ監査スクリプト
--}}

@push('scripts')
<script id="theme-audit-config" type="application/json">
    <?php echo json_encode([
        'auditUrl' => route('admin.settings.themes.audit'),
        'messages' => [
            'scanning' => __('admin/settings/themes/index.permissions.audit_scanning'),
            'scanningDescription' => __('admin/settings/themes/index.permissions.audit_scanning_description'),
            'rescan' => __('admin/settings/themes/index.permissions.audit_button_rescan'),
            'completed' => __('admin/settings/themes/index.audit.completed'),
            'failed' => __('admin/settings/themes/index.audit.failed'),
            'resultTitle' => __('admin/settings/themes/index.permissions.audit_result_title'),
            'noIssues' => __('admin/settings/themes/index.permissions.audit_no_issues'),
            'mismatchFound' => __('admin/settings/themes/index.permissions.audit_mismatch_title'),
            'undeclaredUsage' => __('admin/settings/themes/index.permissions.audit_undeclared_usage'),
            'unusedDeclaration' => __('admin/settings/themes/index.permissions.audit_unused_declaration'),
            'close' => __('common.close'),
        ],
        'healthLabels' => [
            'low' => __('admin/settings/themes/index.permissions.health_healthy'),
            'medium' => __('admin/settings/themes/index.permissions.health_warning'),
            'high' => __('admin/settings/themes/index.permissions.health_needs_attention'),
            'unknown' => __('admin/settings/themes/index.permissions.health_not_verified'),
        ],
        'totalEvaluationLabel' => __('admin/settings/themes/index.permissions.total_evaluation'),
        'healthScoreDisplay' => __('admin/settings/themes/index.permissions.health_score_display'),
        'healthStatusLabels' => [
            'healthy' => __('admin/settings/themes/index.permissions.health_status_healthy'),
            'advisory' => __('admin/settings/themes/index.permissions.health_status_advisory'),
            'needs_attention' => __('admin/settings/themes/index.permissions.health_status_needs_attention'),
            'not_verified' => __('admin/settings/themes/index.permissions.health_status_not_verified'),
        ],
        'signatureSectionLabel' => __('admin/settings/themes/index.permissions.signature_section_label'),
        'attentionReasonsTitle' => __('admin/settings/themes/index.permissions.attention_reasons_title'),
        'permissionConsistencyTitle' => __('admin/settings/themes/index.permissions.permission_consistency_title'),
        'totalRiskScoreLabel' => __('admin/settings/themes/index.permissions.total_risk_score'),
        'healthBadgeLabel' => __('admin/settings/themes/index.badge_labels.health_full'),
        'statsLabel' => __('admin/settings/themes/index.permissions.audit_stats'),
        'matchesLabel' => __('admin/settings/themes/index.permissions.audit_matches'),
        'mismatchesLabel' => __('admin/settings/themes/index.permissions.audit_mismatches'),
        'healthIssueTypeLabels' => [
            'signature_unsigned' => __('admin/settings/themes/index.permissions.health_issue_signature_unsigned'),
            'signature_invalid' => __('admin/settings/themes/index.permissions.health_issue_signature_invalid'),
            'permission_undefined' => __('admin/settings/themes/index.permissions.health_issue_permission_undefined'),
            'permission_undeclared_minor' => __('admin/settings/themes/index.permissions.health_issue_permission_undeclared_minor'),
            'permission_undeclared_major' => __('admin/settings/themes/index.permissions.health_issue_permission_undeclared_major'),
            'permission_unused' => __('admin/settings/themes/index.permissions.health_issue_permission_unused'),
            'csp_inline_css_required' => __('admin/settings/themes/index.permissions.health_issue_csp_inline_css_required'),
            'csp_inline_js_required' => __('admin/settings/themes/index.permissions.health_issue_csp_inline_js_required'),
            'csp_violation_strict' => __('admin/settings/themes/index.permissions.health_issue_csp_violation_strict'),
            'csp_violation_standard' => __('admin/settings/themes/index.permissions.health_issue_csp_violation_standard'),
            'dangerous_api_exec' => __('admin/settings/themes/index.permissions.health_issue_dangerous_api_exec'),
            'scan_not_performed' => __('admin/settings/themes/index.permissions.health_issue_scan_not_performed'),
            'scan_outdated' => __('admin/settings/themes/index.permissions.health_issue_scan_outdated'),
        ],
        'signatureLabels' => [
            'title' => __('admin/settings/themes/index.permissions.signature_status'),
            'official' => __('admin/settings/themes/index.permissions.signature_official'),
            'verified' => __('admin/settings/themes/index.permissions.signature_verified'),
            'partner' => __('admin/settings/themes/index.permissions.signature_partner'),
            'signed' => __('admin/settings/themes/index.permissions.signature_signed'),
            'invalid' => __('admin/settings/themes/index.permissions.signature_invalid'),
            'unsigned' => __('admin/settings/themes/index.permissions.signature_unsigned'),
            'invalidWarning' => __('admin/settings/themes/index.permissions.signature_invalid_warning'),
            'unsignedInfo' => __('admin/settings/themes/index.permissions.signature_unsigned_info'),
            'signedBy' => __('admin/settings/themes/index.permissions.signed_by'),
        ],
        'cspLabels' => [
            'title' => __('admin/settings/themes/index.permissions.csp_status'),
            'sectionLabel' => __('admin/settings/themes/index.permissions.csp_section_label'),
            'compliant' => __('admin/settings/themes/index.permissions.csp_compliant'),
            'notCompliant' => __('admin/settings/themes/index.permissions.csp_not_compliant'),
            'inlineScripts' => __('admin/settings/themes/index.permissions.csp_inline_scripts'),
            'inlineStyles' => __('admin/settings/themes/index.permissions.csp_inline_styles'),
            'eventHandlers' => __('admin/settings/themes/index.permissions.csp_event_handlers'),
            'javascriptUrls' => __('admin/settings/themes/index.permissions.csp_javascript_urls'),
            'violationTypes' => [
                'inline_script' => __('admin/settings/themes/index.permissions.csp_violation_inline_script'),
                'inline_style' => __('admin/settings/themes/index.permissions.csp_violation_inline_style'),
                'event_handler' => __('admin/settings/themes/index.permissions.csp_violation_event_handler'),
                'javascript_url' => __('admin/settings/themes/index.permissions.csp_violation_javascript_url'),
            ],
        ],
        'permissionCategoriesTitle' => __('admin/settings/themes/index.permissions.permission_info'),
        'categoryLabels' => [
            'database' => __('admin/settings/themes/index.permissions.category_database'),
            'storage' => __('admin/settings/themes/index.permissions.category_storage'),
            'settings' => __('admin/settings/themes/index.permissions.category_settings'),
            'assets' => __('admin/settings/themes/index.permissions.category_assets'),
            'system' => __('admin/settings/themes/index.permissions.category_system'),
        ],
        'permissionLabels' => [
            'own_tables' => __('admin/settings/themes/index.permissions.perm_own_tables'),
            'core_tables_read' => __('admin/settings/themes/index.permissions.perm_core_tables_read'),
            'core_tables_write' => __('admin/settings/themes/index.permissions.perm_core_tables_write'),
            'own_directory' => __('admin/settings/themes/index.permissions.perm_own_directory'),
            'public_uploads' => __('admin/settings/themes/index.permissions.perm_public_uploads'),
            'temp_files' => __('admin/settings/themes/index.permissions.perm_temp_files'),
            'read_core' => __('admin/settings/themes/index.permissions.perm_read_core'),
            'write_own' => __('admin/settings/themes/index.permissions.perm_write_own'),
            'custom_css' => __('admin/settings/themes/index.permissions.perm_custom_css'),
            'custom_js' => __('admin/settings/themes/index.permissions.perm_custom_js'),
            'external_resources' => __('admin/settings/themes/index.permissions.perm_external_resources'),
            'register_shortcodes' => __('admin/settings/themes/index.permissions.perm_register_shortcodes'),
            'register_middleware' => __('admin/settings/themes/index.permissions.perm_register_middleware'),
            'register_commands' => __('admin/settings/themes/index.permissions.perm_register_commands'),
            'register_blade_directives' => __('admin/settings/themes/index.permissions.perm_register_blade_directives'),
            'modify_routes' => __('admin/settings/themes/index.permissions.perm_modify_routes'),
        ],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
</script>
@endpush

@push('modals')
    {{-- スキャン中モーダル --}}
    <x-ui-modal
        id="themeAuditScanningModal"
        :title="__('admin/settings/themes/index.permissions.audit_scanning_title')"
        message=""
        iconType="info"
        :dismissible="false"
        :closeOnly="true"
    >
        <p class="text-sm text-gray-700 dark:text-gray-300 text-center">{!! __('admin/settings/themes/index.permissions.audit_scanning_description') !!}</p>
        <x-slot:footer>
            <div class="flex items-center justify-center w-full py-1">
                <i class="fas fa-spinner fa-spin text-indigo-500 text-xl"></i>
            </div>
        </x-slot:footer>
    </x-ui-modal>

    {{-- スキャン結果モーダル --}}
    <x-ui-modal
        id="themeAuditResultModal"
        :title="__('admin/settings/themes/index.permissions.audit_result_title')"
        message=""
        iconType="info"
        :dismissible="false"
        :closeOnly="true"
    >
        <div id="themeAuditResultContent" class="text-left"></div>
        <x-slot:footer>
            <x-form-button
                type="button"
                variant="primary"
                id="themeAuditResultCloseBtn"
            >
                {{ __('common.close') }}
            </x-form-button>
        </x-slot:footer>
    </x-ui-modal>
@endpush
