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
 * プラグイン監査スクリプト用JavaScript
 */

/**
 * プラグイン監査設定を取得
 *
 * @returns {object|null}
 */
export function getAuditConfig() {
    const configEl = document.getElementById('plugin-audit-config');
    if (!configEl) return null;
    return JSON.parse(configEl.textContent);
}

/**
 * スキャン中モーダルを開く（submitting=true で閉じ操作をブロック）
 */
export function openScanningModal() {
    window.openModal('pluginAuditScanningModal');
    const el = document.getElementById('pluginAuditScanningModal');
    if (el && el._x_dataStack && el._x_dataStack[0]) {
        el._x_dataStack[0].submitting = true;
    }
}

/**
 * スキャン中モーダルを閉じる（submitting を解除してから閉じる）
 */
export function closeScanningModal() {
    const el = document.getElementById('pluginAuditScanningModal');
    if (el && el._x_dataStack && el._x_dataStack[0]) {
        el._x_dataStack[0].submitting = false;
        el._x_dataStack[0].close();
    }
}

/**
 * プラグインのスキャンを実行（共通関数）
 *
 * @param {string} slug - プラグインのスラッグ
 * @param {string} auditUrl - 監査APIのURL
 * @returns {Promise<object>} - スキャン結果のJSONデータ
 */
export function runPluginScan(slug, auditUrl) {
    return fetch(auditUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ slug: slug }),
    }).then(response => response.json());
}

/**
 * スキャン結果のHTMLを生成して結果モーダルのコンテンツ領域に挿入する
 *
 * @param {object} scanData - APIレスポンス全体（audit, healthScore, healthStatus等を含む）
 * @param {object} config - 設定オブジェクト
 */
