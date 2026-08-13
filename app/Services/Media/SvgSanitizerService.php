<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace App\Services\Media;

use Illuminate\Support\Facades\Log;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * SVG sanitizer service
 *
 * Remove dangerous elements, attributes, and external references from SVG files
 */
class SvgSanitizerService
{
    /**
     * Allowed SVG elements
     */
    protected array $allowedElements = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc',
        'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'path',
        'text', 'tspan', 'textPath',
        'image', 'clipPath', 'mask', 'pattern',
        'linearGradient', 'radialGradient', 'stop',
        'filter', 'feBlend', 'feColorMatrix', 'feComponentTransfer',
        'feComposite', 'feConvolveMatrix', 'feDiffuseLighting',
        'feDisplacementMap', 'feFlood', 'feGaussianBlur', 'feImage',
        'feMerge', 'feMergeNode', 'feMorphology', 'feOffset',
        'feSpecularLighting', 'feTile', 'feTurbulence',
        'marker', 'switch',
        'style', // CSS content is filtered to a safe-property allowlist; see sanitizeStyleContent()
    ];

    /**
     * Forbidden elements (dangerous)
     */
    protected array $forbiddenElements = [
        'script', 'foreignObject', 'iframe', 'object', 'embed',
        'applet', 'meta', 'link', 'base',
        'form', 'input', 'button', 'select', 'textarea',
        'audio', 'video', 'source', 'track',
        'animate', 'animateMotion', 'animateTransform', 'set', // also remove animation elements
    ];

    /**
     * CSS properties allowed inside <style>.
     *
     * Limited to the SVG "presentation attribute" set (SVG 1.1 §6.4 / SVG 2 §11).
     * Every property listed here also has an equivalent XML attribute form, so
     * nothing rendered here can express behaviour that couldn't already be
     * expressed via attributes we allow.
     */
    protected array $allowedStyleProperties = [
        'alignment-baseline', 'baseline-shift', 'clip-path', 'clip-rule',
        'color', 'color-interpolation', 'color-interpolation-filters', 'color-rendering',
        'cursor', 'direction', 'display', 'dominant-baseline',
        'fill', 'fill-opacity', 'fill-rule',
        'filter', 'flood-color', 'flood-opacity',
        'font', 'font-family', 'font-size', 'font-size-adjust',
        'font-stretch', 'font-style', 'font-variant', 'font-weight',
        'glyph-orientation-horizontal', 'glyph-orientation-vertical',
        'image-rendering', 'letter-spacing', 'lighting-color',
        'marker', 'marker-end', 'marker-mid', 'marker-start', 'mask',
        'opacity', 'overflow', 'paint-order', 'pointer-events',
        'shape-rendering', 'stop-color', 'stop-opacity',
        'stroke', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-linecap',
        'stroke-linejoin', 'stroke-miterlimit', 'stroke-opacity', 'stroke-width',
        'text-anchor', 'text-decoration', 'text-overflow', 'text-rendering',
        'transform', 'unicode-bidi', 'vector-effect', 'visibility',
        'white-space', 'word-spacing', 'writing-mode',
    ];

    /**
     * Forbidden attributes (event handlers, etc.)
     */
    protected array $forbiddenAttributes = [
        // Event handlers
        'onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout',
        'onmousedown', 'onmouseup', 'onmousemove', 'onfocus', 'onblur',
        'onchange', 'onsubmit', 'onreset', 'onselect', 'onkeydown',
        'onkeypress', 'onkeyup', 'ondblclick', 'oncontextmenu',
        'onwheel', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave',
        'ondragover', 'ondragstart', 'ondrop', 'onscroll', 'oncopy',
        'oncut', 'onpaste', 'onabort', 'oncanplay', 'oncanplaythrough',
        'oncuechange', 'ondurationchange', 'onemptied', 'onended',
        'oninput', 'oninvalid', 'onloadeddata', 'onloadedmetadata',
        'onloadstart', 'onpause', 'onplay', 'onplaying', 'onprogress',
        'onratechange', 'onseeked', 'onseeking', 'onstalled', 'onsuspend',
        'ontimeupdate', 'onvolumechange', 'onwaiting', 'ontoggle',
        'onbegin', 'onend', 'onrepeat', // SVG animation events
    ];

    /**
     * Forbidden attribute value patterns (regex)
     */
    protected array $forbiddenAttributePatterns = [
        '/javascript:/i',
        '/vbscript:/i',
        '/data:/i',
        '/expression\s*\(/i',
        '/url\s*\(\s*["\']?\s*javascript:/i',
    ];

    /**
     * Sanitize SVG content.
     *
     * Fast path: if isSafe() confirms nothing would be stripped, return the
     * caller's bytes verbatim — the DOMDocument::saveXML() round-trip
     * otherwise normalises the XML declaration, quote style, attribute
     * order, whitespace, self-closing form, and CDATA/comment layout, so a
     * clean SVG from Illustrator / Figma / Inkscape ends up looking
     * "silently rewritten" even though nothing dangerous was found. Since
     * isSafe() is a superset check of what sanitize would remove (see
     * checkNodeSafety), this shortcut is safe.
     */
    public function sanitize(string $svgContent): string
    {
        if ($this->isSafe($svgContent)) {
            return $svgContent;
        }

        // Parse as XML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();

        // Load SVG (explicitly specify UTF-8 encoding)
        $svgContent = $this->ensureUtf8($svgContent);
        $loaded = $dom->loadXML($svgContent, LIBXML_NONET | LIBXML_NOENT);

        if (! $loaded) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            Log::warning('SVG parsing failed', ['errors' => $errors]);

            return '';
        }

        // Verify that root element is svg
        $root = $dom->documentElement;
        if (! $root || strtolower($root->nodeName) !== 'svg') {
            Log::warning('Invalid SVG: root element is not svg');

            return '';
        }

        // Sanitize recursively
        $this->sanitizeNode($root);

        // Remove external references
        $this->removeExternalReferences($root);

        $result = $dom->saveXML($root);

        return $result !== false ? $result : '';
    }

    /**
     * Sanitize nodes recursively
     */
    protected function sanitizeNode(\DOMNode $node): void
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        /** @var \DOMElement $node */
        $nodeName = strtolower($node->nodeName);

        // Remove forbidden elements
        if (in_array($nodeName, $this->forbiddenElements)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        // Remove non-allowed elements as well
        if (! in_array($nodeName, $this->allowedElements)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        // Sanitize attributes
        $this->sanitizeAttributes($node);

        // Special case: <style> children are CSS text, not markup — filter to
        // a safe-property allowlist instead of recursing. If nothing survives,
        // drop the element entirely so we don't leave an empty <style> stub.
        if ($nodeName === 'style') {
            $safeCss = $this->sanitizeStyleContent($this->collectTextContent($node));

            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }

            if ($safeCss === '') {
                $node->parentNode?->removeChild($node);

                return;
            }

            $node->appendChild($node->ownerDocument->createCDATASection($safeCss));

            return;
        }

        // Process child nodes in reverse order (prevent index shift on deletion)
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach (array_reverse($children) as $child) {
            $this->sanitizeNode($child);
        }
    }

    /**
     * Concatenate the text/CDATA content of an element (shallow — direct children only).
     */
    protected function collectTextContent(\DOMElement $element): string
    {
        $text = '';
        foreach ($element->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $text .= $child->nodeValue;
            }
        }

        return $text;
    }

    /**
     * Filter CSS inside <style> to declarations that use SVG presentation properties only.
     *
     * Guards applied, in order:
     *   1. Strip CSS comments (/* ... *&#47;) up-front so nothing hides inside them.
     *   2. Drop every at-rule (`@import`, `@font-face`, `@media`, `@keyframes`, …)
     *      — none of them are needed for a static SVG and they are historic injection
     *      vectors (`@import url("javascript:…")`, `@font-face src: url()`, etc.).
     *   3. For each remaining ruleset:
     *      - reject the whole ruleset if the selector contains `<`, `>`, `@`, or
     *        one of the script-scheme keywords;
     *      - keep declarations whose property is in the presentation-attribute
     *        allowlist and whose value has no `javascript:` / `vbscript:` /
     *        `expression()`, and whose `url(...)` reference (if any) points at
     *        a same-document fragment (`url(#gradient1)`).
     */
    protected function sanitizeStyleContent(string $css): string
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css) ?? '';

        // Peel off every at-rule. Handles both block form (`@x { … }`, possibly
        // one level of nesting for `@media`) and statement form (`@import "…";`).
        $prev = null;
        while ($prev !== $css) {
            $prev = $css;
            $css = preg_replace(
                '/@[^{;]+(?:\{(?:[^{}]|\{[^{}]*\})*\}|;)/i',
                '',
                $css
            ) ?? '';
        }

        $safeRules = [];
        if (preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $selector = trim($m[1]);
                $declarations = $m[2];

                if ($selector === '') {
                    continue;
                }

                if (preg_match('/[<>@]|expression\s*\(|javascript:|vbscript:/i', $selector)) {
                    continue;
                }

                $safeDecls = [];
                foreach (explode(';', $declarations) as $decl) {
                    $decl = trim($decl);
                    if ($decl === '' || strpos($decl, ':') === false) {
                        continue;
                    }

                    [$prop, $value] = array_map('trim', explode(':', $decl, 2));
                    $prop = strtolower($prop);

                    if (! in_array($prop, $this->allowedStyleProperties, true)) {
                        continue;
                    }

                    if (preg_match('/javascript:|vbscript:|expression\s*\(/i', $value)) {
                        continue;
                    }

                    // Only same-document url(#id) references are permitted (needed for
                    // fill: url(#gradient1) and mask: url(#clip1) patterns).
                    if (preg_match('/url\s*\(/i', $value)
                        && ! preg_match('/^[^)]*url\s*\(\s*["\']?#[^"\')]+["\']?\s*\)[^)]*$/i', $value)) {
                        continue;
                    }

                    $safeDecls[] = $prop.': '.$value;
                }

                if ($safeDecls !== []) {
                    $safeRules[] = $selector.' { '.implode('; ', $safeDecls).' }';
                }
            }
        }

        return implode("\n", $safeRules);
    }

    /**
     * Sanitize attributes
     */
    protected function sanitizeAttributes(\DOMElement $element): void
    {
        $attributesToRemove = [];

        foreach ($element->attributes as $attr) {
            $attrName = strtolower($attr->nodeName);
            $attrValue = $attr->nodeValue;

            // Remove forbidden attributes
            if (in_array($attrName, $this->forbiddenAttributes)) {
                $attributesToRemove[] = $attr->nodeName;

                continue;
            }

            // Remove attributes starting with on* (event handlers)
            if (str_starts_with($attrName, 'on')) {
                $attributesToRemove[] = $attr->nodeName;

                continue;
            }

            // Check for forbidden patterns
            foreach ($this->forbiddenAttributePatterns as $pattern) {
                if (preg_match($pattern, $attrValue)) {
                    $attributesToRemove[] = $attr->nodeName;
                    break;
                }
            }
        }

        foreach ($attributesToRemove as $attrName) {
            $element->removeAttribute($attrName);
        }
    }

    /**
     * Remove external references
     */
    protected function removeExternalReferences(\DOMElement $element): void
    {
        // Check xlink:href and href for external references
        $hrefAttrs = ['href', 'xlink:href'];

        foreach ($hrefAttrs as $attr) {
            if ($element->hasAttribute($attr)) {
                $value = $element->getAttribute($attr);

                // Remove external URLs (allow internal references starting with #)
                if (! str_starts_with($value, '#') && ! str_starts_with($value, 'data:image/')) {
                    // Allow data:image/ for embedded images, but forbid other data: schemes
                    if (str_starts_with($value, 'data:') && ! preg_match('/^data:image\/(png|jpeg|gif|webp);base64,/i', $value)) {
                        $element->removeAttribute($attr);
                    } elseif (preg_match('/^https?:\/\//i', $value) || preg_match('/^\/\//i', $value)) {
                        $element->removeAttribute($attr);
                    }
                }
            }
        }

        // Process child elements as well
        foreach ($element->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $this->removeExternalReferences($child);
            }
        }
    }

    /**
     * Ensure UTF-8 encoding
     */
    protected function ensureUtf8(string $content): string
    {
        // Remove BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Add XML declaration if not present
        if (! preg_match('/^<\?xml/i', $content)) {
            $content = '<?xml version="1.0" encoding="UTF-8"?>'.$content;
        }

        return $content;
    }

    /**
     * Sanitize and save file.
     *
     * When sanitize() returns bytes identical to the input (clean SVG
     * fast-path), the in-place case skips the write entirely — no need to
     * update mtime or rewrite the file when we haven't changed anything.
     * The explicit outputPath case still writes, since the caller asked
     * for a copy at a different path.
     */
    public function sanitizeFile(string $inputPath, ?string $outputPath = null): bool
    {
        if (! file_exists($inputPath)) {
            return false;
        }

        $content = file_get_contents($inputPath);
        if ($content === false) {
            return false;
        }

        $sanitized = $this->sanitize($content);
        if (empty($sanitized)) {
            return false;
        }

        $targetPath = $outputPath ?? $inputPath;

        if ($outputPath === null && $sanitized === $content) {
            return true;
        }

        return file_put_contents($targetPath, $sanitized) !== false;
    }

    /**
     * Check if SVG is safe (validation only, without sanitizing)
     */
    public function isSafe(string $svgContent): bool
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();

        $svgContent = $this->ensureUtf8($svgContent);
        $loaded = $dom->loadXML($svgContent, LIBXML_NONET | LIBXML_NOENT);

        if (! $loaded) {
            libxml_clear_errors();

            return false;
        }

        $root = $dom->documentElement;
        if (! $root || strtolower($root->nodeName) !== 'svg') {
            return false;
        }

        return $this->checkNodeSafety($root);
    }

    /**
     * Recursively check node safety.
     *
     * Kept as a strict superset of what sanitize() would remove — if this
     * returns true, the sanitizer would not modify semantic content and
     * sanitize() can safely return the caller's original bytes verbatim.
     * Rules mirrored here from sanitize()'s traversal:
     *   • element must be in $allowedElements and not in $forbiddenElements
     *     (sanitize drops non-allowed elements just as it drops forbidden ones)
     *   • no forbidden attribute name / on* handler
     *   • no forbidden attribute-value pattern (javascript:, expression(), …)
     *   • no external href / xlink:href (only #fragment and image data URIs
     *     survive removeExternalReferences)
     *   • <style> CSS matches its sanitizer round-trip
     */
    protected function checkNodeSafety(\DOMNode $node): bool
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return true;
        }

        /** @var \DOMElement $node */
        $nodeName = strtolower($node->nodeName);

        // Unsafe if forbidden elements exist
        if (in_array($nodeName, $this->forbiddenElements)) {
            return false;
        }

        // Also unsafe if the element is outside the allowlist — sanitize()
        // strips those the same way it strips forbidden elements, so treat
        // them the same in the safety check.
        if (! in_array($nodeName, $this->allowedElements)) {
            return false;
        }

        // Check attributes
        foreach ($node->attributes as $attr) {
            $attrName = strtolower($attr->nodeName);
            $attrValue = $attr->nodeValue;

            if (in_array($attrName, $this->forbiddenAttributes) || str_starts_with($attrName, 'on')) {
                return false;
            }

            foreach ($this->forbiddenAttributePatterns as $pattern) {
                if (preg_match($pattern, $attrValue)) {
                    return false;
                }
            }
        }

        // External-reference check, mirroring removeExternalReferences():
        // href / xlink:href are only allowed as same-document #fragments or as
        // base64 image data URIs. Anything else (http://, //, non-image
        // data:) would be stripped by sanitize, so mark unsafe.
        foreach (['href', 'xlink:href'] as $hrefAttr) {
            if (! $node->hasAttribute($hrefAttr)) {
                continue;
            }
            $value = $node->getAttribute($hrefAttr);
            if (str_starts_with($value, '#')) {
                continue;
            }
            if (str_starts_with($value, 'data:image/')
                && preg_match('/^data:image\/(png|jpeg|gif|webp);base64,/i', $value)) {
                continue;
            }
            return false;
        }

        // <style> CSS content is validated by round-tripping through the
        // sanitizer: if anything would be stripped the input is unsafe.
        if ($nodeName === 'style') {
            $original = $this->collectTextContent($node);
            $sanitized = $this->sanitizeStyleContent($original);

            if ($this->normaliseCssForCompare($original) !== $this->normaliseCssForCompare($sanitized)) {
                return false;
            }
        }

        // Check child nodes
        foreach ($node->childNodes as $child) {
            if (! $this->checkNodeSafety($child)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Collapse whitespace and trim trailing semicolons so that
     * "  .st0 { fill: #fff; }  " and ".st0 { fill: #fff }" compare equal.
     */
    protected function normaliseCssForCompare(string $css): string
    {
        $css = preg_replace('/\s+/', ' ', $css) ?? '';
        $css = preg_replace('/\s*;\s*}/', ' }', $css) ?? '';

        return trim($css);
    }
}
