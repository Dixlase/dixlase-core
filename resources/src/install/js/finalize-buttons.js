/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * Alpine.js component for the completion screen's two finalize buttons.
 * Lets only the first submission through: a double click otherwise sends
 * finalize twice, and the browser follows the response to the second one.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

import Alpine from 'alpinejs';

Alpine.data('installFinalizeButtons', () => ({
    submitting: false,

    init() {
        // A page restored from the back/forward cache keeps its state, which
        // would leave the buttons disabled with no request in flight.
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                this.submitting = false;
            }
        });
    },

    submit(event) {
        if (this.submitting) {
            event.preventDefault();
            return;
        }

        this.submitting = true;
    },
}));
