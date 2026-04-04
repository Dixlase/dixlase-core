{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api プラグイン/テーマから @include('components.content-editor.scroll-buttons') として使用可能

縦並びモード時のエディタ/プレビュー スクロールフローティングボタン。
右サイドバーの開閉に連動して位置が変わる。
保存ボタンバーの上に配置される。

Alpine.js 親コンポーネントが以下を提供する必要がある:
- isHorizontal, previewVisible, scrollToEditor(), scrollToPreview()
- rightSidebarCollapsed（admin レイアウトから）
--}}

<div x-show="!isHorizontal && previewVisible" x-cloak
     class="fixed z-40 flex flex-col gap-2 transition-all duration-300"
     :class="rightSidebarCollapsed ? 'right-4' : 'right-[21.5rem]'"
     style="bottom: 4.5rem;">
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
