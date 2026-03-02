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
            'resultTitle' => __('admin/settings/themes/index.permissions.audit_result_title') ?? 'スキャン結果',
            'noIssues' => __('admin/settings/themes/index.permissions.audit_no_issues') ?? '問題は検出されませんでした',
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
        'attentionReasonsTitle' => __('admin/settings/themes/index.permissions.attention_reasons_title'),
        'healthBadgeLabel' => __('admin/settings/themes/index.badge_labels.health'),
        'statsLabel' => __('admin/settings/themes/index.permissions.audit_stats'),
        'matchesLabel' => __('admin/settings/themes/index.permissions.audit_matches'),
        'mismatchesLabel' => __('admin/settings/themes/index.permissions.audit_mismatches'),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
</script>
@endpush

@push('modals')
    {{-- スキャン中モーダル --}}
    <x-ui-modal
        id="themeAuditScanningModal"
        :title="__('admin/settings/themes/index.permissions.audit_scanning')"
        :message="__('admin/settings/themes/index.permissions.audit_scanning_description')"
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
        id="themeAuditResultModal"
        :title="__('admin/settings/themes/index.permissions.audit_result_title')"
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
