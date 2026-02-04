/**
 * Passkey Prompt Modal Component
 * 
 * パスキー登録促進モーダルのAlpine.jsコンポーネント
 */

// Alpine.jsのグローバルコンポーネントとして登録
window.passkeyPromptModal = function(dismissUrl, modalId) {
    return {
        dontShowAgain: false,
        
        handleClose() {
            if (this.dontShowAgain) {
                this.dismissPrompt();
            }
        },
        
        closeModal() {
            if (this.dontShowAgain) {
                this.dismissPrompt();
            }
            
            // グローバル関数を使用してモーダルを閉じる
            if (typeof window.closeModal === 'function') {
                window.closeModal(modalId);
            }
        },
        
        dismissPrompt() {
            fetch(dismissUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .catch(error => {
                console.error('Error dismissing passkey prompt:', error);
            });
        }
    }
};
