<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
    ];

    /**
     * Forbidden elements (dangerous)
     */
    protected array $forbiddenElements = [
        'script', 'foreignObject', 'iframe', 'object', 'embed',
        'applet', 'meta', 'link', 'style', 'base',
        'form', 'input', 'button', 'select', 'textarea',
        'audio', 'video', 'source', 'track',
        'animate', 'animateMotion', 'animateTransform', 'set', // also remove animation elements
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
     * Sanitize SVG content
     */
    public function sanitize(string $svgContent): string
    {
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
     * Sanitize and save file
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
     * Recursively check node safety
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

        // Check child nodes
        foreach ($node->childNodes as $child) {
            if (! $this->checkNodeSafety($child)) {
                return false;
            }
        }

        return true;
    }
}
