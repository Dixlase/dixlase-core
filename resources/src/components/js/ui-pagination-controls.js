/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * Pagination Controls Component
 * Alpine.js component for pagination controls including per-page selection,
 * sort field selection, and sort order toggling
 */

// Alpine.js component for pagination controls
window.paginationControls = function () {
    return {
        init() {
            // イベントリスナーを設定
            this.$nextTick(() => {
                this.setupEventListeners();
            });
        },

        setupEventListeners() {
            const perPageSelect = document.getElementById('perPage');
            const sortBySelect = document.getElementById('sortBy');
            const sortOrderButton = document.getElementById('sortOrder');

            // 表示件数変更
            if (perPageSelect) {
                perPageSelect.addEventListener('change', (e) => {
                    this.changePerPage(e.target.value);
                });
            }

            // ソートフィールド変更
            if (sortBySelect) {
                sortBySelect.addEventListener('change', (e) => {
                    this.changeSortField(e.target.value);
                });
            }

            // ソート順序変更
            if (sortOrderButton) {
                sortOrderButton.addEventListener('click', () => {
                    const currentOrder = sortOrderButton.getAttribute('data-order');
                    this.toggleSortOrder(currentOrder);
                });
            }
        },

        changePerPage(perPage) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('per_page', perPage);
            currentUrl.searchParams.delete('page'); // ページ番号をリセット

            window.location.href = currentUrl.toString();
        },

        changeSortField(sortField) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', sortField);
            currentUrl.searchParams.delete('page'); // ページ番号をリセット

            window.location.href = currentUrl.toString();
        },

        toggleSortOrder(currentOrder) {
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('order', newOrder);
            currentUrl.searchParams.delete('page'); // ページ番号をリセット

            window.location.href = currentUrl.toString();
        }
    };
};
