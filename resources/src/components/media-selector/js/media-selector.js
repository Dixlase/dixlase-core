/**
 * Media Selector Component
 * Handles media selection modal for forms
 */

// Global data storage
window.mediaSelectorData = window.mediaSelectorData || {};

/**
 * Open media selector modal
 */
window.openMediaSelector = function (modalId, inputId, previewId, multiple = false, aspectRatio = 'original') {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Initialize data
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

    // Load media
    loadMediaForSelector(modalId);
};

/**
 * Close media selector modal
 */
window.closeMediaSelector = function (modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.classList.add('hidden');
    document.body.style.overflow = '';

    // Clear data
    delete window.mediaSelectorData[modalId];
};

/**
 * Load media list
 */
async function loadMediaForSelector(modalId, page = 1) {
    const grid = document.getElementById(`${modalId}-grid`);
    if (!grid) return;

    const modal = document.getElementById(modalId);
    const apiUrl = modal?.dataset.apiUrl || '/admin/media/api';
    const errorMessage = modal?.dataset.errorMessage || 'Failed to load media';

    try {
        const response = await fetch(`${apiUrl}?page=${page}&per_page=20`);
        const data = await response.json();

        if (data.success) {
            renderMediaGrid(modalId, data.media.data);
            renderPagination(modalId, data.media);
        }
    } catch (error) {
        console.error('[Media Selector] Failed to load media:', error);
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                <p class="text-red-600 dark:text-red-400">${errorMessage}</p>
            </div>
        `;
    }
}

/**
 * Render media grid
 */
function renderMediaGrid(modalId, mediaItems) {
    const grid = document.getElementById(`${modalId}-grid`);
    const modal = document.getElementById(modalId);
    const noMediaMessage = modal?.dataset.noMediaMessage || 'No media found';

    if (!grid || !mediaItems || mediaItems.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i class="fas fa-images text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">${noMediaMessage}</p>
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

/**
 * Toggle media selection
 */
window.toggleMediaSelection = function (modalId, media) {
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

    // Update selected count
    updateSelectedCount(modalId);

    // Re-render grid
    const grid = document.getElementById(`${modalId}-grid`);
    const items = grid.querySelectorAll('.media-selector-item');
    items.forEach(item => {
        const itemId = parseInt(item.dataset.mediaId);
        const isSelected = data.multiple
            ? data.selectedMedia.some(m => m.id === itemId)
            : data.selectedMedia?.id === itemId;

        if (isSelected) {
            item.classList.add('selected');
            const checkmark = item.querySelector('.absolute.top-2');
            if (!checkmark) {
                item.querySelector('.relative').insertAdjacentHTML('beforeend', `
                    <div class="absolute top-2 right-2 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-white text-xs"></i>
                    </div>
                `);
            }
        } else {
            item.classList.remove('selected');
            const checkmark = item.querySelector('.absolute.top-2');
            if (checkmark) checkmark.remove();
        }
    });
};

/**
 * Update selected count
 */
function updateSelectedCount(modalId) {
    const data = window.mediaSelectorData[modalId];
    const countElement = document.getElementById(`${modalId}-selected-count`);
    if (countElement && data) {
        const count = data.multiple ? data.selectedMedia.length : (data.selectedMedia ? 1 : 0);
        countElement.textContent = count;
    }
}

/**
 * Confirm media selection
 */
window.confirmMediaSelection = function (modalId, inputId, previewId, multiple) {
    const data = window.mediaSelectorData[modalId];
    if (!data) return;

    const selectedMedia = data.selectedMedia;
    if (!selectedMedia || (multiple && selectedMedia.length === 0)) {
        closeMediaSelector(modalId);
        return;
    }

    const aspectRatio = data.aspectRatio || 'original';

    // Get aspect ratio classes
    const getAspectClasses = () => {
        switch (aspectRatio) {
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

    // Set input value
    const input = document.getElementById(inputId);
    if (input) {
        if (multiple) {
            input.value = selectedMedia.map(m => m.id).join(',');
        } else {
            input.value = selectedMedia.id;
        }

        // Trigger change event
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Update preview
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
};

/**
 * Remove media preview
 */
window.removeMediaPreview = function (inputId, previewId, mediaId = null) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (mediaId) {
        // Multiple selection
        const ids = input.value.split(',').map(id => parseInt(id)).filter(id => id !== mediaId);
        input.value = ids.join(',');
    } else {
        // Single selection
        input.value = '';
    }

    if (preview) {
        if (mediaId) {
            // Remove specific preview
            const previewItem = preview.querySelector(`[onclick*="${mediaId}"]`)?.closest('.relative');
            if (previewItem) previewItem.remove();
        } else {
            // Clear all
            preview.innerHTML = '';
        }
    }

    input.dispatchEvent(new Event('change', { bubbles: true }));
};

/**
 * Render pagination
 */
function renderPagination(modalId, paginationData) {
    const container = document.getElementById(`${modalId}-pagination`);
    if (!container) return;

    if (paginationData.last_page <= 1) {
        container.classList.add('hidden');
        return;
    }

    container.classList.remove('hidden');
    // Pagination implementation can be added here if needed
}

console.log('[Media Selector] Script loaded');
