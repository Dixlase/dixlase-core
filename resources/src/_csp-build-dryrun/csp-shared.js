/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * @internal Dry-run scratch entry — NOT shipped. See README in this directory.
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
 * Dry-run entry: does the @alpinejs/csp build accept our shared
 * Alpine.data() factories and produce a working bundle?
 *
 * This entry is intentionally NOT exposed to any Blade view. It is built
 * by Vite to verify that:
 *   1. @alpinejs/csp can be imported alongside our shared registrations
 *   2. The resulting bundle is reasonably sized
 *   3. No build-time warnings come from our factories
 *
 * Run: `npm run build` and inspect the output for this entry name.
 *
 * If/when we commit to the strict-mode bundle, this whole directory is
 * deleted and real entries flip their import statements.
 */

import Alpine from '@alpinejs/csp';
import { registerSharedAlpineData } from '../common/js/alpine-data/index.js';

registerSharedAlpineData(Alpine);

// Register a one-off page-specific data the strict-mode-correct way.
Alpine.data('dryRunCounter', () => ({
    count: 0,

    init() {
        const initial = parseInt(this.$el?.dataset?.initial || '', 10);
        if (Number.isFinite(initial)) {
            this.count = initial;
        }
    },

    increment() {
        this.count += 1;
    },

    decrement() {
        this.count -= 1;
    },

    reset() {
        this.count = 0;
    },
}));

window.Alpine = Alpine;
Alpine.start();
