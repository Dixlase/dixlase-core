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
