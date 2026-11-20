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

import '../scss/style.scss';
import './layout';
//import './layout-vanilla';
import './login-flow';
import '../media/js/index';
import '../media/js/tags';
import '../media/js/preview';
import '../media/js/upload';
import '../profile/js/appearance-mode';
import '../../components/mail-server/js/settings-admin';
import '../security/js/safe-mode-banner';
import '../security/js/integrity';
import './sidebar-edit';
import './content-editor';
import './unsaved-changes-guard';

// プラグイン/テーマ向けランタイム API（import 不要で使用可能）
import { previewMixin, DEVICE_PRESETS } from './mixins/preview-mixin';
import { splitPaneMixin, STORAGE_KEY_SPLIT_RATIO, MIN_PANE_WIDTH, HORIZONTAL_MIN_WIDTH } from './mixins/split-pane-mixin';
import { mergeMixins } from './mixins/merge-mixin';

window.Dixlase = window.Dixlase || {};
window.Dixlase.mixins = {
    previewMixin,
    splitPaneMixin,
    mergeMixins,
    constants: { DEVICE_PRESETS, STORAGE_KEY_SPLIT_RATIO, MIN_PANE_WIDTH, HORIZONTAL_MIN_WIDTH },
};

/**
 * The name of the one tab a preview URL opens in (#1079). Previewing the
 * same record again replaces that tab instead of opening another.
 *
 * @param {string} url
 * @returns {string}
 */
function previewTabName(url) {
    return 'dixlase-preview:' + url;
}

/**
 * @api Available to plugins and themes as window.Dixlase.openPreviewTab()
 *
 * Open a saved preview (a GET URL) in its own named tab, the one
 * newTabPreview() uses for the same URL. The tab is opened by script,
 * so the admin bar's "Back to the editor" can close it (#1079).
 *
 * @param {string} url - The preview page's URL
 */
window.Dixlase.openPreviewTab = function(url) {
    if (!url) return;

    var tab = window.open(url, previewTabName(url));
    if (tab) tab.focus();
};

/**
 * @api プラグイン/テーマから window.Dixlase.newTabPreview() として使用可能
 *
 * 別タブプレビュー: 親フォームのデータを収集し、指定URLにPOSTして新しいタブで開く。
 * x-content-editor.new-tab-preview コンポーネントから呼び出される。
 *
 * The tab is named after the URL and opened by script first, so a second
 * preview of the same record replaces it, and the admin bar's "Back to
 * the editor" can close it (#1079).
 *
 * @param {string} url - プレビュー用POSTエンドポイントのURL
 * @param {HTMLElement} el - ボタン要素（親フォームの特定に使用）
 */
window.Dixlase.newTabPreview = function(url, el) {
    if (!url) return;

    // An empty URL opens a blank tab, or returns the named tab as it is
    // without reloading it; the form below then navigates it. A browser
    // that blocks the pop-up gets the name anyway, and the form opens a
    // new tab under it.
    var tabName = previewTabName(url);
    var tab = window.open('', tabName);
    if (tab) tab.focus();

    var parentForm = el.closest('form');
    var previewForm = document.createElement('form');
    previewForm.method = 'POST';
    previewForm.action = url;
    previewForm.target = tabName;
    previewForm.style.display = 'none';

    if (parentForm) {
        var data = new FormData(parentForm);
        for (var pair of data.entries()) {
            // _method (PUT/PATCH) は除外（プレビューはPOST）
            if (pair[0] === '_method') continue;
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = pair[0];
            input.value = pair[1];
            previewForm.appendChild(input);
        }
    }

    document.body.appendChild(previewForm);
    previewForm.submit();
    document.body.removeChild(previewForm);
};

// Sentinel marker written by the dryrun-10 release. Sandbox verification
// greps for this string in the built admin bundle to confirm that a core
// update actually replaces the built front-end assets (not just the source
// tree and the VERSION file). Since #300 the bundle filename is
// content-hashed (public/assets/build/js/admin-<hash>.js), so resolve it
// through public/assets/build/manifest.json instead of a fixed path. Safe
// to keep past dryrun-10; harmless in production.
window.Dixlase.buildMarker = 'DIXLASE_BUILD_MARKER:0.3.35';
