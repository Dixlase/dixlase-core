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
 * 2段階モーダルフロー（プラグインインストール/有効化）
 */

import { getAuditConfig, runPluginScan } from './audit';

/**
 * モーダルを開く
 */
function openModal(id) {
    window.openModal(id);
}

/**
 * モーダルを閉じる
 */
function closeModal(id) {
    const el = document.getElementById(id);
    if (el && el._x_dataStack && el._x_dataStack[0]) {
        el._x_dataStack[0].close();
    }
}

/**
 * モーダルのタイトルを設定
 */
function setModalTitle(id, title) {
    const el = document.getElementById(id);
    if (el && el._x_dataStack && el._x_dataStack[0]) {
        el._x_dataStack[0].title = title;
    }
}

/**
 * モーダルのアイコンタイプを設定
 */
function setModalIconType(id, iconType) {
    const el = document.getElementById(id);
    if (el && el._x_dataStack && el._x_dataStack[0]) {
        el._x_dataStack[0].iconType = iconType;
    }
}

/**
 * モーダルのメッセージを設定
 */
function setModalMessage(id, message) {
    const el = document.getElementById(id);
    if (el && el._x_dataStack && el._x_dataStack[0]) {
        el._x_dataStack[0].message = message;
    }
}

/**
 * モーダルのsubmitting状態を設定
 */
