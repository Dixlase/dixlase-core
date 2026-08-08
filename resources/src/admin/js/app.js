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
import '../media/js/preview';
import '../media/js/upload';
import '../profile/js/appearance-mode';
import '../../components/mail-server/js/settings-admin';
import '../security/js/safe-mode-banner';
import '../security/js/integrity';
import './sidebar-edit';
import './split-pane-editor';
import './front-page-editor';

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
 * @api プラグイン/テーマから window.Dixlase.newTabPreview() として使用可能
 *
 * 別タブプレビュー: 親フォームのデータを収集し、指定URLにPOSTして新しいタブで開く。
 * x-content-editor.new-tab-preview コンポーネントから呼び出される。
 *
 * @param {string} url - プレビュー用POSTエンドポイントのURL
 * @param {HTMLElement} el - ボタン要素（親フォームの特定に使用）
 */
window.Dixlase.newTabPreview = function(url, el) {
    if (!url) return;

    var parentForm = el.closest('form');
    var previewForm = document.createElement('form');
    previewForm.method = 'POST';
    previewForm.action = url;
    previewForm.target = '_blank';
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
// greps for this string in the built public/assets/build/js/admin.js to
// confirm that a core update actually replaces the built front-end
// assets (not just the source tree and the VERSION file). Safe to keep
// past dryrun-10; harmless in production.
window.Dixlase.buildMarker = 'DIXLASE_BUILD_MARKER:dryrun-26';
