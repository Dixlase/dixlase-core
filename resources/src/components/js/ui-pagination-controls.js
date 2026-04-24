/**
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
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
