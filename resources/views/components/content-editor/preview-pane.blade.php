{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-content-editor.preview-pane />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

{{-- ===== Drag Divider (only when side-by-side and preview visible) ===== --}}
<div x-show="isHorizontal && previewVisible" x-cloak
     class="flex items-center justify-center w-2 flex-shrink-0 cursor-col-resize transition-colors"
     :class="isDragging ? 'bg-blue-500 dark:bg-blue-400' : 'bg-gray-200 dark:bg-gray-700 hover:bg-blue-400 dark:hover:bg-blue-500'"
     @mousedown="startDrag($event)">
    <div class="w-0.5 h-8 rounded-full"
         :class="isDragging ? 'bg-white' : 'bg-gray-400 dark:bg-gray-500'"></div>
</div>

{{-- ===== Preview Pane ===== --}}
<div x-show="previewVisible" x-cloak
     x-ref="previewPane"
     class="w-full min-w-0"
     :class="isHorizontal ? 'overflow-y-auto' : ''"
     :style="isHorizontal ? { width: ((1 - splitRatio) * 100) + '%', maxHeight: 'calc(100vh - 160px)' } : {}">

    {{-- Preview Header --}}
    <div class="flex flex-wrap items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-t-lg gap-2">
        <div class="flex items-center gap-2">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-eye mr-1"></i>{{ __('components/content-editor.preview_title') }}
            </span>
            {{-- Scale display (only when scaled down) --}}
            <span x-show="previewScale < 1" x-cloak
                  class="text-[10px] text-gray-400 dark:text-gray-500"
                  x-text="Math.round(previewScale * 100) + '%'"></span>
        </div>

        <div class="flex items-center gap-3">
            {{-- Device toggle buttons --}}
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

            {{-- Size display / free size input --}}
            <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                <template x-if="previewDevice === 'free'">
                    <div class="flex items-center gap-1">
                        <input type="number" x-model.number="freeWidth" min="200" max="3840"
                               class="w-16 px-1.5 py-0.5 text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                               title="{{ __('components/content-editor.preview_width') }}">
                        <span>×</span>
                        <input type="number" x-model.number="freeHeight" min="200" max="3840"
                               class="w-16 px-1.5 py-0.5 text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                               title="{{ __('components/content-editor.preview_height') }}">
                        <span>px</span>
                    </div>
                </template>
                <template x-if="previewDevice !== 'free'">
                    <span x-text="currentPreviewWidth + '×' + currentPreviewHeight + 'px'"></span>
                </template>
            </div>
        </div>
    </div>

    {{-- Preview Container --}}
    <div x-ref="previewContainer"
         class="relative overflow-hidden bg-gray-100 dark:bg-gray-900 border border-t-0 border-gray-200 dark:border-gray-700 rounded-b-lg"
         :style="'height: ' + scaledPreviewHeight + 'px'">
        {{-- Loading Overlay --}}
        <div x-show="previewLoading" x-transition class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 z-10">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-3 block"></i>
                <p class="text-sm text-gray-500">{{ __('components/content-editor.preview_loading') }}</p>
            </div>
        </div>

        {{-- Scaling Wrapper --}}
        <div :style="'width: ' + scaledPreviewWidth + 'px; height: ' + scaledPreviewHeight + 'px; margin: 0 auto;'">
            <iframe x-ref="previewIframe"
                    :src="previewFrameUrl"
                    class="bg-white"
                    :style="'width: ' + currentPreviewWidth + 'px; height: ' + currentPreviewHeight + 'px; transform: scale(' + previewScale + '); transform-origin: top left;'"
                    sandbox="allow-scripts allow-same-origin allow-forms"
                    title="{{ __('components/content-editor.preview_title') }}">
            </iframe>
        </div>

        {{-- Bottom resize handle --}}
        <div class="absolute -bottom-1.5 left-0 w-full h-3 cursor-ns-resize group z-10"
             @mousedown="startPreviewResize($event, 'vertical')">
            <div class="absolute bottom-0.5 left-1/2 -translate-x-1/2 h-1 w-8 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-blue-400 transition-colors"></div>
        </div>
    </div>
</div>