function setModalSubmitting(id, submitting) {
    const el = document.getElementById(id);
    if (el && el._x_dataStack && el._x_dataStack[0]) {
        el._x_dataStack[0].submitting = submitting;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const config = getAuditConfig();
    if (!config) return;

    const auditUrl = config.auditUrl || '';
    const scanRequired = config.scanRequired || false;
    const ts = config.twoStage || {};

    // 現在のアクション情報を保持
    let currentAction = null;

    // Stage 1 要素
    const stage1Message = document.getElementById('pluginActionStage1Message');
    const stage1Spinner = document.getElementById('pluginActionStage1Spinner');
    const stage1Buttons = document.getElementById('pluginActionStage1Buttons');
    const stage1CancelBtn = document.getElementById('pluginActionStage1CancelBtn');
    const stage1SkipBtn = document.getElementById('pluginActionStage1SkipBtn');
    const stage1ScanBtn = document.getElementById('pluginActionStage1ScanBtn');

    // Stage 2 要素
    const stage2Content = document.getElementById('pluginActionStage2Content');
    const stage2ConfirmMessage = document.getElementById('pluginActionStage2ConfirmMessage');
    const stage2CancelBtn = document.getElementById('pluginActionStage2CancelBtn');
    const stage2ConfirmBtn = document.getElementById('pluginActionStage2ConfirmBtn');
    const stage2ConfirmLabel = document.getElementById('pluginActionStage2ConfirmLabel');

    if (!stage1Message || !stage2Content) return;

    /**
     * 2段階フローを開始
     */
    function startPluginAction(actionType, pluginName, pluginSlug, needsScan, formId) {
        currentAction = { actionType, pluginName, pluginSlug, needsScan, formId };

        // スキャン不要（既にスキャン済みでファイル変更なし）の場合、直接 Stage 2
        if (!needsScan) {
            showStage2(null);
            return;
        }

        // Stage 1 表示
        showStage1();
    }

    /**
     * Stage 1: スキャン判定/進捗
     */
    function showStage1() {
        const actionLabel = currentAction.actionType === 'install' ? ts.actionInstall : ts.actionEnable;

        if (scanRequired) {
            // Strict/Balanced: スキャン必須 → 自動開始
            setModalTitle('pluginActionStage1Modal', ts.stage1ScanRequiredTitle);
            stage1Message.textContent = (ts.stage1ScanRequiredMessage || '').replace(':action', actionLabel);
            stage1SkipBtn.classList.add('hidden');
            stage1ScanBtn.classList.remove('hidden');
            stage1Buttons.classList.remove('hidden');
            stage1Spinner.classList.add('hidden');
        } else {
            // Development: スキャンオプション → スキップ可能
            setModalTitle('pluginActionStage1Modal', ts.stage1ScanOptionalTitle);
            stage1Message.textContent = (ts.stage1ScanOptionalMessage || '').replace(':action', actionLabel);
            stage1SkipBtn.classList.remove('hidden');
            stage1ScanBtn.classList.remove('hidden');
            stage1Buttons.classList.remove('hidden');
            stage1Spinner.classList.add('hidden');
        }

        setModalIconType('pluginActionStage1Modal', 'info');
        openModal('pluginActionStage1Modal');
    }

    /**
     * スキャンを実行してStage 2に遷移
     */
    function executeScan() {
        // スピナー表示、ボタン非表示
        stage1Buttons.classList.add('hidden');
        stage1Spinner.classList.remove('hidden');
        stage1Message.textContent = ts.stage1ScanningDescription || '';

        // submitting=true でモーダルを閉じられないようにする
        setModalSubmitting('pluginActionStage1Modal', true);

        runPluginScan(currentAction.pluginSlug, auditUrl)
            .then(data => {
                setModalSubmitting('pluginActionStage1Modal', false);
                closeModal('pluginActionStage1Modal');

                if (data.success) {
                    setTimeout(function () {
                        showStage2(data);
                    }, 150);
                } else {
                    alert(data.message || (config.messages || {}).failed || '');
                }
            })
            .catch(error => {
                console.error('Two-stage scan error:', error);
                setModalSubmitting('pluginActionStage1Modal', false);
                closeModal('pluginActionStage1Modal');
                alert((config.messages || {}).failed || '');
            });
    }

    /**
     * Stage 2: アクション確認またはブロック表示
     *
     * @param {object|null} scanData - スキャン結果データ（スキャンをスキップした場合はnull）
     */
    function showStage2(scanData) {
        const actionLabel = currentAction.actionType === 'install' ? ts.actionInstall : ts.actionEnable;
        const actionedLabel = currentAction.actionType === 'install' ? ts.actionInstalled : ts.actionEnabled;

        let contentHtml = '';
        let isBlocked = false;

        if (scanData && scanData.enableAction === 'blocked') {
            // ブロック: インストール/有効化不可
            isBlocked = true;
            const blockedTitle = (ts.stage2BlockedTitle || '').replace(':action', actionLabel);
            setModalTitle('pluginActionStage2Modal', blockedTitle);
            setModalIconType('pluginActionStage2Modal', 'danger');

            contentHtml = `
                <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 mb-3">
                    <p class="text-sm text-red-700 dark:text-red-300">
                        <i class="fas fa-ban mr-1"></i>
                        ${(ts.stage2BlockedMessage || '').replace(':action', actionedLabel)}
                    </p>
                </div>
            `;

            // スキャン結果の詳細も表示
            if (scanData.audit) {
                contentHtml += buildScanResultHtml(scanData);
            }
        } else if (scanData && (scanData.enableAction === 'warning' || scanData.enableAction === 'ack')) {
            // 警告あり: 確認して続行可能
            const confirmTitle = currentAction.actionType === 'install' ? ts.stage2ConfirmInstall : ts.stage2ConfirmEnable;
            setModalTitle('pluginActionStage2Modal', confirmTitle);
            setModalIconType('pluginActionStage2Modal', 'warning');

            contentHtml = `
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">${ts.stage2WarningMessage || ''}</p>
            `;

            if (scanData.audit) {
                contentHtml += buildScanResultHtml(scanData);
            }
        } else {
            // 問題なし or スキャンスキップ: シンプルな確認
            const confirmTitle = currentAction.actionType === 'install' ? ts.stage2ConfirmInstall : ts.stage2ConfirmEnable;
            setModalTitle('pluginActionStage2Modal', confirmTitle);
            setModalIconType('pluginActionStage2Modal', 'info');

            if (scanData && scanData.audit) {
                contentHtml += buildScanResultHtml(scanData);
            } else {
                contentHtml = `
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>${currentAction.pluginName}</strong>
                    </p>
                `;
            }
        }

        stage2Content.innerHTML = contentHtml;

        // 確認メッセージ表示
        if (stage2ConfirmMessage) {
            if (isBlocked) {
                stage2ConfirmMessage.classList.add('hidden');
                stage2ConfirmMessage.textContent = '';
            } else {
                const confirmMsg = (ts.stage2ConfirmActionMessage || '').replace(':action', actionLabel);
                stage2ConfirmMessage.textContent = confirmMsg;
                stage2ConfirmMessage.classList.remove('hidden');
            }
        }

        // ボタン設定
        if (isBlocked) {
            stage2ConfirmBtn.classList.add('hidden');
        } else {
            stage2ConfirmBtn.classList.remove('hidden');
            stage2ConfirmBtn.disabled = false;
            // ラベル設定
            const btnLabel = currentAction.actionType === 'install' ? ts.install : ts.enable;
            if (stage2ConfirmLabel) {
                stage2ConfirmLabel.textContent = btnLabel;
            }
        }

        // キャンセルボタンをリセット
        if (stage2CancelBtn) {
            stage2CancelBtn.disabled = false;
        }

        openModal('pluginActionStage2Modal');
    }

    /**
     * スキャン結果の詳細HTMLを生成（署名・権限整合性・CSP含む）
     *
     * @param {object} scanData - APIレスポンス全体（audit, healthScore, healthStatus等を含む）
     */
    function buildScanResultHtml(scanData) {
        const audit = scanData.audit || {};
        const signatureLabels = config.signatureLabels || {};
        const cspLabels = config.cspLabels || {};
        const auditMessages = config.messages || {};

        let html = '';

        // 署名セクション（Adj 4a: セクションラベル追加）
        html += `
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                <i class="fas fa-file-signature mr-1"></i>
                ${config.signatureSectionLabel || ''}
            </p>
        `;
        html += buildSignatureHtml(audit, signatureLabels);

        // 権限定義の整合性セクション（Adj 8: attention reasonsを統合）
        const hasIssues = audit.has_mismatches && audit.mismatches && audit.mismatches.length > 0;
        const attentionReasons = audit.formatted_attention_reasons || [];
        html += `
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 mt-1">
                <i class="fas fa-balance-scale mr-1"></i>
                ${config.permissionConsistencyTitle || ''}
            </p>
        `;

        if (hasIssues || attentionReasons.length > 0) {
            const boxBg = hasIssues ? 'bg-red-50 dark:bg-red-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20';
            const boxBorder = hasIssues ? 'border-red-200 dark:border-red-800' : 'border-yellow-200 dark:border-yellow-800';
            html += `<div class="p-3 rounded-lg ${boxBg} border ${boxBorder} mb-3">`;

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
                    html += `<div class="mt-2 pt-2 border-t ${hasIssues ? 'border-red-200 dark:border-red-700' : 'border-yellow-200 dark:border-yellow-700'}"></div>`;
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

            html += `</div>`;
        } else {
            html += `
                <div class="p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 mb-3">
                    <p class="text-sm text-green-700 dark:text-green-300">
                        <i class="fas fa-check-circle mr-1"></i>
                        ${auditMessages.noIssues || ''}
                    </p>
                </div>
            `;
        }

        // CSPステータス
        html += buildCspHtml(audit, cspLabels);

        // 統計情報
        html += `
            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                ${config.statsLabel || ''}: ${audit.total_checked || 0} /
                ${config.matchesLabel || ''}: ${audit.matches_count || 0} /
                ${config.mismatchesLabel || ''}: ${(audit.mismatches || []).length}
            </div>
        `;

        // 総合評価（Adj 2, 3, 7: 下部に移動、実スコア表示、PluginHealthScorerのステータス使用）
        html += buildHealthBadgeHtml(scanData, audit);

        return html;
    }

    /**
     * 総合評価バッジのHTMLを生成
     */
    function buildHealthBadgeHtml(scanData, audit) {
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
    function buildSignatureHtml(audit, labels) {
        const status = audit.signature_status || 'unsigned';
        const signer = audit.signature_signer || '';

        // 署名ステータス別の色とアイコン
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
            html += `
                <p class="text-xs mt-1 ${style.text}">${labels.signedBy || ''}: ${signer}</p>
            `;
        }

        // 未署名・無効の警告メッセージ
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
    function buildCspHtml(audit, labels) {
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

    /**
     * 処理中モーダルを表示
     */
    function showProcessingModal() {
        const actionLabel = currentAction.actionType === 'install' ? ts.processingInstall : ts.processingEnable;
        const actionDescription = currentAction.actionType === 'install' ? ts.processingInstallDescription : ts.processingEnableDescription;

        // モーダルのタイトルとメッセージをDOM要素に直接設定
        const modalEl = document.getElementById('pluginActionProcessingModal');
        if (modalEl) {
            const titleEl = modalEl.querySelector('.modal-title');
            if (titleEl) titleEl.textContent = actionLabel || '';
            const messageEl = modalEl.querySelector('.modal-message p');
            if (messageEl) messageEl.textContent = actionDescription || '';
        }
        setModalIconType('pluginActionProcessingModal', 'info');
        setModalSubmitting('pluginActionProcessingModal', true);
        openModal('pluginActionProcessingModal');
    }

    /**
     * フォームをサブミット
     */
    function submitForm() {
        if (!currentAction || !currentAction.formId) return;
        const form = document.getElementById(currentAction.formId);
        if (form) {
            // 処理中モーダルを表示してからサブミット
            showProcessingModal();
            setTimeout(function () {
                form.submit();
            }, 100);
        }
    }

    // イベントリスナー登録

    // 2段階フロー開始ボタン（.two-stage-action-btn）
    document.querySelectorAll('.two-stage-action-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const actionType = this.dataset.actionType;
            const needsScan = this.dataset.needsScan === '1';
            const pluginSlug = this.dataset.pluginSlug;
            const pluginName = this.dataset.pluginName;
            const formId = this.dataset.formId;

            startPluginAction(actionType, pluginName, pluginSlug, needsScan, formId);
        });
    });

    // Stage 1: キャンセルボタン
    if (stage1CancelBtn) {
        stage1CancelBtn.addEventListener('click', function () {
            closeModal('pluginActionStage1Modal');
            currentAction = null;
        });
    }

    // Stage 1: スキップボタン（Devモードのみ表示）
    if (stage1SkipBtn) {
        stage1SkipBtn.addEventListener('click', function () {
            closeModal('pluginActionStage1Modal');
            setTimeout(function () {
                showStage2(null);
            }, 150);
        });
    }

    // Stage 1: スキャン開始ボタン
    if (stage1ScanBtn) {
        stage1ScanBtn.addEventListener('click', function () {
            executeScan();
        });
    }

    // Stage 2: キャンセルボタン
    if (stage2CancelBtn) {
        stage2CancelBtn.addEventListener('click', function () {
            closeModal('pluginActionStage2Modal');
            currentAction = null;
        });
    }

    // Stage 2: 確認ボタン（フォーム送信）
    if (stage2ConfirmBtn) {
        stage2ConfirmBtn.addEventListener('click', function () {
            // ボタンを無効化してスピナーを表示
            stage2ConfirmBtn.disabled = true;
            if (stage2CancelBtn) stage2CancelBtn.disabled = true;

            closeModal('pluginActionStage2Modal');
            submitForm();
        });
    }

    // Quick-enable フォーム送信時に処理中モーダルを表示（Adj 6）
    const quickEnableForm = document.getElementById('quickEnableForm');
    if (quickEnableForm) {
        quickEnableForm.addEventListener('submit', function () {
            // enable用の処理中モーダルを表示
            currentAction = { actionType: 'enable' };
            showProcessingModal();
        });
    }
});
