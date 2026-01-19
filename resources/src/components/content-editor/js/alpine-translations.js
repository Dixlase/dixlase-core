/**
 * Content Editor - Alpine.js Translation Helper
 * Provides translation magic helper for Alpine.js components
 */

document.addEventListener('alpine:init', () => {
    Alpine.magic('t', () => {
        return (key) => {
            // Get translations from window object (set by blade template)
            const translations = window.editorTranslations || {};
            return translations[key] || key;
        };
    });
});

console.log('[Content Editor] Alpine translation helper loaded');
