/**
 * Appearance Mode Transition Manager
 * 
 * 外観モード切り替え時のアニメーション管理
 * - 外観設定ページでのみアニメーションを有効化
 * - 他のページでは即座に反映
 */

class AppearanceTransitionManager {
    constructor() {
        // アニメーション時間を一元管理（ミリ秒）
        this.transitionDuration = 300; // 通常の要素（サイドバー、フッターなど）
        this.backgroundTransitionDuration = 200; // メインコンテンツとセクションの背景色
        this.transitionClass = `transition-colors duration-[${this.transitionDuration}ms]`;
        this.isAppearancePage = false;
        this.elementsToAnimate = [];
        this.debug = false; // デバッグモード
    }

    /**
     * 初期化
     */
    init() {
        // 外観設定ページかどうかを判定
        this.isAppearancePage = this.checkIfAppearancePage();

        if (this.debug) {
            console.log('[AppearanceTransition] 初期化', {
                isAppearancePage: this.isAppearancePage,
                currentPath: window.location.pathname
            });
        }

        if (this.isAppearancePage) {
            this.enableTransitions();
        }
    }

    /**
     * 外観設定ページかどうかを判定
     */
    checkIfAppearancePage() {
        // URLまたはbody要素のdata属性で判定
        const path = window.location.pathname;
        return path.includes('/profile/appearance');
    }

    /**
     * トランジションを有効化
     */
    enableTransitions() {
        // アニメーション対象の要素を取得
        this.elementsToAnimate = this.getElementsToAnimate();

        if (this.debug) {
            console.log('[AppearanceTransition] アニメーション対象要素', {
                count: this.elementsToAnimate.length,
                elements: this.elementsToAnimate.map(el => ({
                    tag: el.tagName,
                    classes: el.className,
                    id: el.id || '(no id)',
                    selector: this.getElementSelector(el)
                }))
            });
        }

        // 各要素にトランジションクラスを追加
        this.elementsToAnimate.forEach(element => {
            this.addTransitionClass(element);
        });
    }

    /**
     * アニメーション対象の要素を取得
     * 注: 管理バー、保存ボタンエリア、main要素は個別設定のため除外
     */
    getElementsToAnimate() {
        const elements = [];

        // 1. section要素（フォーム内とarticle内の両方を検索）
        const sections = document.querySelectorAll('section, article section, form section');
        if (this.debug) {
            console.log('[AppearanceTransition] section要素の検索', {
                count: sections.length,
                allSections: document.querySelectorAll('section').length,
                formSections: document.querySelectorAll('form section').length,
                articleSections: document.querySelectorAll('article section').length,
                sections: Array.from(sections).map(s => ({
                    classes: s.className,
                    text: s.textContent.substring(0, 50)
                }))
            });
        }
        sections.forEach(section => elements.push(section));

        // 2. サイドバー
        const sidebar = document.querySelector('aside[role="navigation"]');
        if (sidebar) elements.push(sidebar);

        // 3. ページヘッダー（タイトルと説明）
        const pageHeader = document.querySelector('header.w-full');
        if (pageHeader) elements.push(pageHeader);

        // 4. フッター
        const footer = document.querySelector('footer');
        if (footer) elements.push(footer);

        // 注: main要素はAlpine.jsで管理するため除外

        return elements;
    }

    /**
     * 要素にトランジションクラスを追加
     */
    addTransitionClass(element) {
        if (!element) return;

        // section要素かどうかを判定
        const isSection = element.tagName.toLowerCase() === 'section';
        const duration = isSection ? this.backgroundTransitionDuration : this.transitionDuration;

        // 既存のクラスを保持しながら追加
        const existingClasses = element.className;
        const hadTransition = existingClasses.includes('transition-colors') || existingClasses.includes('transition-all');

        if (!hadTransition) {
            element.classList.add('transition-colors', `duration-[${duration}ms]`);

            if (this.debug) {
                console.log('[AppearanceTransition] トランジション追加', {
                    element: this.getElementSelector(element),
                    duration: `${duration}ms`,
                    isSection: isSection,
                    before: existingClasses,
                    after: element.className
                });
            }
        } else {
            // 既存のdurationクラスを統一する
            this.updateTransitionDuration(element, duration);

            if (this.debug) {
                console.log('[AppearanceTransition] トランジション既存（duration更新）', {
                    element: this.getElementSelector(element),
                    duration: `${duration}ms`,
                    isSection: isSection,
                    classes: element.className
                });
            }
        }
    }

    /**
     * 管理バーと保存ボタンエリアのdurationを強制的に更新する
     */
    forceUpdateAdminBarAndSaveButtonDuration() {
        const adminBar = document.querySelector('#admin-bar');
        const saveButtonArea = document.querySelector('.sticky.bottom-0');

        if (adminBar) this.updateTransitionDuration(adminBar);
        if (saveButtonArea) this.updateTransitionDuration(saveButtonArea);
    }

    /**
     * 要素のtransition durationを統一する
     */
    updateTransitionDuration(element, duration = null) {
        const targetDuration = duration || this.transitionDuration;

        // 既存のduration-*クラスを削除（duration-[500ms]などの任意値も含む）
        const classes = element.className.split(' ');
        const filteredClasses = classes.filter(cls =>
            !cls.startsWith('duration-') && !cls.match(/^duration-\[.*\]$/)
        );
        element.className = filteredClasses.join(' ');

        // ブラウザにスタイルの再計算を強制
        void element.offsetHeight;

        // 新しいdurationを追加
        element.classList.add(`duration-[${targetDuration}ms]`);

        // インラインスタイルで確実に適用（Tailwindクラスが効かない場合の保険）
        element.style.transitionDuration = `${targetDuration}ms`;

        if (this.debug) {
            console.log('[AppearanceTransition] Duration更新完了', {
                element: this.getElementSelector(element),
                newDuration: `${targetDuration}ms`,
                computedStyle: window.getComputedStyle(element).transitionDuration
            });
        }
    }

    /**
     * 要素のセレクタを取得（デバッグ用）
     */
    getElementSelector(element) {
        if (!element) return 'null';

        let selector = element.tagName.toLowerCase();
        if (element.id) selector += `#${element.id}`;
        if (element.className) {
            const classes = element.className.split(' ').slice(0, 3).join('.');
            if (classes) selector += `.${classes}`;
        }
        return selector;
    }

    /**
     * トランジションを無効化（他のページ用）
     */
    disableTransitions() {
        // disable-transitionクラスを追加して即座に反映
        const html = document.documentElement;
        html.classList.add('disable-transition');

        // 次のフレームで削除（CSSの適用後）
        requestAnimationFrame(() => {
            html.classList.remove('disable-transition');
        });
    }
}

// グローバルインスタンスを作成
window.appearanceTransitionManager = new AppearanceTransitionManager();

// DOMContentLoaded時に初期化（少し遅延させる）
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Alpine.jsなどの初期化を待つため、少し遅延
        setTimeout(() => {
            window.appearanceTransitionManager.init();
        }, 100);
    });
} else {
    // 既にDOMが読み込まれている場合も少し遅延
    setTimeout(() => {
        window.appearanceTransitionManager.init();
    }, 100);
}

export default AppearanceTransitionManager;
