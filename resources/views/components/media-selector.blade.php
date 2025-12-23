{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
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
--}}

@props([
    'id' => 'mediaSelectorModal',
    'inputId' => 'media_id',
    'previewId' => 'media_preview',
    'multiple' => false,
    'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']
])

<!-- メディア選択モーダル -->
<div id="{{ $id }}" 
     class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50 dark:bg-opacity-70"
     aria-labelledby="{{ $id }}-title"
     role="dialog"
     aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- モーダルコンテンツ -->
        <div class="inline-block w-full max-w-6xl overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl dark:bg-gray-800 sm:my-8 sm:align-middle">
            <!-- ヘッダー -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 id="{{ $id }}-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('common.select_media') }}
                </h3>
                <button type="button" 
                        onclick="closeMediaSelector('{{ $id }}')"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- 検索とフィルター -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex-1">
                        <input type="text" 
                               id="{{ $id }}-search"
                               placeholder="{{ __('common.search') }}..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="flex gap-2">
                        <select id="{{ $id }}-type-filter" 
                                class="px-4 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ __('common.all_types') }}</option>
                            <option value="image">{{ __('common.images') }}</option>
                            <option value="video">{{ __('common.videos') }}</option>
                            <option value="document">{{ __('common.documents') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- メディアグリッド -->
            <div class="px-6 py-4" style="max-height: 60vh; overflow-y: auto;">
                <div id="{{ $id }}-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <!-- JavaScriptで動的に読み込み -->
                    <div class="col-span-full flex items-center justify-center py-12">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('common.loading') }}...</p>
                        </div>
                    </div>
                </div>

                <!-- ページネーション -->
                <div id="{{ $id }}-pagination" class="mt-6 hidden">
                    <!-- JavaScriptで動的に生成 -->
                </div>
            </div>

            <!-- フッター -->
            <div class="flex items-center justify-between px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="{{ $id }}-selected-count">0</span> {{ __('common.items_selected') }}
                </div>
                <div class="flex gap-3">
                    <button type="button" 
                            onclick="closeMediaSelector('{{ $id }}')"
                            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button" 
                            onclick="confirmMediaSelection('{{ $id }}', '{{ $inputId }}', '{{ $previewId }}', {{ $multiple ? 'true' : 'false' }})"
                            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('common.select') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script @cspNonce>
// グローバル変数
window.mediaSelectorData = window.mediaSelectorData || {};

// モーダルを開く
function openMediaSelector(modalId, inputId, previewId, multiple = false, aspectRatio = 'original') {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // 初期化
    window.mediaSelectorData[modalId] = {
        selectedMedia: multiple ? [] : null,
        inputId: inputId,
        previewId: previewId,
        multiple: multiple,
        aspectRatio: aspectRatio,
        currentPage: 1
    };
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // メディアを読み込み
    loadMediaForSelector(modalId);
}

// モーダルを閉じる
function closeMediaSelector(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    
    // データをクリア
    delete window.mediaSelectorData[modalId];
}

// メディア一覧を読み込み
async function loadMediaForSelector(modalId, page = 1) {
    const grid = document.getElementById(`${modalId}-grid`);
    if (!grid) return;
    
    try {
        const response = await fetch(`{{ route('admin.media.api') }}?page=${page}&per_page=20`);
        const data = await response.json();
        
        if (data.success) {
            renderMediaGrid(modalId, data.media.data);
            renderPagination(modalId, data.media);
        }
    } catch (error) {
        console.error('Failed to load media:', error);
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                <p class="text-red-600 dark:text-red-400">{{ __('common.error_loading_media') }}</p>
            </div>
        `;
    }
}

// メディアグリッドをレンダリング
function renderMediaGrid(modalId, mediaItems) {
    const grid = document.getElementById(`${modalId}-grid`);
    if (!grid || !mediaItems || mediaItems.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-images text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">{{ __('common.no_media_found') }}</p>
            </div>
        `;
        return;
    }
    
    const data = window.mediaSelectorData[modalId];
    
    grid.innerHTML = mediaItems.map(media => {
        const isImage = media.type.startsWith('image/');
        const isSelected = data.multiple 
            ? data.selectedMedia.some(m => m.id === media.id)
            : data.selectedMedia?.id === media.id;
        
        return `
            <div class="media-selector-item ${isSelected ? 'selected' : ''}" 
                 data-media-id="${media.id}"
                 onclick="toggleMediaSelection('${modalId}', ${JSON.stringify(media).replace(/"/g, '&quot;')})">
                <div class="relative aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-blue-500 transition-all">
                    ${isImage 
                        ? `<img src="${media.url}" alt="${media.name}" class="w-full h-full object-cover">`
                        : `<div class="flex items-center justify-center w-full h-full">
                             <i class="fas fa-file-alt text-4xl text-gray-400"></i>
                           </div>`
                    }
                    <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 transition-all"></div>
                    ${isSelected 
                        ? `<div class="absolute top-2 right-2 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
                             <i class="fas fa-check text-white text-xs"></i>
                           </div>`
                        : ''
                    }
                </div>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300 truncate" title="${media.name}">${media.name}</p>
            </div>
        `;
    }).join('');
}

