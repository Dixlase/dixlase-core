/*
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
 * Content Editor - Alpine.js Translation Helper & Marked.js
 * Provides translation magic helper and markdown parser for Alpine.js components
 */

import { marked } from 'marked';

// Expose marked to window for use in Alpine components
window.marked = marked;

document.addEventListener('alpine:init', () => {
    Alpine.magic('t', () => {
        return (key) => {
            // Get translations from data attribute on content editor element
            const editorElement = document.querySelector('[data-translations]');
            if (!editorElement) {
                console.warn('[Content Editor] No element with data-translations found');
                return key;
            }

            try {
                const translations = JSON.parse(editorElement.dataset.translations || '{}');
                return translations[key] || key;
            } catch (e) {
                console.error('[Content Editor] Failed to parse translations:', e);
                return key;
            }
        };
    });
});

console.log('[Content Editor] Alpine translation helper and marked.js loaded');
