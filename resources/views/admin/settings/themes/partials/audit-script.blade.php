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
        scanning: @json(__('admin/settings/themes/index.permissions.audit_scanning')),
        rescan: @json(__('admin/settings/themes/index.permissions.audit_button_rescan')),
        completed: @json(__('admin/settings/themes/index.audit.completed')),
        failed: @json(__('admin/settings/themes/index.audit.failed')),
        resultTitle: @json(__('admin/settings/themes/index.permissions.audit_result_title') ?? 'スキャン結果'),
        noIssues: @json(__('admin/settings/themes/index.permissions.audit_no_issues') ?? '問題は検出されませんでした'),
        mismatchFound: @json(__('admin/settings/themes/index.permissions.audit_mismatch_title')),
        undeclaredUsage: @json(__('admin/settings/themes/index.permissions.audit_undeclared_usage')),
        unusedDeclaration: @json(__('admin/settings/themes/index.permissions.audit_unused_declaration')),
        close: @json(__('common.close')),
    };
    
    function showThemeAuditResultModal(slug, audit) {
        const modalId = 'themeAuditResultModal';
        let modal = document.getElementById(modalId);
        
        if (modal) {
            modal.remove();
        }
        
        const hasIssues = audit.has_mismatches && audit.mismatches && audit.mismatches.length > 0;
        const riskLevel = audit.risk_level || 'low';
        
        let contentHtml = '';
        
        // 健全性ステータス
        const healthColors = {
            'low': { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-200 dark:border-green-800', text: 'text-green-700 dark:text-green-300', icon: 'fa-check-circle' },
            'medium': { bg: 'bg-yellow-50 dark:bg-yellow-900/20', border: 'border-yellow-200 dark:border-yellow-800', text: 'text-yellow-700 dark:text-yellow-300', icon: 'fa-exclamation-circle' },
            'high': { bg: 'bg-orange-50 dark:bg-orange-900/20', border: 'border-orange-200 dark:border-orange-800', text: 'text-orange-700 dark:text-orange-300', icon: 'fa-exclamation-triangle' },
            'unknown': { bg: 'bg-gray-50 dark:bg-gray-900/20', border: 'border-gray-200 dark:border-gray-800', text: 'text-gray-700 dark:text-gray-300', icon: 'fa-question-circle' }
        };
        const healthStyle = healthColors[riskLevel] || healthColors['unknown'];
        const healthLabels = {
            'low': @json(__('admin/settings/themes/index.permissions.health_healthy')),
            'medium': @json(__('admin/settings/themes/index.permissions.health_warning')),
            'high': @json(__('admin/settings/themes/index.permissions.health_needs_attention')),
            'unknown': @json(__('admin/settings/themes/index.permissions.health_not_verified'))
        };
        
        contentHtml += `
            <div class="p-3 rounded-lg ${healthStyle.bg} border ${healthStyle.border} mb-3">
                <div class="flex items-center gap-2 ${healthStyle.text}">
                    <i class="fas ${healthStyle.icon}"></i>
                    <span class="font-semibold">${@json(__('admin/settings/themes/index.badge_labels.health'))}: ${healthLabels[riskLevel] || healthLabels['unknown']}</span>
                </div>
            </div>
        `;
        
        // 権限不一致の警告
        if (hasIssues) {
            contentHtml += `
                <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 mb-3">
                    <p class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
                        <i class="fas fa-code-branch mr-1"></i>
                        ${auditMessages.mismatchFound}
                    </p>
                    <ul class="text-sm text-red-700 dark:text-red-300 space-y-1 ml-5 list-disc">
                        ${audit.mismatches.slice(0, 10).map(m => `
                            <li>
                                <code class="bg-red-100 dark:bg-red-800 px-1 rounded">${m.permission}</code>
                                - ${m.type === 'undeclared_usage' ? auditMessages.undeclaredUsage : auditMessages.unusedDeclaration}
                            </li>
                        `).join('')}
                    </ul>
                    ${audit.mismatches.length > 10 ? `<p class="text-xs text-red-600 dark:text-red-400 mt-2">...他 ${audit.mismatches.length - 10} 件</p>` : ''}
                </div>
            `;
        } else {
            contentHtml += `
                <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 mb-3">
                    <p class="text-sm text-green-700 dark:text-green-300">
                        <i class="fas fa-check-circle mr-1"></i>
                        ${auditMessages.noIssues}
                    </p>
                </div>
            `;
        }
        
        contentHtml += `
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                ${@json(__('admin/settings/themes/index.permissions.audit_stats'))}: ${audit.total_checked || 0} / 
                ${@json(__('admin/settings/themes/index.permissions.audit_matches'))}: ${audit.matches_count || 0} / 
                ${@json(__('admin/settings/themes/index.permissions.audit_mismatches'))}: ${(audit.mismatches || []).length}
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
            const icon = this.querySelector('i');
            const button = this;
            
            // ボタンのテキストノードを取得（アイコン以外のテキスト）
            const textNodes = Array.from(button.childNodes).filter(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
            const originalText = textNodes.length > 0 ? textNodes[0].textContent.trim() : '';
            const originalIcon = icon ? icon.className : '';
            
            button.disabled = true;
            if (textNodes.length > 0) {
                textNodes[0].textContent = ' ' + auditMessages.scanning;
            }
            if (icon) {
                icon.className = 'fas fa-spinner fa-spin mr-2';
            }
            
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
                if (textNodes.length > 0) {
                    textNodes[0].textContent = ' ' + auditMessages.rescan;
                }
                if (icon) {
                    icon.className = originalIcon;
                }
                
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
                if (textNodes.length > 0) {
                    textNodes[0].textContent = ' ' + originalText;
                }
                if (icon) {
                    icon.className = originalIcon;
                }
            });
        });
    });
});
</script>
@endpush
