/**
 * Appearance Form Component
 * 
 * 外観設定フォームの保存成功時にテーマストアを更新
 */

export default function appearanceForm() {
    return {
        /**
         * 初期化
         */
        init() {
            // フォーム送信成功時にグローバルテーマストアを更新
            const successMessage = this.$el.dataset.success;
            const savedAppearance = this.$el.dataset.appearance;

            if (successMessage && savedAppearance && window.themeStore) {
                window.themeStore.theme = savedAppearance;
                window.themeStore.applyTheme();
            }
        }
    };
}

// グローバルに登録
window.appearanceForm = appearanceForm;
