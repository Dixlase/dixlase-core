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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Services\Csp;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Actually scan plugin/theme files to
 * Service to verify CSP compliance status
 *
 * Analyze actual code instead of plugin.json declarations to
 * Detect inline scripts, styles, and event handlers
 */
class CspComplianceScanner
{
    /**
     * List of inline event handler attributes
     *
     * @var array<string>
     */
    protected const EVENT_HANDLER_ATTRIBUTES = [
        'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover',
        'onmousemove', 'onmouseout', 'onmouseenter', 'onmouseleave',
        'onkeydown', 'onkeypress', 'onkeyup',
        'onfocus', 'onblur', 'onchange', 'oninput', 'onsubmit', 'onreset',
        'onselect', 'oncontextmenu',
        'onload', 'onunload', 'onbeforeunload', 'onerror', 'onresize', 'onscroll',
        'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover',
        'ondragstart', 'ondrop',
        'oncopy', 'oncut', 'onpaste',
        'ontouchstart', 'ontouchmove', 'ontouchend', 'ontouchcancel',
        'onanimationend', 'onanimationiteration', 'onanimationstart',
        'ontransitionend',
    ];

    /**
     * Scan plugin CSP compliance status
     *
     * @param  string  $slug  Plugin slug
     * @return array{
     *     status: string,
     *     requires_inline_js: bool,
     *     requires_inline_css: bool,
     *     has_csp_config: bool,
     *     violations: array<array{type: string, file: string, line: int, match: string, severity: string}>,
     *     summary: array{inline_scripts: int, inline_styles: int, event_handlers: int, javascript_urls: int}
     * }
     */
    public function scanPlugin(string $slug): array
    {
        $dir = $this->resolveDirectory('plugins', $slug);

        return $this->scan($dir, 'plugin', $slug);
    }

    /**
     * Scan theme CSP compliance status
     *
     * @param  string  $slug  Theme slug
     */
    public function scanTheme(string $slug): array
    {
        $dir = $this->resolveDirectory('themes', $slug);

        return $this->scan($dir, 'theme', $slug);
    }

    /**
     * Resolve actual directory path from slug
     *
     * Slug is kebab-case (e.g. my-plugin), but
     * Directory name is PascalCase (e.g. MyPlugin) so conversion is needed
     */
    protected function resolveDirectory(string $baseDir, string $slug): string
    {
        // Try StudlyCase conversion (my-plugin → MyPlugin)
        $studlyName = Str::studly(str_replace('-', '_', $slug));
        $path = base_path("{$baseDir}/{$studlyName}");
        if (File::isDirectory($path)) {
            return $path;
        }

        // Try slug as-is
        $path = base_path("{$baseDir}/{$slug}");
        if (File::isDirectory($path)) {
            return $path;
        }

        // Search from directory list by kebab-case comparison
        $parentDir = base_path($baseDir);
        if (File::isDirectory($parentDir)) {
            foreach (File::directories($parentDir) as $dir) {
                if (Str::kebab(basename($dir)) === $slug) {
                    return $dir;
                }
            }
        }

        // If not found, return path with slug as-is (will become unknown on scan() side)
        return base_path("{$baseDir}/{$slug}");
    }

    /**
     * Scan directory CSP compliance status
     *
     * @param  string  $dir  Directory to scan
     * @param  string  $type  'plugin' or 'theme'
     * @param  string  $slug  Slug
     */
    protected function scan(string $dir, string $type, string $slug): array
    {
        $violations = [];
        $summary = [
            'inline_scripts' => 0,
            'inline_styles' => 0,
            'event_handlers' => 0,
            'javascript_urls' => 0,
        ];

        if (! File::isDirectory($dir)) {
            return $this->buildResult('unknown', false, false, $slug, $type, $violations, $summary);
        }

        // Scan Blade files and view files
        $bladeFiles = $this->getBladeFiles($dir);
        foreach ($bladeFiles as $filePath) {
            $content = File::get($filePath);
            $relativePath = str_replace($dir.'/', '', $filePath);

            $this->detectInlineScripts($content, $relativePath, $violations, $summary);
            $this->detectInlineStyles($content, $relativePath, $violations, $summary);
            $this->detectEventHandlers($content, $relativePath, $violations, $summary);
            $this->detectJavascriptUrls($content, $relativePath, $violations, $summary);
        }

        // Check for CSP settings in plugin.json / theme.json
        $metaFile = $type === 'plugin' ? "{$dir}/plugin.json" : "{$dir}/theme.json";
        $hasCspConfig = false;
        if (File::exists($metaFile)) {
            $json = json_decode(File::get($metaFile), true);
            $hasCspConfig = isset($json['csp']);
        }

        // Evaluate results
        $requiresInlineJs = $summary['inline_scripts'] > 0 || $summary['event_handlers'] > 0 || $summary['javascript_urls'] > 0;
        $requiresInlineCss = $summary['inline_styles'] > 0;

        return $this->buildResult(
            $this->determineStatus($requiresInlineJs, $requiresInlineCss, $hasCspConfig),
            $requiresInlineJs,
            $requiresInlineCss,
            $slug,
            $type,
            $violations,
            $summary
        );
    }

