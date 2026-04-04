{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

テーマプレビューコンテナ（インラインプレビュー用）。
デバイス切替、フリーサイズ、CSSスケーリングを提供する。
共通プレビューペイン（content-editor/preview-pane）と同じUIパターンを使用。
--}}

@props([
    'title' => __('components/content-editor.preview_title'),
    'appearanceMode' => '0',
    'defaultDevice' => 'desktop',
    'outerId' => 'preview-outer',
    'innerId' => 'preview-inner',
])

<div class="relative">
    {{-- プレビューヘッダー --}}
    <div class="flex flex-wrap items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-t-lg gap-2">
        <div class="flex items-center gap-2">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-eye mr-1"></i>{{ $title }}
            </span>
            {{-- スケール表示（縮小時のみ） --}}
            <span x-show="previewScale < 1" x-cloak
                  class="text-[10px] text-gray-400 dark:text-gray-500"
                  x-text="Math.round(previewScale * 100) + '%'"></span>
        </div>

        <div class="flex items-center gap-3">
            {{-- デバイストグルボタン --}}
            <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-0.5 gap-0.5">
                <button type="button" @click="setPreviewDevice('mobile')"
                    :class="previewDevice === 'mobile' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                    title="{{ __('components/content-editor.device_mobile') }} (375×667)">
                    <i class="fas fa-mobile-alt"></i>
                </button>
                <button type="button" @click="setPreviewDevice('tablet')"
                    :class="previewDevice === 'tablet' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                    title="{{ __('components/content-editor.device_tablet') }} (768×1024)">
                    <i class="fas fa-tablet-alt"></i>
                </button>
                <button type="button" @click="setPreviewDevice('desktop')"
                    :class="previewDevice === 'desktop' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                    title="{{ __('components/content-editor.device_desktop') }} (1440×900)">
                    <i class="fas fa-desktop"></i>
                </button>
                <button type="button" @click="setPreviewDevice('free')"
                    :class="previewDevice === 'free' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                    title="{{ __('components/content-editor.device_free') }}">
                    <i class="fas fa-expand-arrows-alt"></i>
                </button>
            </div>

            {{-- サイズ表示 / フリーサイズ入力 --}}
            <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                <template x-if="previewDevice === 'free'">
                    <div class="flex items-center gap-1">
                        <input type="number" x-model.number="freeWidth" min="200" max="3840"
                               class="w-16 px-1.5 py-0.5 text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                               title="{{ __('components/content-editor.preview_width') }}">
                        <span>px</span>
                    </div>
                </template>
                <template x-if="previewDevice !== 'free'">
                    <span x-text="previewDeviceWidth + 'px'"></span>
                </template>
            </div>
        </div>
    </div>

    {{-- スケーリングプレビューコンテナ --}}
    <div class="rounded-b-lg overflow-hidden relative w-full border border-t-0 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900"
         id="{{ $outerId }}" style="min-height: 300px;">
        <div id="{{ $innerId }}"
             data-preview-theme="{{ $appearanceMode === '1' ? 'light' : 'dark' }}"
             @appearance-changed.window="$el.dataset.previewTheme = $event.detail.mode === '1' ? 'light' : 'dark'"
             :style="'visibility: ' + (_previewReady ? 'visible' : 'hidden') + '; width: ' + previewDeviceWidth + 'px; transform: scale(' + previewScale + '); transform-origin: top left; margin: 0;'"
             style="visibility: hidden;"
             class="relative">

            {{ $slot }}

        </div>
    </div>
</div>

@once
@push('scripts')
<script @cspNonce>
/**
 * テーマプレビューコンテナ用 Alpine.js ミックスイン
 * デバイス切替、フリーサイズ、CSSスケーリングを提供する。
 */
window.previewContainerMixin = function(outerId = 'preview-outer', innerId = 'preview-inner') {
    const DEVICE_WIDTHS = { mobile: 375, tablet: 768, desktop: 1440 };

    return {
        previewDevice: '{{ $defaultDevice }}',
        previewDeviceWidth: {{ $defaultDevice === 'mobile' ? 375 : ($defaultDevice === 'tablet' ? 768 : 1440) }},
        freeWidth: 1440,
        previewScale: 1,
        _previewReady: false,
        _containerWidth: 0,

        initPreviewContainer() {
            // 即座にスケール計算（FOUC防止）
            const outerImmediate = document.getElementById(outerId);
            if (outerImmediate && outerImmediate.offsetWidth > 0) {
                this._containerWidth = outerImmediate.offsetWidth;
                this.updatePreviewScale();
            }
            this._previewReady = true;

            this.$nextTick(() => {
                const outer = document.getElementById(outerId);
                if (!outer) return;

                const update = () => {
                    this._containerWidth = outer.offsetWidth;
                    this.updatePreviewScale();
                };

                new ResizeObserver(update).observe(outer);
                update();
            });
        },

        setPreviewDevice(device) {
            this.previewDevice = device;
            if (device === 'free') {
                this.previewDeviceWidth = this.freeWidth;
            } else {
                this.previewDeviceWidth = DEVICE_WIDTHS[device] || 1440;
            }
            this.$nextTick(() => this.updatePreviewScale());
        },

        updatePreviewScale() {
            const outer = document.getElementById(outerId);
            const inner = document.getElementById(innerId);
            if (!outer || !inner) return;

            // フリーモード: x-model で幅を同期
            if (this.previewDevice === 'free') {
                this.previewDeviceWidth = this.freeWidth;
            }

            const containerW = outer.offsetWidth;
            if (containerW <= 0 || this.previewDeviceWidth <= containerW) {
                this.previewScale = 1;
            } else {
                this.previewScale = containerW / this.previewDeviceWidth;
            }

            // コンテナ高さをスケーリングに合わせる
            const scaledH = inner.scrollHeight * this.previewScale;
            outer.style.height = Math.max(300, scaledH) + 'px';
        },
    };
};
</script>
@endpush
@endonce
