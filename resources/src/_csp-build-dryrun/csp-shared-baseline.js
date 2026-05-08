/**
 * Baseline comparison entry — same code as csp-shared.js but using the
 * regular Alpine bundle. Used to measure size delta when switching to
 * @alpinejs/csp.
 *
 * @internal Dry-run scratch entry — NOT shipped.
 */

import Alpine from 'alpinejs';
import { registerSharedAlpineData } from '../common/js/alpine-data/index.js';

registerSharedAlpineData(Alpine);

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
