{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api プラグイン/テーマから @include('components.content-editor.scroll-buttons') として使用可能

縦並びモード時のエディタ/プレビュー スクロールフローティングボタン。
メインコンテンツエリアの右下に配置され、右サイドバーの開閉に追従する。

Alpine.js 親コンポーネントが以下を提供する必要がある:
- isHorizontal, previewVisible, scrollToEditor(), scrollToPreview()
--}}

<div x-show="!isHorizontal && previewVisible" x-cloak
     x-data="{ _btnRight: 16 }"
     x-init="
        const updatePos = () => {
            const main = document.getElementById('admin-main-content');
            if (main) {
                _btnRight = Math.max(16, window.innerWidth - main.getBoundingClientRect().right + 16);
            }
        };
        updatePos();
        new ResizeObserver(updatePos).observe(document.getElementById('admin-main-content'));
     "
     class="fixed z-40 flex flex-col gap-2"
     :style="'right: ' + _btnRight + 'px; bottom: 4.5rem;'">
    <button type="button" @click="scrollToEditor()"
            class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition-colors"
            title="{{ __('components/content-editor.scroll_to_editor') }}">
        <i class="fas fa-edit text-sm"></i>
    </button>
    <button type="button" @click="scrollToPreview()"
            class="w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition-colors"
            title="{{ __('components/content-editor.scroll_to_preview') }}">
        <i class="fas fa-eye text-sm"></i>
    </button>
</div>
