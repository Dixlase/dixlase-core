/*
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 */

/**
 * CSP設定確認モーダルの自動ロールバック機能
 */

document.addEventListener('DOMContentLoaded', function () {
    let countdown = 10;
    let countdownInterval;
    const modal = document.getElementById('cspConfirmationModal');
    const countdownElement = document.getElementById('csp-countdown');

    // モーダルが存在しない場合は何もしない
    if (!modal) {
        return;
    }

    // モーダルを表示
    setTimeout(() => {
        const alpineData = Alpine.$data(modal);
        if (alpineData) {
            alpineData.show = true;
        }
    }, 100);

    // カウントダウン開始
    countdownInterval = setInterval(() => {
        countdown--;
        if (countdownElement) {
            countdownElement.textContent = countdown;
        }

        if (countdown <= 0) {
            clearInterval(countdownInterval);
            rollbackCspSettings();
        }
    }, 1000);

    // 確認ボタン
    window.confirmCspSettings = function () {
        clearInterval(countdownInterval);

        const confirmUrl = modal.dataset.confirmUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        fetch(confirmUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // モーダルを閉じる
                    const alpineData = Alpine.$data(modal);
                    if (alpineData) {
                        alpineData.show = false;
                    }

                    // 成功メッセージは表示しない（設定が確定されただけなので）
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'エラーが発生しました');
            });
    };

    // ロールバックボタン
    window.rollbackCspSettings = function () {
        clearInterval(countdownInterval);

        const rollbackUrl = modal.dataset.rollbackUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        fetch(rollbackUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // ページをリロード
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // エラーでもリロード
                window.location.reload();
            });
    };

    // 通知表示関数
    function showNotification(type, message) {
        // 既存の通知システムを使用
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
        } else {
            alert(message);
        }
    }
});
