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

import Alpine from 'alpinejs';
import { previewMixin } from './mixins/preview-mixin';
import { mergeMixins } from './mixins/merge-mixin';

/**
 * The read-only theme preview on the front page index.
 *
 * Built from previewMixin, so the device switch, scaling and the iframe
 * sync are the same as in the content editor. The frame URL is read from
 * the root element's data-preview-frame-url. The page shows no loading
 * overlay, so the mixin's message listener is not started; only the
 * container width is observed for scaling.
 */
Alpine.data('frontPagePreview', function () {
    return mergeMixins(
        previewMixin({ previewFrameUrl: this?.$el?.dataset.previewFrameUrl }),
        {
            init() {
                this.$nextTick(() => this.observePreviewContainerWidth());
            },
        },
    );
});
