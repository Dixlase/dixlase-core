/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * スキャン結果HTML共通ビルダー
 * audit.js と two-stage-modal.js で共有する
 */

/**
 * 署名ステータスのスタイルマップ
 */
const SIGNATURE_STYLES = {
    'official': { bg: 'bg-blue-50 dark:bg-blue-900/20', text: 'text-blue-700 dark:text-blue-300', icon: 'fa-shield-alt' },
    'verified': { bg: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-700 dark:text-green-300', icon: 'fa-check-circle' },
    'partner': { bg: 'bg-indigo-50 dark:bg-indigo-900/20', text: 'text-indigo-700 dark:text-indigo-300', icon: 'fa-handshake' },
    'signed': { bg: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-700 dark:text-green-300', icon: 'fa-check' },
    'invalid': { bg: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-700 dark:text-red-300', icon: 'fa-times-circle' },
    'unsigned': { bg: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-700 dark:text-amber-300', icon: 'fa-exclamation-triangle' },
    'pending': { bg: 'bg-gray-50 dark:bg-gray-900/20', text: 'text-gray-700 dark:text-gray-300', icon: 'fa-clock' },
};

/**
 * 健全性ステータスのスタイルマップ
 */
const HEALTH_STATUS_STYLES = {
    'healthy': { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-200 dark:border-green-800', text: 'text-green-700 dark:text-green-300', icon: 'fa-check-circle' },
    'advisory': { bg: 'bg-yellow-50 dark:bg-yellow-900/20', border: 'border-yellow-200 dark:border-yellow-800', text: 'text-yellow-700 dark:text-yellow-300', icon: 'fa-exclamation-circle' },
    'needs_attention': { bg: 'bg-orange-50 dark:bg-orange-900/20', border: 'border-orange-200 dark:border-orange-800', text: 'text-orange-700 dark:text-orange-300', icon: 'fa-exclamation-triangle' },
    'not_verified': { bg: 'bg-gray-50 dark:bg-gray-900/20', border: 'border-gray-200 dark:border-gray-800', text: 'text-gray-700 dark:text-gray-300', icon: 'fa-question-circle' },
};

/**
 * 統合スキャン結果HTMLを生成
 * 署名・権限整合性・CSPを1つのボックスにまとめ、総合評価は別ボックスで表示
 *
 * @param {object} scanData - APIレスポンス全体（audit, healthScore, healthStatus等を含む）
 * @param {object} config - 設定オブジェクト（getAuditConfig()の戻り値）
 * @returns {string} 生成されたHTML文字列
 */
export function buildUnifiedScanResultHtml(scanData, config) {
    const audit = scanData.audit || {};
    const signatureLabels = config.signatureLabels || {};
    const cspLabels = config.cspLabels || {};
    const auditMessages = config.messages || {};

    let html = '';

    // 「スキャン結果」見出し
    html += `
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            <i class="fas fa-clipboard-check mr-1"></i>
            ${auditMessages.resultTitle || ''}
        </p>
    `;

    // 統合ボックス: 署名 + 権限整合性 + CSP
    html += '<div class="rounded-lg border border-gray-200 dark:border-gray-700 mb-3 overflow-hidden">';

    // 署名サブセクション
    html += buildSignatureSection(audit, signatureLabels, config);

    // 区切り線
    html += '<div class="border-t border-gray-200 dark:border-gray-700"></div>';

    // 権限整合性サブセクション
    html += buildPermissionsSection(audit, auditMessages, config);

    // CSPサブセクション（該当する場合のみ）
    const cspHtml = buildCspSection(audit, cspLabels);
    if (cspHtml) {
        html += '<div class="border-t border-gray-200 dark:border-gray-700"></div>';
        html += cspHtml;
    }

    html += '</div>';

    // 統計情報
    html += `
        <div class="text-xs text-gray-500 dark:text-gray-400">
            ${config.statsLabel || ''}: ${audit.total_checked || 0} /
            ${config.matchesLabel || ''}: ${audit.matches_count || 0} /
            ${config.mismatchesLabel || ''}: ${(audit.mismatches || []).length}
        </div>
    `;

    // 総合評価（別ボックス）
    html += buildHealthBadgeHtml(scanData, audit, config);

    return html;
}

/**
 * 署名サブセクションのHTMLを生成
 */
function buildSignatureSection(audit, labels, config) {
    const status = audit.signature_status || 'unsigned';
    const signer = audit.signature_signer || '';
    const style = SIGNATURE_STYLES[status] || SIGNATURE_STYLES['unsigned'];
    const statusLabel = labels[status] || status;

    let html = `<div class="p-3 ${style.bg}">`;

    // セクションヘッダー
    html += `
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
            <i class="fas fa-file-signature mr-1"></i>
            ${config.signatureSectionLabel || ''}
        </p>
    `;

    // ステータス表示
    html += `
        <div class="flex items-center gap-2 ${style.text}">
            <i class="fas ${style.icon}"></i>
            <span class="font-semibold text-sm">${labels.title || ''}: ${statusLabel}</span>
        </div>
    `;

    if (signer) {
        html += `<p class="text-xs mt-1 ${style.text}">${labels.signedBy || ''}: ${signer}</p>`;
    }

    if (status === 'unsigned' && labels.unsignedInfo) {
        html += `<p class="text-xs mt-1 ${style.text}"><i class="fas fa-info-circle mr-1"></i>${labels.unsignedInfo}</p>`;
    } else if (status === 'invalid' && labels.invalidWarning) {
        html += `<p class="text-xs mt-1 ${style.text}"><i class="fas fa-exclamation-triangle mr-1"></i>${labels.invalidWarning}</p>`;
    }

    html += '</div>';
    return html;
}

/**
 * 権限整合性サブセクションのHTMLを生成
 */
function buildPermissionsSection(audit, auditMessages, config) {
    const hasIssues = audit.has_mismatches && audit.mismatches && audit.mismatches.length > 0;
    const attentionReasons = audit.formatted_attention_reasons || [];

    // 問題の深刻度に応じた背景色
    let bgClass = 'bg-green-50 dark:bg-green-900/20';
    if (hasIssues) {
        bgClass = 'bg-red-50 dark:bg-red-900/20';
    } else if (attentionReasons.length > 0) {
        bgClass = 'bg-yellow-50 dark:bg-yellow-900/20';
    }

    let html = `<div class="p-3 ${bgClass}">`;

    // セクションヘッダー
    html += `
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
            <i class="fas fa-balance-scale mr-1"></i>
            ${config.permissionConsistencyTitle || ''}
        </p>
    `;

    if (hasIssues || attentionReasons.length > 0) {
        // 不一致の詳細
        if (hasIssues) {
            html += `
                <p class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
                    <i class="fas fa-code-branch mr-1"></i>
                    ${auditMessages.mismatchFound || ''}
                </p>
                <ul class="text-sm text-red-700 dark:text-red-300 space-y-1 ml-5 list-disc">
                    ${audit.mismatches.slice(0, 10).map(m => `
                        <li>
                            <code class="bg-red-100 dark:bg-red-800 px-1 rounded">${m.permission}</code>
                            - ${m.type === 'undeclared_usage' ? (auditMessages.undeclaredUsage || '') : (auditMessages.unusedDeclaration || '')}
                        </li>
                    `).join('')}
                </ul>
                ${audit.mismatches.length > 10 ? `<p class="text-xs text-red-600 dark:text-red-400 mt-2">...+${audit.mismatches.length - 10}</p>` : ''}
            `;
        }

        // attention reasons（統合表示）
        if (attentionReasons.length > 0) {
            if (hasIssues) {
                html += '<div class="mt-2 pt-2 border-t border-red-200 dark:border-red-700"></div>';
            }
            html += `
                <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    ${config.attentionReasonsTitle || ''}
                </p>
                <ul class="text-sm space-y-1 ml-5 list-disc">
                    ${attentionReasons.map(r => `
                        <li class="${r.color || 'text-yellow-700 dark:text-yellow-300'}">
                            <i class="${r.icon || 'fas fa-info-circle'} mr-1"></i>
                            ${r.text || ''}
                            ${r.score ? `<span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200">+${r.score}</span>` : ''}
                        </li>
                    `).join('')}
                </ul>
            `;
        }
    } else {
        html += `
            <p class="text-sm text-green-700 dark:text-green-300">
                <i class="fas fa-check-circle mr-1"></i>
                ${auditMessages.noIssues || ''}
            </p>
        `;
    }

    html += '</div>';
    return html;
}

/**
 * CSPサブセクションのHTMLを生成
 *
 * @returns {string} HTMLまたは空文字列（CSPステータスがunknownの場合）
 */
function buildCspSection(audit, labels) {
    const cspStatus = audit.csp_status || 'unknown';
    if (cspStatus === 'unknown') return '';

    const isCompliant = cspStatus === 'compliant';
    const style = isCompliant
        ? { bg: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-700 dark:text-green-300', icon: 'fa-check-circle' }
        : { bg: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-700 dark:text-amber-300', icon: 'fa-exclamation-triangle' };

    let html = `<div class="p-3 ${style.bg}">`;
    html += `
        <div class="flex items-center gap-2 ${style.text}">
            <i class="fas ${style.icon}"></i>
            <span class="font-semibold text-sm">${labels.title || ''}: ${isCompliant ? (labels.compliant || '') : (labels.notCompliant || '')}</span>
        </div>
    `;

    if (!isCompliant) {
        const issues = [];
        if (audit.csp_requires_inline_js) issues.push(labels.inlineScripts || 'Inline Scripts');
        if (audit.csp_requires_inline_css) issues.push(labels.inlineStyles || 'Inline Styles');
        if (issues.length > 0) {
            html += `<p class="text-xs mt-1 ${style.text}"><i class="fas fa-info-circle mr-1"></i>${issues.join(', ')}</p>`;
        }
    }

    html += '</div>';
    return html;
}

/**
 * 総合評価バッジのHTMLを生成（別ボックス）
 */
function buildHealthBadgeHtml(scanData, audit, config) {
    const healthScore = scanData.healthScore;
    const healthStatus = scanData.healthStatus || 'not_verified';
    const healthStatusLabels = config.healthStatusLabels || {};
    const signatureLabels = config.signatureLabels || {};

    const style = HEALTH_STATUS_STYLES[healthStatus] || HEALTH_STATUS_STYLES['not_verified'];
    const statusLabel = healthStatusLabels[healthStatus] || healthStatus;

    // スコア表示テキスト
    const scoreText = healthScore !== null && healthScore !== undefined
        ? (config.healthScoreDisplay || '').replace(':score', healthScore)
        : '';

    // 減点項目の表示（APIから返されたhealthIssuesを使用）
    const healthIssues = scanData.healthIssues || [];
    const issueTypeLabels = config.healthIssueTypeLabels || {};
    let deductionHtml = '';

    if (healthIssues.length > 0) {
        deductionHtml = healthIssues.map(issue => {
            const label = issueTypeLabels[issue.type] || issue.description || issue.type;
            const points = Math.abs(issue.deduction || 0);
            return `<p class="text-xs mt-1 ${style.text}"><i class="fas fa-minus-circle mr-1"></i>${label} <span class="font-medium">(-${points})</span></p>`;
        }).join('');
    }

    return `
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 mt-3">
            <i class="fas fa-chart-bar mr-1"></i>
            ${config.totalEvaluationLabel || ''}
        </p>
        <div class="p-3 rounded-lg ${style.bg} border ${style.border}">
            <div class="flex items-center gap-2 ${style.text}">
                <i class="fas ${style.icon}"></i>
                <span class="font-semibold">${config.healthBadgeLabel || ''}: ${statusLabel}</span>
                ${scoreText ? `<span class="text-xs ml-1">${scoreText}</span>` : ''}
            </div>
            ${deductionHtml}
        </div>
    `;
}