export function populateResultContent(scanData, config) {
    const audit = scanData.audit || {};
    const auditMessages = config.messages || {};
    const hasIssues = audit.has_mismatches && audit.mismatches && audit.mismatches.length > 0;
    const attentionReasons = audit.formatted_attention_reasons || [];

    const signatureLabels = config.signatureLabels || {};
    const cspLabels = config.cspLabels || {};

    let contentHtml = '';

    // 署名セクション（Adj 4a: セクションラベル追加）
    contentHtml += `
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            <i class="fas fa-file-signature mr-1"></i>
            ${config.signatureSectionLabel || ''}
        </p>
    `;
    contentHtml += buildSignatureStatusHtml(audit, signatureLabels);

    // 権限定義の整合性セクション（Adj 8: attention reasonsを統合）
    contentHtml += `
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 mt-1">
            <i class="fas fa-balance-scale mr-1"></i>
            ${config.permissionConsistencyTitle || ''}
        </p>
    `;

    if (hasIssues || attentionReasons.length > 0) {
        const boxBg = hasIssues ? 'bg-red-50 dark:bg-red-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20';
        const boxBorder = hasIssues ? 'border-red-200 dark:border-red-800' : 'border-yellow-200 dark:border-yellow-800';
        contentHtml += `<div class="p-3 rounded-lg ${boxBg} border ${boxBorder} mb-3">`;

        // 不一致の詳細
        if (hasIssues) {
            contentHtml += `
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
                contentHtml += `<div class="mt-2 pt-2 border-t ${hasIssues ? 'border-red-200 dark:border-red-700' : 'border-yellow-200 dark:border-yellow-700'}"></div>`;
            }
            contentHtml += `
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

        contentHtml += `</div>`;
    } else {
        contentHtml += `
            <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 mb-3">
                <p class="text-sm text-green-700 dark:text-green-300">
                    <i class="fas fa-check-circle mr-1"></i>
                    ${auditMessages.noIssues || ''}
                </p>
            </div>
        `;
    }

    // CSPステータス
    contentHtml += buildCspStatusHtml(audit, cspLabels);

    // 統計情報
    contentHtml += `
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            ${config.statsLabel || ''}: ${audit.total_checked || 0} /
            ${config.matchesLabel || ''}: ${audit.matches_count || 0} /
            ${config.mismatchesLabel || ''}: ${(audit.mismatches || []).length}
        </div>
    `;

    // 総合評価（Adj 2, 3, 7: 下部に移動、実スコア表示、PluginHealthScorerのステータス使用）
    contentHtml += buildHealthBadgeHtml(scanData, audit, config);

    const container = document.getElementById('pluginAuditResultContent');
    if (container) {
        container.innerHTML = contentHtml;
    }
}

/**
 * 総合評価バッジのHTMLを生成
 */
function buildHealthBadgeHtml(scanData, audit, config) {
    const healthScore = scanData.healthScore;
    const healthStatus = scanData.healthStatus || 'not_verified';
    const healthStatusLabels = config.healthStatusLabels || {};
    const signatureLabels = config.signatureLabels || {};

    // ステータス別の色とアイコン
    const statusColors = {
        'healthy': { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-200 dark:border-green-800', text: 'text-green-700 dark:text-green-300', icon: 'fa-check-circle' },
        'advisory': { bg: 'bg-yellow-50 dark:bg-yellow-900/20', border: 'border-yellow-200 dark:border-yellow-800', text: 'text-yellow-700 dark:text-yellow-300', icon: 'fa-exclamation-circle' },
        'needs_attention': { bg: 'bg-orange-50 dark:bg-orange-900/20', border: 'border-orange-200 dark:border-orange-800', text: 'text-orange-700 dark:text-orange-300', icon: 'fa-exclamation-triangle' },
        'not_verified': { bg: 'bg-gray-50 dark:bg-gray-900/20', border: 'border-gray-200 dark:border-gray-800', text: 'text-gray-700 dark:text-gray-300', icon: 'fa-question-circle' },
    };
    const style = statusColors[healthStatus] || statusColors['not_verified'];
    const statusLabel = healthStatusLabels[healthStatus] || healthStatus;

    // スコア表示テキスト
    const scoreText = healthScore !== null && healthScore !== undefined
        ? (config.healthScoreDisplay || '').replace(':score', healthScore)
        : '';

    // 署名による減点表示
    let deductionText = '';
    const sigStatus = audit.signature_status || 'unsigned';
    if (sigStatus === 'unsigned' || sigStatus === 'invalid') {
        const deductionPoints = sigStatus === 'unsigned' ? 5 : 15;
        deductionText = (config.signatureDeduction || '').replace(':points', deductionPoints);
    }

    let html = `
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
            ${deductionText ? `<p class="text-xs mt-1 ${style.text}"><i class="fas fa-minus-circle mr-1"></i>${signatureLabels.title || ''} ${deductionText}</p>` : ''}
        </div>
    `;

    return html;
}

/**
 * 署名ステータスのHTMLを生成
 */
function buildSignatureStatusHtml(audit, labels) {
    const status = audit.signature_status || 'unsigned';
    const signer = audit.signature_signer || '';

    const styles = {
        'official': { bg: 'bg-blue-50 dark:bg-blue-900/20', border: 'border-blue-200 dark:border-blue-800', text: 'text-blue-700 dark:text-blue-300', icon: 'fa-shield-alt' },
        'verified': { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-200 dark:border-green-800', text: 'text-green-700 dark:text-green-300', icon: 'fa-check-circle' },
        'partner': { bg: 'bg-indigo-50 dark:bg-indigo-900/20', border: 'border-indigo-200 dark:border-indigo-800', text: 'text-indigo-700 dark:text-indigo-300', icon: 'fa-handshake' },
        'signed': { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-200 dark:border-green-800', text: 'text-green-700 dark:text-green-300', icon: 'fa-check' },
        'invalid': { bg: 'bg-red-50 dark:bg-red-900/20', border: 'border-red-200 dark:border-red-800', text: 'text-red-700 dark:text-red-300', icon: 'fa-times-circle' },
        'unsigned': { bg: 'bg-amber-50 dark:bg-amber-900/20', border: 'border-amber-200 dark:border-amber-800', text: 'text-amber-700 dark:text-amber-300', icon: 'fa-exclamation-triangle' },
        'pending': { bg: 'bg-gray-50 dark:bg-gray-900/20', border: 'border-gray-200 dark:border-gray-800', text: 'text-gray-700 dark:text-gray-300', icon: 'fa-clock' },
    };
    const style = styles[status] || styles['unsigned'];
    const statusLabel = labels[status] || status;

    let html = `
        <div class="p-3 rounded-lg ${style.bg} border ${style.border} mb-3">
            <div class="flex items-center gap-2 ${style.text}">
                <i class="fas ${style.icon}"></i>
                <span class="font-semibold">${labels.title || ''}: ${statusLabel}</span>
            </div>
    `;

    if (signer) {
        html += `<p class="text-xs mt-1 ${style.text}">${labels.signedBy || ''}: ${signer}</p>`;
    }

    if (status === 'unsigned' && labels.unsignedInfo) {
        html += `
            <p class="text-xs mt-1 ${style.text}">
                <i class="fas fa-info-circle mr-1"></i>
                ${labels.unsignedInfo}
            </p>
        `;
    } else if (status === 'invalid' && labels.invalidWarning) {
        html += `
            <p class="text-xs mt-1 ${style.text}">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                ${labels.invalidWarning}
            </p>
        `;
    }

    html += '</div>';
    return html;
}

/**
 * CSPステータスのHTMLを生成
 */
function buildCspStatusHtml(audit, labels) {
    const cspStatus = audit.csp_status || 'unknown';
    const requiresInlineJs = audit.csp_requires_inline_js || false;
    const requiresInlineCss = audit.csp_requires_inline_css || false;

    if (cspStatus === 'unknown') return '';

    const isCompliant = cspStatus === 'compliant';
    const style = isCompliant
        ? { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-200 dark:border-green-800', text: 'text-green-700 dark:text-green-300', icon: 'fa-check-circle' }
        : { bg: 'bg-amber-50 dark:bg-amber-900/20', border: 'border-amber-200 dark:border-amber-800', text: 'text-amber-700 dark:text-amber-300', icon: 'fa-exclamation-triangle' };

    let html = `
        <div class="p-3 rounded-lg ${style.bg} border ${style.border} mb-3">
            <div class="flex items-center gap-2 ${style.text}">
                <i class="fas ${style.icon}"></i>
                <span class="font-semibold">${labels.title || ''}: ${isCompliant ? (labels.compliant || '') : (labels.notCompliant || '')}</span>
            </div>
    `;

    if (!isCompliant) {
        const issues = [];
        if (requiresInlineJs) issues.push(labels.inlineScripts || 'Inline Scripts');
        if (requiresInlineCss) issues.push(labels.inlineStyles || 'Inline Styles');
        if (issues.length > 0) {
            html += `
                <p class="text-xs mt-1 ${style.text}">
                    <i class="fas fa-info-circle mr-1"></i>
                    ${issues.join(', ')}
                </p>
            `;
        }
    }

    html += '</div>';
    return html;
}

document.addEventListener('DOMContentLoaded', function () {
    const config = getAuditConfig();
    if (!config) return;

    const auditMessages = config.messages || {};
    const auditUrl = config.auditUrl || '';

    // 結果モーダルの閉じるボタン（CSP対応: インラインonclickではなくイベントリスナーで登録）
    const resultCloseBtn = document.getElementById('pluginAuditResultCloseBtn');
    if (resultCloseBtn) {
        resultCloseBtn.addEventListener('click', function () {
            window.location.reload();
        });
    }

    document.querySelectorAll('.audit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const slug = this.dataset.slug;
            const icon = this.querySelector('i');
            const button = this;

            // ボタンのテキストノードを取得（アイコン以外のテキスト）
            const textNodes = Array.from(button.childNodes).filter(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
            const originalText = textNodes.length > 0 ? textNodes[0].textContent.trim() : '';
            const originalIcon = icon ? icon.className : '';

            button.disabled = true;
            if (textNodes.length > 0) {
                textNodes[0].textContent = ' ' + (auditMessages.scanning || '');
            }
            if (icon) {
                icon.className = 'fas fa-spinner fa-spin mr-2';
            }

            // スキャン中モーダルを開く
            openScanningModal();

            runPluginScan(slug, auditUrl)
            .then(data => {
                // スキャン中モーダルを閉じる
                closeScanningModal();

                button.disabled = false;
                if (textNodes.length > 0) {
                    textNodes[0].textContent = ' ' + (auditMessages.rescan || '');
                }
                if (icon) {
                    icon.className = originalIcon;
                }

                if (data.success) {
                    // 結果コンテンツを挿入して結果モーダルを開く
                    populateResultContent(data, config);
                    // スキャン中モーダルが完全に閉じるのを待つ
                    setTimeout(function () {
                        window.openModal('pluginAuditResultModal');
                    }, 150);
                } else {
                    alert(data.message || auditMessages.failed || '');
                }
            })
            .catch(error => {
                console.error('Audit error:', error);

                // スキャン中モーダルを閉じる
                closeScanningModal();

                alert(auditMessages.failed || '');
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
