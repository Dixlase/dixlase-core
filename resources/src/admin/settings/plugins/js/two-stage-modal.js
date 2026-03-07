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

import { getAuditConfig, runPluginScan, populateResultContent } from './audit';

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
    const stage2CancelBtn = document.getElementById('pluginActionStage2CancelBtn');
    const stage2ConfirmBtn = document.getElementById('pluginActionStage2ConfirmBtn');

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
        const stage1El = document.getElementById('pluginActionStage1Modal');
        if (stage1El && stage1El._x_dataStack && stage1El._x_dataStack[0]) {
            stage1El._x_dataStack[0].submitting = true;
        }

        runPluginScan(currentAction.pluginSlug, auditUrl)
            .then(data => {
                // submitting を解除
                if (stage1El && stage1El._x_dataStack && stage1El._x_dataStack[0]) {
                    stage1El._x_dataStack[0].submitting = false;
                }

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

                if (stage1El && stage1El._x_dataStack && stage1El._x_dataStack[0]) {
                    stage1El._x_dataStack[0].submitting = false;
                }

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
                contentHtml += buildScanResultHtml(scanData.audit);
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
                contentHtml += buildScanResultHtml(scanData.audit);
            }
        } else {
            // 問題なし or スキャンスキップ: シンプルな確認
            const confirmTitle = currentAction.actionType === 'install' ? ts.stage2ConfirmInstall : ts.stage2ConfirmEnable;
            setModalTitle('pluginActionStage2Modal', confirmTitle);
            setModalIconType('pluginActionStage2Modal', 'info');

            if (scanData && scanData.audit) {
                contentHtml += buildScanResultHtml(scanData.audit);
            } else {
                contentHtml = `
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>${currentAction.pluginName}</strong>
                    </p>
                `;
            }
        }

        stage2Content.innerHTML = contentHtml;

        // ボタン設定
        if (isBlocked) {
            stage2ConfirmBtn.classList.add('hidden');
        } else {
            stage2ConfirmBtn.classList.remove('hidden');
            // ラベルとスタイル設定
            const btnLabel = currentAction.actionType === 'install' ? ts.install : ts.enable;
            const btnTextNode = stage2ConfirmBtn.querySelector('span') || stage2ConfirmBtn;
            if (btnTextNode.tagName === 'SPAN') {
                btnTextNode.textContent = btnLabel;
            } else {
                // span がない場合はテキストノードを探す
                const textNodes = Array.from(stage2ConfirmBtn.childNodes).filter(n => n.nodeType === Node.TEXT_NODE);
                if (textNodes.length > 0) {
                    textNodes[0].textContent = ' ' + btnLabel;
                }
            }
        }

        openModal('pluginActionStage2Modal');
    }

    /**
     * スキャン結果の概要HTMLを生成
     */
    function buildScanResultHtml(audit) {
        const riskLevel = audit.risk_level || 'low';
        const healthColors = {
            'low': { bg: 'bg-green-50 dark:bg-green-900/20', border: 'border-green-200 dark:border-green-800', text: 'text-green-700 dark:text-green-300', icon: 'fa-check-circle' },
            'medium': { bg: 'bg-yellow-50 dark:bg-yellow-900/20', border: 'border-yellow-200 dark:border-yellow-800', text: 'text-yellow-700 dark:text-yellow-300', icon: 'fa-exclamation-circle' },
            'high': { bg: 'bg-orange-50 dark:bg-orange-900/20', border: 'border-orange-200 dark:border-orange-800', text: 'text-orange-700 dark:text-orange-300', icon: 'fa-exclamation-triangle' },
            'unknown': { bg: 'bg-gray-50 dark:bg-gray-900/20', border: 'border-gray-200 dark:border-gray-800', text: 'text-gray-700 dark:text-gray-300', icon: 'fa-question-circle' }
        };
        const healthStyle = healthColors[riskLevel] || healthColors['unknown'];
        const healthLabels = config.healthLabels || {};

        let html = `
            <div class="p-3 rounded-lg ${healthStyle.bg} border ${healthStyle.border} mb-3">
                <div class="flex items-center gap-2 ${healthStyle.text}">
                    <i class="fas ${healthStyle.icon}"></i>
                    <span class="font-semibold">${config.healthBadgeLabel || ''}: ${healthLabels[riskLevel] || healthLabels['unknown'] || ''}</span>
                </div>
            </div>
        `;

        // attention reasons
        const attentionReasons = audit.formatted_attention_reasons || [];
        if (attentionReasons.length > 0) {
            html += `
                <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                    <ul class="text-sm space-y-1 ml-4 list-disc">
                        ${attentionReasons.map(r => `
                            <li class="${r.color || 'text-yellow-700 dark:text-yellow-300'}">
                                <i class="${r.icon || 'fas fa-info-circle'} mr-1"></i>
                                ${r.text || ''}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }

        return html;
    }

    /**
     * フォームをサブミット
     */
    function submitForm() {
        if (!currentAction || !currentAction.formId) return;
        const form = document.getElementById(currentAction.formId);
        if (form) {
            form.submit();
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
            closeModal('pluginActionStage2Modal');
            submitForm();
        });
    }
});
