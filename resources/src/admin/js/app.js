/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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
//import './layout';
import './layout-vanilla';
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

// プラグイン/テーマ向けランタイム API（import 不要で使用可能）
import { previewMixin, DEVICE_PRESETS } from './mixins/preview-mixin';
import { splitPaneMixin, STORAGE_KEY_SPLIT_RATIO, MIN_PANE_WIDTH, HORIZONTAL_MIN_WIDTH } from './mixins/split-pane-mixin';

window.Dixlase = window.Dixlase || {};
window.Dixlase.mixins = {
    previewMixin,
    splitPaneMixin,
    constants: { DEVICE_PRESETS, STORAGE_KEY_SPLIT_RATIO, MIN_PANE_WIDTH, HORIZONTAL_MIN_WIDTH },
};
