{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

プラグイン監査スクリプト
--}}

@push('scripts')
<script id="plugin-audit-config" type="application/json">
    @json([
        'auditUrl' => route('admin.settings.plugins.audit'),
        'messages' => [
            'scanning' => __('admin/settings/plugins/index.permissions.audit_scanning'),
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
        'healthBadgeLabel' => __('admin/settings/plugins/index.badge_labels.health'),
        'statsLabel' => __('admin/settings/plugins/index.permissions.audit_stats'),
        'matchesLabel' => __('admin/settings/plugins/index.permissions.audit_matches'),
        'mismatchesLabel' => __('admin/settings/plugins/index.permissions.audit_mismatches'),
    ])
</script>
@endpush