// メディア選択をトグル
function toggleMediaSelection(modalId, media) {
    const data = window.mediaSelectorData[modalId];
    if (!data) return;
    
    if (data.multiple) {
        const index = data.selectedMedia.findIndex(m => m.id === media.id);
        if (index > -1) {
            data.selectedMedia.splice(index, 1);
        } else {
            data.selectedMedia.push(media);
        }
    } else {
        data.selectedMedia = data.selectedMedia?.id === media.id ? null : media;
    }
    
    // 選択数を更新
    updateSelectedCount(modalId);
    
    // グリッドを再描画
    const grid = document.getElementById(`${modalId}-grid`);
    const items = grid.querySelectorAll('.media-selector-item');
    items.forEach(item => {
        const itemId = parseInt(item.dataset.mediaId);
        const isSelected = data.multiple
            ? data.selectedMedia.some(m => m.id === itemId)
            : data.selectedMedia?.id === itemId;
        
        if (isSelected) {
            item.classList.add('selected');
            item.querySelector('.relative').innerHTML += `
                <div class="absolute top-2 right-2 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-white text-xs"></i>
                </div>
            `;
        } else {
            item.classList.remove('selected');
            const checkmark = item.querySelector('.absolute.top-2');
            if (checkmark) checkmark.remove();
        }
    });
}

// 選択数を更新
function updateSelectedCount(modalId) {
    const data = window.mediaSelectorData[modalId];
    const countElement = document.getElementById(`${modalId}-selected-count`);
    if (countElement && data) {
        const count = data.multiple ? data.selectedMedia.length : (data.selectedMedia ? 1 : 0);
        countElement.textContent = count;
    }
}

// 選択を確定
function confirmMediaSelection(modalId, inputId, previewId, multiple) {
    const data = window.mediaSelectorData[modalId];
    if (!data) return;
    
    const selectedMedia = data.selectedMedia;
    if (!selectedMedia || (multiple && selectedMedia.length === 0)) {
        closeMediaSelector(modalId);
        return;
    }
    
    const aspectRatio = data.aspectRatio || 'original';
    
    // アスペクト比に応じたクラスを取得
    const getAspectClasses = () => {
        switch(aspectRatio) {
            case 'ogp': return 'aspect-[1.91/1] object-cover';
            case 'square': return 'aspect-square object-cover';
            case '16:9': return 'aspect-video object-cover';
            case '4:3': return 'aspect-[4/3] object-cover';
            case 'hero': return 'aspect-[21/9] object-cover';
            case 'original': return 'h-auto object-contain';
            default: return 'h-auto object-contain';
        }
    };
    
    const aspectClasses = getAspectClasses();
    
    // 入力フィールドに値を設定
    const input = document.getElementById(inputId);
    if (input) {
        if (multiple) {
            input.value = selectedMedia.map(m => m.id).join(',');
        } else {
            input.value = selectedMedia.id;
        }
        
        // changeイベントを発火
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    // プレビューを更新
    const preview = document.getElementById(previewId);
    if (preview) {
        if (multiple) {
            preview.innerHTML = selectedMedia.map(m => `
                <div class="relative inline-block mr-2 mb-2">
                    <img src="${m.url}" alt="${m.name}" class="w-20 h-20 object-cover rounded">
                    <button type="button" onclick="removeMediaPreview('${inputId}', '${previewId}', ${m.id})" 
                            class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 text-white rounded-full hover:bg-red-700">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            `).join('');
        } else {
            preview.innerHTML = `
                <div class="relative inline-block">
                    <img src="${selectedMedia.url}" alt="${selectedMedia.name}" class="w-64 ${aspectClasses} rounded border border-gray-300 dark:border-gray-600">
                    <button type="button" onclick="removeMediaPreview('${inputId}', '${previewId}')" 
                            class="absolute -top-2 -right-2 w-8 h-8 bg-red-600 text-white rounded-full hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }
    }
    
    closeMediaSelector(modalId);
}

// プレビューから削除
function removeMediaPreview(inputId, previewId, mediaId = null) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (mediaId) {
        // 複数選択の場合
        const ids = input.value.split(',').map(id => parseInt(id)).filter(id => id !== mediaId);
        input.value = ids.join(',');
    } else {
        // 単一選択の場合
        input.value = '';
    }
    
    if (preview) {
        if (mediaId) {
            // 特定のプレビューのみ削除
            const previewItem = preview.querySelector(`[onclick*="${mediaId}"]`)?.closest('.relative');
            if (previewItem) previewItem.remove();
        } else {
            // 全てクリア
            preview.innerHTML = '';
        }
    }
    
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

// ページネーションをレンダリング
function renderPagination(modalId, paginationData) {
    const container = document.getElementById(`${modalId}-pagination`);
    if (!container) return;
    
    if (paginationData.last_page <= 1) {
        container.classList.add('hidden');
        return;
    }
    
    container.classList.remove('hidden');
    // ページネーション実装は省略（必要に応じて追加）
}
</script>
@endpush

@push('styles')
<style>
.media-selector-item.selected .relative {
    @apply ring-2 ring-blue-600;
}
</style>
@endpush
@endonce
