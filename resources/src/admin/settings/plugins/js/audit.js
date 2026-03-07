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
 */
export function populateResultContent(audit, config) {
    const auditMessages = config.messages || {};
    const hasIssues = audit.has_mismatches && audit.mismatches && audit.mismatches.length > 0;
    const riskLevel = audit.risk_level || 'low';

    const healthColors = {
        'low': { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-200 dark:border-green-800', text: 'text-green-700 dark:text-green-300', icon: 'fa-check-circle' },
        'medium': { bg: 'bg-yellow-50 dark:bg-yellow-900/20', border: 'border-yellow-200 dark:border-yellow-800', text: 'text-yellow-700 dark:text-yellow-300', icon: 'fa-exclamation-circle' },
        'high': { bg: 'bg-orange-50 dark:bg-orange-900/20', border: 'border-orange-200 dark:border-orange-800', text: 'text-orange-700 dark:text-orange-300', icon: 'fa-exclamation-triangle' },
        'unknown': { bg: 'bg-gray-50 dark:bg-gray-900/20', border: 'border-gray-200 dark:border-gray-800', text: 'text-gray-700 dark:text-gray-300', icon: 'fa-question-circle' }
    };
    const healthStyle = healthColors[riskLevel] || healthColors['unknown'];
    const healthLabels = config.healthLabels || {};

    let contentHtml = '';

    // 健全性ステータス
    contentHtml += `
        <div class="p-3 rounded-lg ${healthStyle.bg} border ${healthStyle.border} mb-3">
            <div class="flex items-center gap-2 ${healthStyle.text}">
                <i class="fas ${healthStyle.icon}"></i>
                <span class="font-semibold">${config.healthBadgeLabel || ''}: ${healthLabels[riskLevel] || healthLabels['unknown'] || ''}</span>
            </div>
        </div>
    `;

    // 確認が必要な理由（attention reasons）
    const attentionReasons = audit.formatted_attention_reasons || [];
    if (attentionReasons.length > 0) {
        contentHtml += `
            <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
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
                ${(() => {
                    const totalScore = attentionReasons.reduce((sum, r) => sum + (r.score || 0), 0);
                    return totalScore > 0 ? `
                        <div class="mt-2 pt-2 border-t border-yellow-200 dark:border-yellow-700 flex items-center justify-between">
                            <span class="text-xs font-medium text-yellow-700 dark:text-yellow-300">${config.totalRiskScoreLabel || ''}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold ${riskLevel === 'high' ? 'bg-orange-200 dark:bg-orange-800 text-orange-800 dark:text-orange-200' : riskLevel === 'medium' ? 'bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200' : 'bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200'}">${totalScore}</span>
                        </div>
                    ` : '';
                })()}
            </div>
        `;
    }

    // 権限定義の整合性セクション見出し
    contentHtml += `
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 mt-1">
            <i class="fas fa-balance-scale mr-1"></i>
            ${config.permissionConsistencyTitle || ''}
        </p>
    `;

    // 権限不一致の警告
    if (hasIssues) {
        contentHtml += `
            <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 mb-3">
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
                ${audit.mismatches.length > 10 ? `<p class="text-xs text-red-600 dark:text-red-400 mt-2">...他 ${audit.mismatches.length - 10} 件</p>` : ''}
            </div>
        `;
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

    contentHtml += `
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            ${config.statsLabel || ''}: ${audit.total_checked || 0} /
            ${config.matchesLabel || ''}: ${audit.matches_count || 0} /
            ${config.mismatchesLabel || ''}: ${(audit.mismatches || []).length}
        </div>
    `;

    const container = document.getElementById('pluginAuditResultContent');
    if (container) {
        container.innerHTML = contentHtml;
    }
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
                    populateResultContent(data.audit, config);
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
