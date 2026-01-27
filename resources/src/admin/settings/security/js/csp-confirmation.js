/**
 * CSP設定確認モーダルの自動ロールバック機能
 */

document.addEventListener('DOMContentLoaded', function() {
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
    window.confirmCspSettings = function() {
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
                
                // 成功メッセージを表示
                const confirmMessage = modal.dataset.confirmMessage;
                showNotification('success', data.message || confirmMessage);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'エラーが発生しました');
        });
    };
    
    // ロールバックボタン
    window.rollbackCspSettings = function() {
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
