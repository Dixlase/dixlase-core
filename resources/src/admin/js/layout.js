/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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

/*
 * Sidebar display modes (#819).
 *
 * Above the phone breakpoint the sidebar has two shapes: a narrow icon rail
 * and the full-width menu.
 *
 * `sidebarRail` is the member's choice for the desktop band, and it is the
 * only part that is remembered. `sidebarOverlay` is the tablet band's
 * transient reveal: there the full menu comes out over the content until it
 * is dismissed, the way the phone drawer does, so storing it would mean a
 * later visit started with the menu covering the page.
 *
 * The two collapse into one fact -- whether the rail is the shape on screen
 * right now -- which is published as a class on <html>. The stylesheet acts
 * on that class, so the widths do not wait for Alpine to boot. The same
 * computation is inlined in the head of layouts/admin.blade.php to get the
 * first paint right; the two must stay in step.
 */
const SIDEBAR_MODE_KEY = 'adminSidebarMode';
const SIDEBAR_LEGACY_KEY = 'sidebarCollapsed';
const SIDEBAR_RAIL_CLASS = 'dls-sidebar-rail-active';
const SIDEBAR_TABLET_MIN = 640;
const SIDEBAR_DESKTOP_MIN = 1280;

/**
 * Which band the viewport is in. The rail only exists in 'tablet' and
 * 'desktop'; 'mobile' is the drawer's band and is left untouched.
 *
 * @param {number} width
 * @returns {'mobile'|'tablet'|'desktop'}
 */
function sidebarBandFor(width) {
    if (width >= SIDEBAR_DESKTOP_MIN) {
        return 'desktop';
    }

    return width >= SIDEBAR_TABLET_MIN ? 'tablet' : 'mobile';
}

/**
 * Read the stored desktop preference, falling back to the boolean key the
 * two-state sidebar used. A blocked or cleared localStorage throws rather
 * than returning null, so every access is guarded.
 *
 * @returns {boolean} true when the rail is the stored preference
 */
function readStoredRail() {
    try {
        const stored = localStorage.getItem(SIDEBAR_MODE_KEY);
        if (stored !== null) {
            return stored === 'rail';
        }

        return localStorage.getItem(SIDEBAR_LEGACY_KEY) === 'true';
    } catch (e) {
        return false;
    }
}

/**
 * @param {boolean} rail
 */
function writeStoredRail(rail) {
    try {
        localStorage.setItem(SIDEBAR_MODE_KEY, rail ? 'rail' : 'expanded');
        localStorage.removeItem(SIDEBAR_LEGACY_KEY);
    } catch (e) {
        // A viewer with site data blocked keeps the mode for this page only.
    }
}

/**
 * Admin Layout Alpine.js Component
 * 
 * 管理画面レイアウトの状態管理（サイドバー開閉、ユーザーメニュー等）
 */
window.adminLayout = function() {
    return {
        openSidebar: false,
        openUserMenu: false,
        sidebarRail: readStoredRail(),
        sidebarOverlay: false,
        sidebarBand: sidebarBandFor(window.innerWidth),
        sidebarReady: false,

        // 右サイドバー（ページエディタ等で使用）
        rightSidebarActive: false,
        rightSidebarCollapsed: false,
        rightSidebarReady: false,

        init() {
            // モバイル（< lg ブレークポイント）では右サイドバーをデフォルトで折りたたむ
            if (window.innerWidth < 1024) {
                this.rightSidebarCollapsed = true;
            }

            this.applySidebarMode();

            // ウィンドウリサイズ時にモバイル幅になったら右サイドバーを折りたたむ
            window.addEventListener('resize', () => {
                if (window.innerWidth < 1024 && !this.rightSidebarCollapsed) {
                    this.rightSidebarCollapsed = true;
                }

                this.syncSidebarBand();
            });

            // Menu editing needs the labels, the eye toggles and the drag
            // handles, none of which fit a 96px rail, so sidebarEditor asks
            // for the full menu before it starts.
            window.addEventListener('dls-sidebar-expand', () => {
                this.expandSidebar();
            });

            // サイドバーの準備完了フラグを次のティックで設定
            this.$nextTick(() => {
                this.sidebarReady = true;
                this.rightSidebarReady = true;
            });

            // 右サイドバー強制オープンイベント（新規作成画面等で使用）
            window.addEventListener('right-sidebar-force-open', () => {
                this.$nextTick(() => {
                    this.rightSidebarCollapsed = false;
                });
            });

            // Only the desktop band's choice is remembered; see the note above.
            this.$watch('sidebarRail', value => {
                writeStoredRail(value);
                this.applySidebarMode();
            });

            this.$watch('sidebarOverlay', () => {
                this.applySidebarMode();
            });

        },

        /**
         * 右サイドバーの開閉をトグルする
         */
        toggleRightSidebar() {
            this.rightSidebarCollapsed = !this.rightSidebarCollapsed;
        },

        /**
         * Whether the icon rail is the shape currently on screen.
         *
         * @returns {boolean}
         */
        sidebarRailActive() {
            if (this.sidebarBand === 'tablet') {
                return ! this.sidebarOverlay;
            }

            return this.sidebarBand === 'desktop' ? this.sidebarRail : false;
        },

        /**
         * Whether the full menu is currently covering the content, which is
         * the only state that needs a backdrop to dismiss it.
         *
         * @returns {boolean}
         */
        sidebarOverlayOpen() {
            return this.sidebarBand === 'tablet' && this.sidebarOverlay;
        },

        /**
         * Publish the computed shape for the stylesheet.
         */
        applySidebarMode() {
            document.documentElement.classList.toggle(
                SIDEBAR_RAIL_CLASS,
                this.sidebarRailActive()
            );
        },

        /**
         * Follow the viewport across a band boundary. A tablet reveal is
         * dropped on the way out, so a window dragged wider does not land on
         * the desktop with the menu stuck over the page.
         */
        syncSidebarBand() {
            const band = sidebarBandFor(window.innerWidth);
            if (band === this.sidebarBand) {
                return;
            }

            this.sidebarBand = band;
            if (band !== 'tablet') {
                this.sidebarOverlay = false;
            }

            this.applySidebarMode();
        },

        /**
         * The tab button. On a tablet it reveals the labels over the content
         * without remembering the choice; on a desktop it switches the
         * remembered shape and the content moves with it.
         */
        toggleSidebar() {
            if (this.sidebarBand === 'tablet') {
                this.sidebarOverlay = ! this.sidebarOverlay;

                return;
            }

            this.sidebarRail = ! this.sidebarRail;
        },

        /**
         * Dismiss the tablet reveal (the backdrop and Escape both land here).
         */
        closeSidebarOverlay() {
            this.sidebarOverlay = false;
        },

        /**
         * Show the full menu whichever band we are in.
         */
        expandSidebar() {
            if (this.sidebarBand === 'tablet') {
                this.sidebarOverlay = true;

                return;
            }

            this.sidebarRail = false;
        }
    };
};
