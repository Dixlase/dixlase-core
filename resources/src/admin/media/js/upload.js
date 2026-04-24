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
 * Media Upload - Alpine.js コンポーネント
 * 複数ファイルの選択・ドラッグ&ドロップによる自動アップロード
 */
import Alpine from 'alpinejs';

Alpine.data('mediaUploader', (uploadUrl, csrfToken, redirectUrl) => ({
    isDragging: false,
    queue: [],
    isUploading: false,
    redirectUrl: redirectUrl,

    get completedCount() {
        return this.queue.filter(item => item.status === 'success' || item.status === 'error').length;
    },

    get successCount() {
        return this.queue.filter(item => item.status === 'success').length;
    },

    get failCount() {
        return this.queue.filter(item => item.status === 'error').length;
    },

    get allDone() {
        return this.queue.length > 0 && this.completedCount === this.queue.length;
    },

    get successMessage() {
        return this.successCount + ' file(s) uploaded successfully.';
    },

    /**
     * ファイルサイズを読みやすい形式に変換
     */
    formatFileSize(bytes) {
        if (bytes === 0) {
            return '0 B';
        }
        const units = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(i > 0 ? 1 : 0) + ' ' + units[i];
    },

    /**
     * ファイル選択ハンドラ
     */
    handleFileSelect(event) {
        const files = event.target.files;
        if (files.length > 0) {
            this.addFilesToQueue(files);
        }
    },

    /**
     * ドラッグ&ドロップハンドラ
     */
    handleDrop(event) {
        this.isDragging = false;
        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.addFilesToQueue(files);
        }
    },

    /**
     * キューにファイルを追加して自動アップロード開始
     */
    addFilesToQueue(files) {
        for (const file of files) {
            this.queue.push({
                file: file,
                name: file.name,
                sizeLabel: this.formatFileSize(file.size),
                status: 'pending',
                error: null,
            });
        }
        this.processQueue();
    },

    /**
     * キュー内のファイルを順次アップロード
     */
    async processQueue() {
        if (this.isUploading) {
            return;
        }
        this.isUploading = true;

        for (const item of this.queue) {
            if (item.status !== 'pending') {
                continue;
            }

            item.status = 'uploading';

            try {
                const formData = new FormData();
                formData.append('files[]', item.file);

                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const data = await response.json().catch(() => null);
                    item.status = 'error';
                    if (data && data.errors) {
                        const firstError = Object.values(data.errors).flat()[0];
                        item.error = firstError || 'Upload failed';
                    } else if (data && data.error) {
                        item.error = data.error;
                    } else {
                        item.error = 'Upload failed (' + response.status + ')';
                    }
                    continue;
                }

                const data = await response.json();
                if (data.success) {
                    item.status = 'success';
                } else if (data.results && data.results.length > 0) {
                    const result = data.results[0];
                    if (result.success) {
                        item.status = 'success';
                    } else {
                        item.status = 'error';
                        item.error = result.error || 'Upload failed';
                    }
                } else {
                    item.status = 'error';
                    item.error = data.error || 'Upload failed';
                }
            } catch (error) {
                item.status = 'error';
                item.error = error.message || 'Network error';
            }
        }

        this.isUploading = false;
    },
}));
