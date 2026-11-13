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

// Single line breaks render as <br>, as ContentPreviewService does.
marked.setOptions({ breaks: true });

// `@[service](URL)` on a line of its own (#731). The side-by-side preview
// shows it as a link to the URL; the server renders the real embed, which
// the preview pane and the published page use.
const EMBED_SERVICES = { youtube: 'YouTube', x: 'X', github: 'GitHub' };

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

marked.use({
    extensions: [{
        name: 'contentEmbed',
        level: 'block',
        tokenizer(src) {
            const match = /^ {0,3}@\[(youtube|x|github)\]\(\s*(https?:\/\/\S+?)\s*\)[ \t]*(?:\n+|$)/i.exec(src);
            if (!match) {
                return undefined;
            }
            return { type: 'contentEmbed', raw: match[0], service: match[1].toLowerCase(), url: match[2] };
        },
        renderer(token) {
            const url = escapeHtml(token.url);
            return '<p class="dls-embed"><a href="' + url + '">' + EMBED_SERVICES[token.service] + ': ' + url + '</a></p>\n';
        },
    }],
});

// A link's `{target="_blank" rel="..."}` (#1032) is for the server's
// renderer; marked would print it as text, so the side-by-side preview drops
// it and shows the plain link.
// Items are separated by whitespace, which no value can contain, so the
// pattern has one way to match and cannot backtrack exponentially.
const LINK_ATTRIBUTE = '(?:target|rel)=(?:"[^"\\n]*"|[^\\s}"=]+)';
const LINK_ATTRIBUTES = new RegExp('(\\]\\([^)\\n]*\\))\\{[ \\t]*' + LINK_ATTRIBUTE + '(?:[ \\t]+' + LINK_ATTRIBUTE + ')*[ \\t]*\\}', 'g');

marked.use({
    hooks: {
        preprocess(markdown) {
            return markdown.replace(LINK_ATTRIBUTES, '$1');
        },
    },
});

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

