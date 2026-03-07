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
            'stage2WarningMessage' => __('admin/settings/plugins/index.two_stage.stage2_warning_message'),
            'actionInstall' => __('admin/settings/plugins/index.two_stage.action_install'),
            'actionEnable' => __('admin/settings/plugins/index.two_stage.action_enable'),
            'actionInstalled' => __('admin/settings/plugins/index.two_stage.action_installed'),
            'actionEnabled' => __('admin/settings/plugins/index.two_stage.action_enabled'),
            'installBlocked' => __('admin/settings/plugins/index.two_stage.install_blocked'),
            'install' => __('common.install'),
            'enable' => __('common.enable'),
            'cancel' => __('common.cancel'),
        ],
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

    {{-- 2段階モーダル: Stage 1（スキャン判定/進捗） --}}
    <x-ui-modal
        id="pluginActionStage1Modal"
        :title="__('admin/settings/plugins/index.two_stage.stage1_scan_required_title')"
        message=""
        iconType="info"
        :dismissible="false"
        :closeOnly="true"
    >
        <div id="pluginActionStage1Content" class="text-left">
            <p id="pluginActionStage1Message" class="text-sm text-gray-700 dark:text-gray-300"></p>
        </div>
        <div id="pluginActionStage1Spinner" class="hidden flex items-center justify-center w-full py-3">
            <i class="fas fa-spinner fa-spin text-indigo-500 text-xl mr-2"></i>
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/plugins/index.two_stage.stage1_scanning') }}</span>
        </div>
        <x-slot:footer>
            <div id="pluginActionStage1Buttons" class="flex gap-2">
                <x-form-button
                    type="button"
                    :label="__('common.cancel')"
                    variant="secondary"
                    id="pluginActionStage1CancelBtn"
                />
                <x-form-button
                    type="button"
                    :label="__('admin/settings/plugins/index.two_stage.stage1_skip_scan')"
                    variant="secondary"
                    id="pluginActionStage1SkipBtn"
                    class="hidden"
                />
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
        <x-slot:footer>
            <div id="pluginActionStage2Buttons" class="flex gap-2">
                <x-form-button
                    type="button"
                    :label="__('common.cancel')"
                    variant="secondary"
                    id="pluginActionStage2CancelBtn"
                />
                <x-form-button
                    type="button"
                    label=""
                    variant="success"
                    id="pluginActionStage2ConfirmBtn"
                />
            </div>
        </x-slot:footer>
    </x-ui-modal>
@endpush
