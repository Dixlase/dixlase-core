/*
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Media Selector Component
 * Handles media selection modal for forms
 */

// Global data storage
window.mediaSelectorData = window.mediaSelectorData || {};

/**
 * Escape a value for interpolation into a double-quoted HTML attribute.
 *
 * Media names come from the uploaded file name. UploadMediaAction stores
 * `$file->getClientOriginalName()` unchanged, and that only applies
 * basename() -- path separators go, HTML metacharacters stay. So a file
 * uploaded as
 *
 *     "><img src=x onerror=alert(1)>.png
 *
 * kept those characters, and interpolating it into `alt="${media.name}"`
 * inside an innerHTML template closed the attribute and injected an element.
 * It fired in the browser of whichever admin opened the media picker, not the
 * uploader's -- a stored XSS across the admin trust boundary.
 *
 * URLs are escaped for the same reason: they are derived from the stored name.
 */
function escapeAttribute(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/**
 * How long the search box waits after the last keystroke before it asks
 * the server.
 */
const SEARCH_DEBOUNCE_MS = 300;

/**
 * How many characters at the end of a file name stay visible when the name
 * is too long for its tile. Variants of one image usually differ only there
 * (`card-ja.png` / `card-en.png`), so the name is cut in the middle, never at
 * the end.
 */
const NAME_TAIL_LENGTH = 10;

/**
 * Format a byte count the way Media::formattedFileSize does.
 */
function formatFileSize(bytes) {
    if (bytes === null || bytes === undefined) return '';
    const value = Number(bytes);
    if (value === 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const i = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1);
    const scaled = value / Math.pow(1024, i);
    return `${i > 0 ? Math.round(scaled * 10) / 10 : Math.round(scaled)} ${units[i]}`;
}

/**
 * Caption under a grid tile: the file name, cut in the middle when it does
 * not fit, and a muted line with the dimensions and the file size.
 *
 * Every value goes through escapeAttribute(): the name is the uploaded file
 * name verbatim (see the note on escapeAttribute), and escaping all five
 * characters makes it safe in a text node as well as in an attribute.
 */
function renderMediaCaption(media) {
    // Split by code point, so an emoji or other astral character is never cut
    // in half. whitespace-pre keeps a space at the cut from collapsing.
    const chars = Array.from(String(media.name ?? ''));
    const head = 'min-w-0 overflow-hidden text-ellipsis whitespace-pre';
    const nameHtml = chars.length > NAME_TAIL_LENGTH + 2
        ? `<span class="${head}">${escapeAttribute(chars.slice(0, -NAME_TAIL_LENGTH).join(''))}</span>`
            + `<span class="shrink-0 whitespace-pre">${escapeAttribute(chars.slice(-NAME_TAIL_LENGTH).join(''))}</span>`
        : `<span class="${head}">${escapeAttribute(chars.join(''))}</span>`;

    const meta = [
        media.width && media.height ? `${media.width}×${media.height}` : '',
        formatFileSize(media.file_size),
    ].filter(Boolean).join(' · ');

    return `
        <div class="mt-1.5 px-0.5 text-xs leading-tight">
            <div class="flex text-gray-700 dark:text-gray-200">${nameHtml}</div>
            ${meta ? `<div class="mt-0.5 truncate text-gray-500 dark:text-gray-400">${escapeAttribute(meta)}</div>` : ''}
        </div>
    `;
}

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
        currentPage: 1,
        search: '',
        type: '',
        requestId: 0,
        searchTimer: null
    };

    bindSelectorFilters(modalId);

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    // Load media
    loadMediaForSelector(modalId);
};

/**
 * Wire the search box and the type filter, and clear what the last
 * opening left in them. Typing waits for a pause before it asks the
 * server; either change starts again from page 1.
 */
function bindSelectorFilters(modalId) {
    const search = document.getElementById(`${modalId}-search`);
    const typeFilter = document.getElementById(`${modalId}-type-filter`);

    if (search) {
        search.value = '';
        search.oninput = function () {
            const state = window.mediaSelectorData[modalId];
            if (!state) return;
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(function () {
                state.search = search.value.trim();
                loadMediaForSelector(modalId, 1);
            }, SEARCH_DEBOUNCE_MS);
        };
    }

    if (typeFilter) {
        typeFilter.value = '';
        typeFilter.onchange = function () {
            const state = window.mediaSelectorData[modalId];
            if (!state) return;
            state.type = typeFilter.value;
            loadMediaForSelector(modalId, 1);
        };
    }
}

