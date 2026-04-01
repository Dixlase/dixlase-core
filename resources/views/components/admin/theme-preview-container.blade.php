{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

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

Reusable preview container with device toggle, scaling, and theme isolation.
--}}

@props([
    'title' => __('common.preview'),
    'appearanceMode' => '0',
    'defaultDevice' => 'desktop',
    'outerId' => 'preview-outer',
    'innerId' => 'preview-inner',
])

<div class="relative">
    {{-- Preview Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            <i class="fas fa-eye mr-1.5"></i>{{ $title }}
        </h3>

        <div class="flex items-center gap-3">
            {{-- Device Toggle Buttons --}}
            <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-0.5 gap-0.5">
                <button type="button" @click="setPreviewDevice('mobile')"
                    :class="previewDevice === 'mobile' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    class="px-2.5 py-1.5 rounded-md transition-all text-xs" title="Mobile (375px)">
                    <i class="fas fa-mobile-alt"></i>
                </button>
                <button type="button" @click="setPreviewDevice('tablet')"
                    :class="previewDevice === 'tablet' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    class="px-2.5 py-1.5 rounded-md transition-all text-xs" title="Tablet (768px)">
                    <i class="fas fa-tablet-alt"></i>
                </button>
                <button type="button" @click="setPreviewDevice('desktop')"
                    :class="previewDevice === 'desktop' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                    class="px-2.5 py-1.5 rounded-md transition-all text-xs" title="Desktop (1440px)">
                    <i class="fas fa-desktop"></i>
                </button>
            </div>

            <span class="text-xs text-gray-400 dark:text-gray-500" x-text="previewDeviceWidth + 'px'"></span>
        </div>
    </div>

    {{-- Scaled Preview Container --}}
    <div class="rounded-xl overflow-hidden relative w-full border border-gray-200 dark:border-gray-700" id="{{ $outerId }}" style="min-height: 300px;">
        <div class="transition-[width] duration-300" id="{{ $innerId }}"
             data-preview-theme="{{ $appearanceMode === '1' ? 'light' : 'dark' }}"
             @appearance-changed.window="$el.dataset.previewTheme = $event.detail.mode === '1' ? 'light' : 'dark'"
             :style="'width: 100%; max-width: ' + previewDeviceWidth + 'px; margin: 0 auto;'"
             class="relative"

            {{ $slot }}

        </div>
    </div>
</div>

@once
@push('scripts')
<script @cspNonce>
/**
 * Preview container mixin for Alpine.js components.
 * Provides: previewDevice, previewDeviceWidth, setPreviewDevice(), updatePreviewScale(), initPreviewContainer()
 */
window.previewContainerMixin = function(outerId = 'preview-outer', innerId = 'preview-inner') {
    return {
        previewDevice: '{{ $defaultDevice }}',
        previewDeviceWidth: {{ $defaultDevice === 'mobile' ? 375 : ($defaultDevice === 'tablet' ? 768 : 1440) }},

        initPreviewContainer() {
            this.$nextTick(() => {
                const outer = document.getElementById(outerId);
                const inner = document.getElementById(innerId);
                let lastWidth = outer ? outer.offsetWidth : 0;
                let lastHeight = inner ? inner.scrollHeight : 0;
                setInterval(() => {
                    const w = outer ? outer.offsetWidth : 0;
                    const h = inner ? inner.scrollHeight : 0;
                    if (w !== lastWidth || h !== lastHeight) {
                        lastWidth = w;
                        lastHeight = h;
                        this.updatePreviewScale();
                    }
                }, 200);
                this.updatePreviewScale();
            });
        },

        setPreviewDevice(device) {
            const widths = { mobile: 375, tablet: 768, desktop: 1440 };
            this.previewDevice = device;
            this.previewDeviceWidth = widths[device];
            this.$nextTick(() => this.updatePreviewScale());
        },

        updatePreviewScale() {
            const outer = document.getElementById(outerId);
            const inner = document.getElementById(innerId);
            if (outer && inner) {
                // 全デバイス: スケーリングなし、max-width + 中央寄せのリキッドレイアウト
                inner.style.maxWidth = this.previewDeviceWidth + 'px';
                inner.style.transform = 'none';
                outer.style.height = 'auto';
                outer.style.minHeight = '300px';
            }
        },
    };
};
</script>
@endpush
@endonce
