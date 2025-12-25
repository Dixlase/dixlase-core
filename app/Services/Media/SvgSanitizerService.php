<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Services\Media;

use Illuminate\Support\Facades\Log;

/**
 * SVGサニタイザーサービス
 * 
 * SVGファイルから危険な要素・属性・外部参照を除去する
 */
class SvgSanitizerService
{
    /**
     * 許可するSVG要素
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
     * 禁止する要素（危険）
     */
    protected array $forbiddenElements = [
        'script', 'foreignObject', 'iframe', 'object', 'embed',
        'applet', 'meta', 'link', 'style', 'base',
        'form', 'input', 'button', 'select', 'textarea',
        'audio', 'video', 'source', 'track',
        'animate', 'animateMotion', 'animateTransform', 'set', // アニメーション要素も除去
    ];

    /**
     * 禁止する属性（イベントハンドラ等）
     */
    protected array $forbiddenAttributes = [
        // イベントハンドラ
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
        'onbegin', 'onend', 'onrepeat', // SVGアニメーションイベント
    ];

    /**
     * 禁止する属性値パターン（正規表現）
     */
    protected array $forbiddenAttributePatterns = [
        '/javascript:/i',
        '/vbscript:/i',
        '/data:/i',
        '/expression\s*\(/i',
        '/url\s*\(\s*["\']?\s*javascript:/i',
    ];

    /**
     * SVGコンテンツをサニタイズする
     */
    public function sanitize(string $svgContent): string
    {
        // XMLとして解析
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        
        // SVGをロード（UTF-8エンコーディングを明示）
        $svgContent = $this->ensureUtf8($svgContent);
        $loaded = $dom->loadXML($svgContent, LIBXML_NONET | LIBXML_NOENT);
        
        if (!$loaded) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            Log::warning('SVG parsing failed', ['errors' => $errors]);
            return '';
        }

        // ルート要素がsvgであることを確認
        $root = $dom->documentElement;
        if (!$root || strtolower($root->nodeName) !== 'svg') {
            Log::warning('Invalid SVG: root element is not svg');
            return '';
        }

        // 再帰的にサニタイズ
        $this->sanitizeNode($root);

        // 外部参照を除去
        $this->removeExternalReferences($root);

        $result = $dom->saveXML($root);
        
        return $result !== false ? $result : '';
    }

    /**
     * ノードを再帰的にサニタイズ
     */
    protected function sanitizeNode(\DOMNode $node): void
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        /** @var \DOMElement $node */
        $nodeName = strtolower($node->nodeName);

        // 禁止要素は削除
        if (in_array($nodeName, $this->forbiddenElements)) {
            $node->parentNode?->removeChild($node);
            return;
        }

        // 許可されていない要素も削除
        if (!in_array($nodeName, $this->allowedElements)) {
            $node->parentNode?->removeChild($node);
            return;
        }

        // 属性をサニタイズ
        $this->sanitizeAttributes($node);

        // 子ノードを逆順で処理（削除時のインデックスずれを防ぐ）
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        
        foreach (array_reverse($children) as $child) {
            $this->sanitizeNode($child);
        }
    }

    /**
     * 属性をサニタイズ
     */
    protected function sanitizeAttributes(\DOMElement $element): void
    {
        $attributesToRemove = [];

        foreach ($element->attributes as $attr) {
            $attrName = strtolower($attr->nodeName);
            $attrValue = $attr->nodeValue;

            // 禁止属性を削除
            if (in_array($attrName, $this->forbiddenAttributes)) {
                $attributesToRemove[] = $attr->nodeName;
                continue;
            }

            // on*で始まる属性を削除（イベントハンドラ）
            if (str_starts_with($attrName, 'on')) {
                $attributesToRemove[] = $attr->nodeName;
                continue;
            }

            // 禁止パターンをチェック
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
     * 外部参照を除去
     */
    protected function removeExternalReferences(\DOMElement $element): void
    {
        // xlink:href や href の外部参照をチェック
        $hrefAttrs = ['href', 'xlink:href'];
        
        foreach ($hrefAttrs as $attr) {
            if ($element->hasAttribute($attr)) {
                $value = $element->getAttribute($attr);
                
                // 外部URLを除去（#で始まる内部参照は許可）
                if (!str_starts_with($value, '#') && !str_starts_with($value, 'data:image/')) {
                    // data:image/は画像埋め込みなので許可するが、他のdata:は禁止
                    if (str_starts_with($value, 'data:') && !preg_match('/^data:image\/(png|jpeg|gif|webp);base64,/i', $value)) {
                        $element->removeAttribute($attr);
                    } elseif (preg_match('/^https?:\/\//i', $value) || preg_match('/^\/\//i', $value)) {
                        $element->removeAttribute($attr);
                    }
                }
            }
        }

        // 子要素も処理
        foreach ($element->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $this->removeExternalReferences($child);
            }
        }
    }

    /**
     * UTF-8エンコーディングを確保
     */
    protected function ensureUtf8(string $content): string
    {
        // BOMを除去
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // XML宣言がない場合は追加
        if (!preg_match('/^<\?xml/i', $content)) {
            $content = '<?xml version="1.0" encoding="UTF-8"?>' . $content;
        }
        
        return $content;
    }

    /**
     * ファイルをサニタイズして保存
     */
    public function sanitizeFile(string $inputPath, ?string $outputPath = null): bool
    {
        if (!file_exists($inputPath)) {
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
     * SVGが安全かどうかをチェック（サニタイズせずに検証のみ）
     */
    public function isSafe(string $svgContent): bool
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        
        $svgContent = $this->ensureUtf8($svgContent);
        $loaded = $dom->loadXML($svgContent, LIBXML_NONET | LIBXML_NOENT);
        
        if (!$loaded) {
            libxml_clear_errors();
            return false;
        }

        $root = $dom->documentElement;
        if (!$root || strtolower($root->nodeName) !== 'svg') {
            return false;
        }

        return $this->checkNodeSafety($root);
    }

    /**
     * ノードの安全性を再帰的にチェック
     */
    protected function checkNodeSafety(\DOMNode $node): bool
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return true;
        }

        /** @var \DOMElement $node */
        $nodeName = strtolower($node->nodeName);

        // 禁止要素があれば安全でない
        if (in_array($nodeName, $this->forbiddenElements)) {
            return false;
        }

        // 属性をチェック
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

        // 子ノードをチェック
        foreach ($node->childNodes as $child) {
            if (!$this->checkNodeSafety($child)) {
                return false;
            }
        }

        return true;
    }
}
