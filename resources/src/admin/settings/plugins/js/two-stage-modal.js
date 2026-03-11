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
import { buildUnifiedScanResultHtml, buildHealthBadgeHtml } from './scan-result-builder';

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
    let scanWasPerformed = false;

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
    function startPluginAction(actionType, pluginName, pluginSlug, needsScan, formId, storedHealthData) {
        currentAction = { actionType, pluginName, pluginSlug, needsScan, formId };

        // スキャン不要（既にスキャン済みでファイル変更なし）の場合、保存済みデータで Stage 2
        if (!needsScan) {
            showStage2(storedHealthData || null);
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
        setModalTitle('pluginActionStage1Modal', ts.stage1Scanning || '');
        stage1Message.textContent = ts.stage1ScanningDescription || '';

        // submitting=true でモーダルを閉じられないようにする
        setModalSubmitting('pluginActionStage1Modal', true);

        runPluginScan(currentAction.pluginSlug, auditUrl)
            .then(data => {
                setModalSubmitting('pluginActionStage1Modal', false);
                closeModal('pluginActionStage1Modal');

                if (data.success) {
                    scanWasPerformed = true;
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

        // Stage 2 見出し（プラグイン名 + アクション前の確認）
        const scanResultSubheading = (ts.stage2ScanResultHeading || ':action')
            .replace(':action', actionLabel);
        const scanResultHeading = `${currentAction.pluginName}<br><span class="text-sm font-normal text-gray-500 dark:text-gray-400">${scanResultSubheading}</span>`;

        if (scanData && scanData.enableAction === 'blocked') {
            // ブロック: インストール/有効化不可
            isBlocked = true;
            const blockedTitle = (ts.stage2BlockedTitle || '').replace(':action', actionLabel);
            setModalTitle('pluginActionStage2Modal', blockedTitle);
            setModalIconType('pluginActionStage2Modal', 'danger');

            contentHtml = `
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 text-center">${scanResultHeading}</h3>
                <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 mb-3">
                    <p class="text-sm text-red-700 dark:text-red-300">
                        <i class="fas fa-ban mr-1"></i>
                        ${(ts.stage2BlockedMessage || '').replace(':action', actionedLabel)}
                    </p>
                </div>
            `;

            // スキャン結果の詳細も表示
            if (scanData.audit) {
                contentHtml += buildUnifiedScanResultHtml(scanData, config);
            } else if (scanData.healthIssues && scanData.healthIssues.length > 0) {
                contentHtml += buildHealthBadgeHtml(scanData, null, config);
            }
        } else if (scanData && (scanData.enableAction === 'warning' || scanData.enableAction === 'ack')) {
            // 警告あり: 確認して続行可能
            const confirmTitle = currentAction.actionType === 'install' ? ts.stage2ConfirmInstall : ts.stage2ConfirmEnable;
            setModalTitle('pluginActionStage2Modal', confirmTitle);
            setModalIconType('pluginActionStage2Modal', 'warning');

            contentHtml = `
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 text-center">${scanResultHeading}</h3>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3 text-center">${ts.stage2WarningMessage || ''}</p>
            `;

            if (scanData.audit) {
                contentHtml += buildUnifiedScanResultHtml(scanData, config);
            } else if (scanData.healthIssues && scanData.healthIssues.length > 0) {
                contentHtml += buildHealthBadgeHtml(scanData, null, config);
            }
        } else {
            // 問題なし or スキャンスキップ
            const confirmTitle = currentAction.actionType === 'install' ? ts.stage2ConfirmInstall : ts.stage2ConfirmEnable;
            setModalTitle('pluginActionStage2Modal', confirmTitle);

            if (scanData && scanData.audit) {
                setModalIconType('pluginActionStage2Modal', 'info');
                contentHtml += `<h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 text-center">${scanResultHeading}</h3>`;
                contentHtml += buildUnifiedScanResultHtml(scanData, config);
            } else if (scanData && scanData.healthIssues && scanData.healthIssues.length > 0) {
                // スキャン済みで減点項目あり: 健全性バッジで警告表示
                setModalIconType('pluginActionStage2Modal', 'warning');
                contentHtml = `
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 text-center">${scanResultHeading}</h3>
                `;
                contentHtml += buildHealthBadgeHtml(scanData, null, config);
            } else {
                setModalIconType('pluginActionStage2Modal', 'info');
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

            // 保存済み健全性データを解析（スキャン不要時に使用）
            let storedHealthData = null;
            const healthIssuesRaw = this.dataset.healthIssues;
            if (healthIssuesRaw) {
                try {
                    const healthIssues = JSON.parse(healthIssuesRaw);
                    const healthScore = this.dataset.healthScore ? parseInt(this.dataset.healthScore) : null;
                    const healthStatus = this.dataset.healthStatus || null;
                    const enableAction = this.dataset.enableAction || 'allowed';

                    if (healthIssues.length > 0 || enableAction !== 'allowed') {
                        storedHealthData = {
                            healthIssues,
                            healthScore,
                            healthStatus,
                            enableAction,
                            audit: null,
                        };
                    }
                } catch (e) {
                    // JSON解析失敗時はnullのまま
                }
            }

            startPluginAction(actionType, pluginName, pluginSlug, needsScan, formId, storedHealthData);
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

    // Stage 2: キャンセルボタン（スキャン実行後はページリロードしてバッジを更新）
    if (stage2CancelBtn) {
        stage2CancelBtn.addEventListener('click', function () {
            closeModal('pluginActionStage2Modal');
            if (scanWasPerformed) {
                window.location.reload();
            }
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

    // Quick-enable フォーム送信時に処理中モーダルを表示
    const quickEnableForm = document.getElementById('quickEnableForm');
    if (quickEnableForm) {
        quickEnableForm.addEventListener('submit', function () {
            // enable用の処理中モーダルを表示
            if (!currentAction) {
                currentAction = { actionType: 'enable' };
            }
            showProcessingModal();
        });
    }
});