    /**
     * Detect inline script tags
     *
     * Detect `<script>` tags without src attribute (inline code)
     * Exclude external file loads inside Blade @push('scripts')
     * Exclude JSON settings blocks (type="application/json")
     */
    protected function detectInlineScripts(string $content, string $filePath, array &$violations, array &$summary): void
    {
        // Detect <script> tags (without src attribute, excluding type="application/json")
        $pattern = '/<script\b(?![^>]*\bsrc\s*=)[^>]*>/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $tag = $match[0];
                $offset = $match[1];

                // Exclude type="application/json" and type="application/ld+json"
                if (preg_match('/type\s*=\s*["\']application\/(json|ld\+json)["\']/i', $tag)) {
                    continue;
                }

                // Exclude @cspNonce as it is CSP-compliant
                if (str_contains($tag, '@cspNonce')) {
                    continue;
                }

                // Exclude inside Blade comments
                if ($this->isInsideBladeComment($content, $offset)) {
                    continue;
                }

                $line = $this->getLineNumber($content, $offset);
                $violations[] = [
                    'type' => 'inline_script',
                    'file' => $filePath,
                    'line' => $line,
                    'match' => $this->truncateMatch($tag),
                    'severity' => 'warning',
                ];
                $summary['inline_scripts']++;
            }
        }
    }

    /**
     * Detect inline style tags
     *
     * Detect `<style>` tags
     * Also detect cases containing Tailwind's @apply
     */
    protected function detectInlineStyles(string $content, string $filePath, array &$violations, array &$summary): void
    {
        $pattern = '/<style\b[^>]*>/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $tag = $match[0];
                $offset = $match[1];

                // Exclude @cspNonce as it is CSP-compliant
                if (str_contains($tag, '@cspNonce')) {
                    continue;
                }

                // Exclude inside Blade comments
                if ($this->isInsideBladeComment($content, $offset)) {
                    continue;
                }

                $line = $this->getLineNumber($content, $offset);
                $violations[] = [
                    'type' => 'inline_style',
                    'file' => $filePath,
                    'line' => $line,
                    'match' => $this->truncateMatch($tag),
                    'severity' => 'info',
                ];
                $summary['inline_styles']++;
            }
        }
    }

    /**
     * Detect inline event handler attributes
     *
     * Detect inline event handlers such as onclick, onchange, etc.
     * Exclude Alpine.js directives (@click, x-on:click, etc.)
     */
    protected function detectEventHandlers(string $content, string $filePath, array &$violations, array &$summary): void
    {
        // Event handler attribute patterns (those appearing as HTML attributes)
        $attrList = implode('|', self::EVENT_HANDLER_ATTRIBUTES);
        $pattern = '/\b('.$attrList.')\s*=\s*["\'][^"\']*["\']/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $text = $match[0];
                $offset = $match[1];

                // Exclude inside Blade comments and HTML comments
                if ($this->isInsideBladeComment($content, $offset) || $this->isInsideHtmlComment($content, $offset)) {
                    continue;
                }

                // Exclude PHP comments (PHPDoc, line comments)
                if ($this->isInsidePhpComment($content, $offset)) {
                    continue;
                }

                $line = $this->getLineNumber($content, $offset);
                $violations[] = [
                    'type' => 'event_handler',
                    'file' => $filePath,
                    'line' => $line,
                    'match' => $this->truncateMatch($text),
                    'severity' => 'warning',
                ];
                $summary['event_handlers']++;
            }
        }
    }

    /**
     * Detect javascript: URLs
     *
     * Detect patterns like href="javascript:..."
     */
    protected function detectJavascriptUrls(string $content, string $filePath, array &$violations, array &$summary): void
    {
        $pattern = '/(?:href|src|action)\s*=\s*["\']javascript\s*:/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $text = $match[0];
                $offset = $match[1];

                // Exclude comments
                if ($this->isInsideBladeComment($content, $offset) || $this->isInsideHtmlComment($content, $offset)) {
                    continue;
                }

                $line = $this->getLineNumber($content, $offset);
                $violations[] = [
                    'type' => 'javascript_url',
                    'file' => $filePath,
                    'line' => $line,
                    'match' => $this->truncateMatch($text),
                    'severity' => 'critical',
                ];
                $summary['javascript_urls']++;
            }
        }
    }

    /**
     * Get Blade file list
     *
     * @return array<string>
     */
    protected function getBladeFiles(string $dir): array
    {
        $files = [];
        $excludeDirs = ['tests', 'vendor', 'node_modules'];

        if (! File::isDirectory($dir)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            // Skip files in excluded directories
            $relativePath = str_replace($dir.'/', '', $file->getPathname());
            $topDir = explode('/', $relativePath)[0] ?? '';
            if (in_array($topDir, $excludeDirs, true)) {
                continue;
            }

            $filename = $file->getFilename();

            // Only target Blade template files (.blade.php)
            if (str_ends_with($filename, '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Determine CSP status
     */
    protected function determineStatus(bool $requiresInlineJs, bool $requiresInlineCss, bool $hasCspConfig): string
    {
        if ($requiresInlineJs) {
            return 'inline_required';
        }

        if ($requiresInlineCss) {
            return 'inline_css_only';
        }

        if ($hasCspConfig) {
            return 'csp_ready';
        }

        return 'compatible';
    }

    /**
     * Build scan results
     *
     * @param  array<array>  $violations
     * @param  array<string, int>  $summary
     */
    protected function buildResult(
        string $status,
        bool $requiresInlineJs,
        bool $requiresInlineCss,
        string $slug,
        string $type,
        array $violations,
        array $summary,
    ): array {
        // Also check plugin.json declarations
        $metaFile = $type === 'plugin'
            ? base_path("plugins/{$slug}/plugin.json")
            : base_path("themes/{$slug}/theme.json");

        $hasCspConfig = false;
        if (File::exists($metaFile)) {
            $json = json_decode(File::get($metaFile), true);
            $hasCspConfig = isset($json['csp']);
        }

        return [
            'status' => $status,
            'requires_inline_js' => $requiresInlineJs,
            'requires_inline_css' => $requiresInlineCss,
            'has_csp_config' => $hasCspConfig,
            'csp_ready' => ! $requiresInlineJs && ! $requiresInlineCss,
            'violations' => $violations,
            'summary' => $summary,
        ];
    }

    /**
     * Determine if inside Blade comment
     */
    protected function isInsideBladeComment(string $content, int $offset): bool
    {
        // Find the last {{-- before offset and check if corresponding --}} exists after offset
        $before = substr($content, 0, $offset);
        $openPos = strrpos($before, '{{--');
        if ($openPos === false) {
            return false;
        }

        $closePos = strpos($content, '--}}', $openPos + 4);

        return $closePos !== false && $closePos > $offset;
    }

    /**
     * Determine if inside HTML comment
     */
    protected function isInsideHtmlComment(string $content, int $offset): bool
    {
        $before = substr($content, 0, $offset);
        $openPos = strrpos($before, '<!--');
        if ($openPos === false) {
            return false;
        }

        // Skip Blade comments (handled by separate method)
        if (substr($content, $openPos, 4) === '{{--') {
            return false;
        }

        $closePos = strpos($content, '-->', $openPos + 4);

        return $closePos !== false && $closePos > $offset;
    }

    /**
     * Determine if inside PHP comment
     */
    protected function isInsidePhpComment(string $content, int $offset): bool
    {
        // Get target line
        $before = substr($content, 0, $offset);
        $lineStart = strrpos($before, "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;
        $line = substr($content, $lineStart, $offset - $lineStart);

        // Line comment
        if (preg_match('/\/\//', $line) || preg_match('/^\s*\*/', $line) || preg_match('/^\s*#/', $line)) {
            return true;
        }

        // Block comment
        $beforeStr = substr($content, 0, $offset);
        $lastOpen = strrpos($beforeStr, '/*');
        if ($lastOpen !== false) {
            $lastClose = strrpos($beforeStr, '*/');
            if ($lastClose === false || $lastClose < $lastOpen) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate line number from offset
     */
    protected function getLineNumber(string $content, int $offset): int
    {
        return substr_count($content, "\n", 0, min($offset, strlen($content))) + 1;
    }

    /**
     * Truncate matched string for display
     */
    protected function truncateMatch(string $text, int $maxLength = 80): string
    {
        $text = trim($text);

        if (mb_strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength).'...';
        }

        return $text;
    }
}