/**
 * Close media selector modal
 */
window.closeMediaSelector = function (modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.classList.add('hidden');
    document.body.style.overflow = '';

    // Clear data
    clearTimeout(window.mediaSelectorData[modalId]?.searchTimer);
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

    const state = window.mediaSelectorData[modalId];
    if (state) state.currentPage = page;
    const requestId = state ? ++state.requestId : 0;

    const params = new URLSearchParams({ page: String(page), per_page: '20' });
    if (state?.search) params.set('search', state.search);
    if (state?.type) params.set('type', state.type);

    try {
        const response = await fetch(`${apiUrl}?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            // Drop the response when the modal was closed, or another page,
            // search or type was asked for, while the request was in flight.
            if (window.mediaSelectorData[modalId]?.requestId !== requestId) return;
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
 * The badge on a selected tile. In multiple mode it is the item's place in
 * the selection (1, 2, 3 …), because callers such as the content editor
 * use the selection in that order; in single mode it is a check mark.
 */
function selectionMarker(data, mediaId) {
    const base = 'media-selector-marker absolute top-2 right-2 h-6 bg-blue-600 rounded-full flex items-center justify-center text-white';

    if (data.multiple) {
        const index = data.selectedMedia.findIndex(m => m.id === mediaId);
        if (index === -1) return '';
        return `<div class="${base} min-w-6 px-1.5 text-xs font-semibold">${index + 1}</div>`;
    }

    if (data.selectedMedia?.id !== mediaId) return '';
    return `<div class="${base} w-6"><i class="fas fa-check text-xs" aria-hidden="true"></i></div>`;
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

        // The full name, and the alt text when there is one, for the tooltip
        const tooltip = [media.name, media.alt_text].filter(Boolean).join('\n');

        // object-contain, not object-cover: a picker is for telling files
        // apart, and a centre crop removes exactly the edges where variants
        // of one image differ.
        return `
            <div class="media-selector-item cursor-pointer ${isSelected ? 'selected' : ''}"
                 data-media-id="${media.id}"
                 data-media-json="${encodeURIComponent(JSON.stringify(media))}"
                 title="${escapeAttribute(tooltip)}">
                <div class="relative aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden hover:ring-2 hover:ring-blue-500 transition-all">
                    ${isImage
                ? `<img src="${escapeAttribute(media.url)}" alt="${escapeAttribute(media.name)}" class="w-full h-full object-contain">`
                : `<div class="flex items-center justify-center w-full h-full">
                             <i class="fas fa-file-alt text-4xl text-gray-400"></i>
                           </div>`
            }
                    <div class="absolute inset-0 bg-black/0 hover:bg-black/10 transition-all"></div>
                    ${selectionMarker(data, media.id)}
                </div>
                ${renderMediaCaption(media)}
            </div>
        `;
    }).join('');

    // Attach click handler via event delegation (CSP-safe)
    grid.onclick = function (e) {
        const item = e.target.closest('.media-selector-item');
        if (!item) return;
        const mediaJson = item.dataset.mediaJson;
        if (mediaJson) {
            const media = JSON.parse(decodeURIComponent(mediaJson));
            window.toggleMediaSelection(modalId, media);
        }
    };
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

        item.classList.toggle('selected', isSelected);

        // Redrawn on every change: deselecting one item renumbers the rest
        item.querySelector('.media-selector-marker')?.remove();
        if (isSelected) {
            item.querySelector('.relative').insertAdjacentHTML('beforeend', selectionMarker(data, itemId));
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
    }

    // Update preview
    const preview = document.getElementById(previewId);
    if (preview) {
        if (multiple) {
            preview.innerHTML = selectedMedia.map(m => `
                <div class="relative inline-block mr-2 mb-2">
                    <img src="${escapeAttribute(m.url)}" alt="${escapeAttribute(m.name)}" class="w-20 h-20 object-cover rounded">
                    <button type="button" class="media-remove-btn absolute -top-2 -right-2 w-6 h-6 bg-red-600 text-white rounded-full hover:bg-red-700"
                            data-input-id="${inputId}" data-preview-id="${previewId}" data-media-id="${m.id}">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            `).join('');
        } else {
            preview.innerHTML = `
                <div class="relative inline-block">
                    <img src="${escapeAttribute(selectedMedia.url)}" alt="${escapeAttribute(selectedMedia.name)}" class="w-64 ${aspectClasses} rounded border border-gray-300 dark:border-gray-600">
                    <button type="button" class="media-remove-btn absolute -top-2 -right-2 w-8 h-8 bg-red-600 text-white rounded-full hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                            data-input-id="${inputId}" data-preview-id="${previewId}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }
    }

    // Dispatch custom event with media details (for theme preview etc.)
    document.dispatchEvent(new CustomEvent('media-selected', {
        detail: {
            inputId: inputId,
            previewId: previewId,
            media: multiple ? selectedMedia : selectedMedia,
            url: multiple ? selectedMedia.map(m => m.url) : selectedMedia.url,
        }
    }));

    // Trigger change event on input (after preview is updated)
    if (input) {
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    closeMediaSelector(modalId);
};

/**
 * Remove media preview (delegated event handler)
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
            const previewItem = preview.querySelector(`[data-media-id="${mediaId}"]`)?.closest('.relative');
            if (previewItem) previewItem.remove();
        } else {
            // Clear all
            preview.innerHTML = '';
        }
    }

    input.dispatchEvent(new Event('change', { bubbles: true }));
};

// Delegated click handler for remove buttons (CSP-safe)
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.media-remove-btn');
    if (!btn) return;
    const inputId = btn.dataset.inputId;
    const previewId = btn.dataset.previewId;
    const mediaId = btn.dataset.mediaId ? parseInt(btn.dataset.mediaId) : null;
    window.removeMediaPreview(inputId, previewId, mediaId);
});

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

    const modal = document.getElementById(modalId);
    const current = Number(paginationData.current_page) || 1;
    const last = Number(paginationData.last_page) || 1;
    const previousLabel = escapeAttribute(modal?.dataset.previousLabel || 'Previous');
    const nextLabel = escapeAttribute(modal?.dataset.nextLabel || 'Next');
    const pageLabel = escapeAttribute((modal?.dataset.pageLabel || 'Page :current of :last')
        .replace(':current', current)
        .replace(':last', last));
    // Arrow-only buttons keep the navigation small enough to share the footer
    // with the selected count and the Cancel / Select buttons; the label
    // stays available as the accessible name and the tooltip.
    const buttonClass = 'inline-flex items-center justify-center w-9 h-9 text-sm rounded-lg border border-gray-300 dark:border-gray-600 '
        + 'text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 '
        + 'disabled:opacity-50 disabled:cursor-not-allowed';

    container.innerHTML = `
        <nav class="flex items-center justify-center gap-3" aria-label="${pageLabel}">
            <button type="button" class="${buttonClass}" data-page="${current - 1}"
                    aria-label="${previousLabel}" title="${previousLabel}" ${current <= 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
            <span class="text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap" aria-live="polite">${pageLabel}</span>
            <button type="button" class="${buttonClass}" data-page="${current + 1}"
                    aria-label="${nextLabel}" title="${nextLabel}" ${current >= last ? 'disabled' : ''}>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
        </nav>
    `;

    // Event delegation, as for the grid (CSP-safe). The selection lives in
    // mediaSelectorData, so it survives moving between pages. The grid's own
    // scroll container goes back to the top; scrollIntoView() would also
    // scroll the page behind the modal.
    container.onclick = function (e) {
        const button = e.target.closest('button[data-page]');
        if (!button || button.disabled) return;
        loadMediaForSelector(modalId, Number(button.dataset.page))
            .then(() => {
                const scroller = document.getElementById(`${modalId}-scroll`);
                if (scroller) scroller.scrollTop = 0;
            });
    };
}

