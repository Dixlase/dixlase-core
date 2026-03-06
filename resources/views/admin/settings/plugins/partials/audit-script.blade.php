{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

プラグイン監査スクリプト
--}}

@push('scripts')
<script id="plugin-audit-config" type="application/json">
    <?php echo json_encode([
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
        'attentionReasonsTitle' => __('admin/settings/plugins/index.permissions.attention_reasons_title'),
        'permissionConsistencyTitle' => __('admin/settings/plugins/index.permissions.permission_consistency_title'),
        'totalRiskScoreLabel' => __('admin/settings/plugins/index.permissions.total_risk_score'),
        'healthBadgeLabel' => __('admin/settings/plugins/index.badge_labels.health'),
        'statsLabel' => __('admin/settings/plugins/index.permissions.audit_stats'),
        'matchesLabel' => __('admin/settings/plugins/index.permissions.audit_matches'),
        'mismatchesLabel' => __('admin/settings/plugins/index.permissions.audit_mismatches'),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
</script>
@endpush

@push('modals')
    {{-- スキャン中モーダル --}}
    <x-ui-modal
        id="pluginAuditScanningModal"
        :title="__('admin/settings/plugins/index.permissions.audit_scanning')"
        :message="__('admin/settings/plugins/index.permissions.audit_scanning_description')"
        iconType="info"
        :dismissible="false"
        :closeOnly="true"
    >
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
@endpush
