{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

テーマ監査スクリプト
--}}

@push('scripts')
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    const auditMessages = {
        scanning: @json(__('admin/settings/themes.permissions.audit_scanning')),
        rescan: @json(__('admin/settings/themes.permissions.audit_button_rescan')),
        completed: @json(__('admin/settings/themes.index.audit.completed')),
        failed: @json(__('admin/settings/themes.index.audit.failed')),
        resultTitle: @json(__('admin/settings/themes.permissions.audit_result_title') ?? 'スキャン結果'),
        noIssues: @json(__('admin/settings/themes.permissions.audit_no_issues') ?? '問題は検出されませんでした'),
        mismatchFound: @json(__('admin/settings/themes.permissions.audit_mismatch_title')),
        undeclaredUsage: @json(__('admin/settings/themes.permissions.audit_undeclared_usage')),
        unusedDeclaration: @json(__('admin/settings/themes.permissions.audit_unused_declaration')),
        close: @json(__('common.close')),
    };
    
    function showThemeAuditResultModal(slug, audit) {
        const modalId = 'themeAuditResultModal';
        let modal = document.getElementById(modalId);
        
        if (modal) {
            modal.remove();
        }
        
        let contentHtml = '';
        if (audit.has_mismatches && audit.mismatches && audit.mismatches.length > 0) {
            contentHtml = `
                <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        ${auditMessages.mismatchFound}
                    </p>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-5 list-disc">
                        ${audit.mismatches.slice(0, 10).map(m => `
                            <li>
                                <code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">${m.permission}</code>
                                - ${m.type === 'undeclared_usage' ? auditMessages.undeclaredUsage : auditMessages.unusedDeclaration}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        } else {
            contentHtml = `
                <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                    <p class="text-sm text-green-700 dark:text-green-300">
                        <i class="fas fa-check-circle mr-1"></i>
                        ${auditMessages.noIssues}
                    </p>
                </div>
            `;
        }
        
        contentHtml += `
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                チェック項目: ${audit.total_checked || 0} / 一致: ${audit.matches_count || 0} / 不一致: ${(audit.mismatches || []).length}
            </div>
        `;
        
        const modalHtml = `
            <div id="${modalId}" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(0,0,0,0.5);">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            <i class="fas fa-search mr-2"></i>${auditMessages.resultTitle}
                        </h3>
                        <button type="button" onclick="window.location.reload()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-4">
                        ${contentHtml}
                    </div>
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <button type="button" onclick="window.location.reload()" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700">
                            ${auditMessages.close}
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    document.querySelectorAll('.theme-audit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const slug = this.dataset.slug;
            const btnText = this.querySelector('.audit-btn-text');
            const icon = this.querySelector('i');
            const originalText = btnText.textContent;
            const originalIcon = icon.className;
            const button = this;
            
            button.disabled = true;
            btnText.textContent = auditMessages.scanning;
            icon.className = 'fas fa-spinner fa-spin mr-1';
            
            fetch('{{ route("admin.settings.themes.audit") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ slug: slug }),
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                btnText.textContent = auditMessages.rescan;
                icon.className = originalIcon;
                
                if (data.success) {
                    showThemeAuditResultModal(slug, data.audit);
                } else {
                    alert(data.message || auditMessages.failed);
                }
            })
            .catch(error => {
                console.error('Audit error:', error);
                alert(auditMessages.failed);
                button.disabled = false;
                btnText.textContent = originalText;
                icon.className = originalIcon;
            });
        });
    });
});
</script>
@endpush
