/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * ダークモード初期化スクリプト
 * ページロード前に即座に実行されてちらつきを防止
 */

// PCのシステム設定を即座にチェックしてダークモードを適用
if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.classList.add('dark');
}
