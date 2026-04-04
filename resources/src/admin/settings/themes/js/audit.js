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
 * テーマ監査スクリプト用JavaScript
 */

import { buildUnifiedScanResultHtml } from '../../plugins/js/scan-result-builder';

document.addEventListener('DOMContentLoaded', function () {
    const configEl = document.getElementById('theme-audit-config');
    if (!configEl) return;

    const config = JSON.parse(configEl.textContent);
    const auditMessages = config.messages || {};
    const auditUrl = config.auditUrl || '';

    // 結果モーダルの閉じる動作を管理するフラグ
    // true: スキャン実行後（ページリロード） / false: バッジクリック（単純に閉じる）
    let resultModalReloadOnClose = false;

    /**
     * スキャン中モーダルを開く（submitting=true で閉じ操作をブロック）
     */
    function openScanningModal() {
        window.openModal('themeAuditScanningModal');
        const el = document.getElementById('themeAuditScanningModal');
        if (el && el._x_dataStack && el._x_dataStack[0]) {
            el._x_dataStack[0].submitting = true;
        }
    }

    /**
     * スキャン中モーダルを閉じる（submitting を解除してから閉じる）
     */
    function closeScanningModal() {
        const el = document.getElementById('themeAuditScanningModal');
        if (el && el._x_dataStack && el._x_dataStack[0]) {
            el._x_dataStack[0].submitting = false;
            el._x_dataStack[0].close();
        }
    }

    /**
     * スキャン結果のHTMLを生成して結果モーダルのコンテンツ領域に挿入する
     */
    function populateResultContent(scanData) {
        const container = document.getElementById('themeAuditResultContent');
        if (container) {
            container.innerHTML = buildUnifiedScanResultHtml(scanData, config);
        }
    }

    // 結果モーダルの閉じるボタン
    const resultCloseBtn = document.getElementById('themeAuditResultCloseBtn');
    if (resultCloseBtn) {
        resultCloseBtn.addEventListener('click', function () {
            if (resultModalReloadOnClose) {
                window.location.reload();
            } else {
                window.closeModal('themeAuditResultModal');
            }
        });
    }

    // バッジ詳細ボタン: data-scan-data からスキャンデータを読み取り結果モーダルに表示
    document.querySelectorAll('.badge-detail-btn').forEach(function (btn) {
        // プラグインページのバッジはプラグイン側で処理するためスキップ
        if (document.getElementById('plugin-audit-config')) return;

        btn.addEventListener('click', function () {
            const container = this.closest('[data-scan-data]');
            if (!container) return;

            const scanData = JSON.parse(container.dataset.scanData);
            const themeName = container.dataset.pluginName || '';

            // リロードなしモードに設定
            resultModalReloadOnClose = false;

            // モーダルタイトルにテーマ名を設定
            const titleEl = document.querySelector('#themeAuditResultModal .modal-title');
            if (titleEl) {
                titleEl.textContent = (auditMessages.resultTitle || '') + (themeName ? ' - ' + themeName : '');
            }

            // 結果コンテンツを挿入して結果モーダルを開く
            populateResultContent(scanData);
            window.openModal('themeAuditResultModal');
        });
    });

    document.querySelectorAll('.theme-audit-btn').forEach(function (btn) {
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

            fetch(auditUrl, {
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
                    // スキャン実行後はリロードモードに設定
                    resultModalReloadOnClose = true;

                    // 結果コンテンツを挿入して結果モーダルを開く
                    populateResultContent(data);
                    // スキャン中モーダルが完全に閉じるのを待つ
                    setTimeout(function () {
                        window.openModal('themeAuditResultModal');
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
