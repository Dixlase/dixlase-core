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
 * Upload inside the media selector modal (x-media.selector).
 *
 * Bound to the modal's root element, so a file dropped anywhere on the
 * modal is taken. Each file is posted on its own to admin.media.store, so
 * one rejected file does not stop the others and its error is the server's
 * own message. When the queue is done, selector.js reloads page 1 and
 * selects what was uploaded (window.mediaSelectorUploaded).
 *
 * Configuration comes from data attributes on the element, not from an
 * x-data argument, so the directive stays a bare name (strict-mode ready).
 */

import Alpine from 'alpinejs';

Alpine.data('mediaSelectorUpload', () => ({
    panelOpen: false,
    dragging: false,
    busy: false,
    items: [],
    nextKey: 0,
    requests: [],
    // Bumped by reset(), so a queue still running from the last opening
    // stops instead of running alongside the next one
    generation: 0,

    init() {
        // Each opening of the modal starts with an empty panel
        this.$el.addEventListener('media-selector-open', () => this.reset());
    },

    reset() {
        this.generation++;
        this.requests.forEach(xhr => xhr.abort());
        this.requests = [];
        this.items = [];
        this.busy = false;
        this.dragging = false;
        this.panelOpen = false;
    },

    togglePanel() {
        this.panelOpen = !this.panelOpen;
    },

    closePanel() {
        this.panelOpen = false;
    },

    chooseFiles() {
        this.$refs.uploadInput?.click();
    },

    hasFiles(event) {
        return Array.from(event.dataTransfer?.types || []).includes('Files');
    },

    dragOver(event) {
        if (!this.hasFiles(event)) return;
        event.preventDefault();
        this.panelOpen = true;
        this.dragging = true;
    },

    dragLeave(event) {
        // Leaving for a child element is not leaving the modal
        if (event.relatedTarget && this.$el.contains(event.relatedTarget)) return;
        this.dragging = false;
    },

    drop(event) {
        if (!this.hasFiles(event)) return;
        event.preventDefault();
        this.dragging = false;
        this.addFiles(event.dataTransfer.files);
    },

    picked(event) {
        this.addFiles(event.target.files);
        // Let the same file be picked again after a failure
        event.target.value = '';
    },

    addFiles(fileList) {
        Array.from(fileList || []).forEach(file => {
            this.items.push({
                key: ++this.nextKey,
                file: file,
                name: file.name,
                status: 'pending',
                progress: 0,
                error: '',
            });
        });
        this.processQueue();
    },

    async processQueue() {
        if (this.busy) return;
        this.busy = true;
        const generation = this.generation;

        const uploadedIds = [];
        for (const item of this.items) {
            if (item.status !== 'pending') continue;
            const id = await this.upload(item);
            // reset() ran while this file was in flight: the modal was
            // closed or opened again, so the rest of this queue is gone
            if (generation !== this.generation) return;
            if (id !== null) uploadedIds.push(id);
        }

        this.busy = false;

        if (uploadedIds.length > 0) {
            await window.mediaSelectorUploaded(this.$el.id, uploadedIds);
        }

        // Files added while the last request was finishing
        if (this.items.some(item => item.status === 'pending')) this.processQueue();
    },

    /**
     * Post one file. Resolves to the new media id, or null on failure.
     * XMLHttpRequest rather than fetch, because fetch reports no upload
     * progress.
     */
    upload(item) {
        return new Promise(resolve => {
            const dataset = this.$el.dataset;
            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            // The reactive proxy around the File is not a Blob to FormData
            formData.append('file', Alpine.raw(item.file));

            item.status = 'uploading';
            this.requests.push(xhr);
            const done = () => {
                this.requests = this.requests.filter(request => request !== xhr);
            };

            xhr.upload.onprogress = event => {
                if (event.lengthComputable) item.progress = Math.round((event.loaded / event.total) * 100);
            };

            xhr.onload = () => {
                done();
                const data = this.parseJson(xhr.responseText);
                const result = data?.results?.[0];

                if (xhr.status >= 200 && xhr.status < 300 && result?.success) {
                    item.status = 'success';
                    item.progress = 100;
                    resolve(result.id);
                    return;
                }

                item.status = 'error';
                item.error = this.errorFrom(data, result, xhr.status);
                resolve(null);
            };

            xhr.onerror = () => {
                done();
                item.status = 'error';
                item.error = dataset.uploadNetworkError || 'Network error';
                resolve(null);
            };

            xhr.onabort = () => {
                done();
                resolve(null);
            };

            xhr.open('POST', dataset.uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });
    },

    parseJson(text) {
        try {
            return JSON.parse(text);
        } catch (error) {
            return null;
        }
    },

    /**
     * The server's own reason: a validation message (422), the action's
     * message for a refused file, or the abort message (403).
     *
     * Admin JSON errors come in an envelope,
     * {"error": {"code", "message", "details": {field: [messages]}}};
     * the store action's per-file refusal is a plain string in results[].
     */
    errorFrom(data, result, status) {
        const envelope = data && typeof data.error === 'object' ? data.error : null;
        const details = envelope?.details || data?.errors;
        const validation = details ? Object.values(details).flat()[0] : '';
        const plain = typeof data?.error === 'string' ? data.error : '';
        const message = validation || result?.error || plain || envelope?.message || data?.message || '';
        if (message) return String(message);
        return (this.$el.dataset.uploadFailed || 'Upload failed') + ' (' + status + ')';
    },

    hasItems() {
        return this.items.length > 0;
    },

    isUploading(item) {
        return item.status === 'pending' || item.status === 'uploading';
    },

    isSuccess(item) {
        return item.status === 'success';
    },

    isError(item) {
        return item.status === 'error';
    },

    progressWidth(item) {
        return 'width: ' + item.progress + '%';
    },

    dropZoneClass() {
        return this.dragging
            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
            : 'border-gray-300 dark:border-gray-600';
    },

    clearFinished() {
        this.items = this.items.filter(item => this.isUploading(item));
    },

    hasFinished() {
        return this.items.some(item => !this.isUploading(item));
    },
}));
