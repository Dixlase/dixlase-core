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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
 * プラグイン/テーマのファイルを実際にスキャンして
 * CSP準拠状況を検証するサービス
 *
 * plugin.json の宣言ではなく、実際のコードを解析して
 * インラインスクリプト・スタイル・イベントハンドラを検出する
 */
class CspComplianceScanner
{
    /**
     * インラインイベントハンドラ属性のリスト
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
     * プラグインのCSP準拠状況をスキャン
     *
     * @param  string  $slug  プラグインスラッグ
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
     * テーマのCSP準拠状況をスキャン
     *
     * @param  string  $slug  テーマスラッグ
     */
    public function scanTheme(string $slug): array
    {
        $dir = $this->resolveDirectory('themes', $slug);

        return $this->scan($dir, 'theme', $slug);
    }

    /**
     * スラッグから実際のディレクトリパスを解決する
     *
     * スラッグはkebab-case（例: dixlase-legal）だが、
     * ディレクトリ名はPascalCase（例: DixlaseLegal）のため変換が必要
     */
    protected function resolveDirectory(string $baseDir, string $slug): string
    {
        // StudlyCase変換を試行（dixlase-legal → DixlaseLegal）
        $studlyName = Str::studly(str_replace('-', '_', $slug));
        $path = base_path("{$baseDir}/{$studlyName}");
        if (File::isDirectory($path)) {
            return $path;
        }

        // スラッグそのままを試行
        $path = base_path("{$baseDir}/{$slug}");
        if (File::isDirectory($path)) {
            return $path;
        }

        // ディレクトリ一覧からkebab-case比較で検索
        $parentDir = base_path($baseDir);
        if (File::isDirectory($parentDir)) {
            foreach (File::directories($parentDir) as $dir) {
                if (Str::kebab(basename($dir)) === $slug) {
                    return $dir;
                }
            }
        }

        // 見つからない場合はスラッグのままのパスを返す（scan()側でunknownになる）
        return base_path("{$baseDir}/{$slug}");
    }

    /**
     * ディレクトリのCSP準拠状況をスキャン
     *
     * @param  string  $dir  スキャン対象ディレクトリ
     * @param  string  $type  'plugin' または 'theme'
     * @param  string  $slug  スラッグ
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

        // Bladeファイルとビューファイルをスキャン
        $bladeFiles = $this->getBladeFiles($dir);
        foreach ($bladeFiles as $filePath) {
            $content = File::get($filePath);
            $relativePath = str_replace($dir.'/', '', $filePath);

            $this->detectInlineScripts($content, $relativePath, $violations, $summary);
            $this->detectInlineStyles($content, $relativePath, $violations, $summary);
            $this->detectEventHandlers($content, $relativePath, $violations, $summary);
            $this->detectJavascriptUrls($content, $relativePath, $violations, $summary);
        }

        // plugin.json / theme.json からCSP設定の有無を確認
        $metaFile = $type === 'plugin' ? "{$dir}/plugin.json" : "{$dir}/theme.json";
        $hasCspConfig = false;
        if (File::exists($metaFile)) {
            $json = json_decode(File::get($metaFile), true);
            $hasCspConfig = isset($json['csp']);
        }

        // 結果を判定
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
     * インラインスクリプトタグを検出
     *
     * `<script>` タグのうち、src属性なし（インラインコード）のものを検出する
     * Blade @push('scripts') 内の外部ファイル読み込みは除外
     * JSON設定ブロック (type="application/json") は除外
     */
    protected function detectInlineScripts(string $content, string $filePath, array &$violations, array &$summary): void
    {
        // <script> タグを検出（src属性なし、type="application/json" 以外）
        $pattern = '/<script\b(?![^>]*\bsrc\s*=)[^>]*>/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $tag = $match[0];
                $offset = $match[1];

                // type="application/json" や type="application/ld+json" は除外
                if (preg_match('/type\s*=\s*["\']application\/(json|ld\+json)["\']/i', $tag)) {
                    continue;
                }

                // @cspNonce 付きはCSP準拠のため除外
                if (str_contains($tag, '@cspNonce')) {
                    continue;
                }

                // Bladeコメント内は除外
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
     * インラインスタイルタグを検出
     *
     * `<style>` タグを検出する
     * Tailwind の @apply を含む場合も検出対象
     */
    protected function detectInlineStyles(string $content, string $filePath, array &$violations, array &$summary): void
    {
        $pattern = '/<style\b[^>]*>/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $tag = $match[0];
                $offset = $match[1];

                // @cspNonce 付きはCSP準拠のため除外
                if (str_contains($tag, '@cspNonce')) {
                    continue;
                }

                // Bladeコメント内は除外
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
     * インラインイベントハンドラ属性を検出
     *
     * onclick, onchange 等のインラインイベントハンドラを検出する
     * Alpine.js のディレクティブ (@click, x-on:click 等) は除外
     */
    protected function detectEventHandlers(string $content, string $filePath, array &$violations, array &$summary): void
    {
        // イベントハンドラ属性のパターン（HTML属性として出現するもの）
        $attrList = implode('|', self::EVENT_HANDLER_ATTRIBUTES);
        $pattern = '/\b('.$attrList.')\s*=\s*["\'][^"\']*["\']/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $text = $match[0];
                $offset = $match[1];

                // Bladeコメント・HTMLコメント内は除外
                if ($this->isInsideBladeComment($content, $offset) || $this->isInsideHtmlComment($content, $offset)) {
                    continue;
                }

                // PHPコメント内は除外（PHPDoc、行コメント）
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
     * javascript: URL を検出
     *
     * href="javascript:..." 等のパターンを検出する
     */
    protected function detectJavascriptUrls(string $content, string $filePath, array &$violations, array &$summary): void
    {
        $pattern = '/(?:href|src|action)\s*=\s*["\']javascript\s*:/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $text = $match[0];
                $offset = $match[1];

                // コメント内は除外
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
     * Bladeファイル一覧を取得
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

            // 除外ディレクトリ内のファイルをスキップ
            $relativePath = str_replace($dir.'/', '', $file->getPathname());
            $topDir = explode('/', $relativePath)[0] ?? '';
            if (in_array($topDir, $excludeDirs, true)) {
                continue;
            }

            $filename = $file->getFilename();

            // Bladeテンプレートファイル（.blade.php）のみ対象
            if (str_ends_with($filename, '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * CSPステータスを判定
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
     * スキャン結果を構築
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
        // plugin.json の宣言も確認
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
     * Bladeコメント内かどうかを判定
     */
    protected function isInsideBladeComment(string $content, int $offset): bool
    {
        // offset以前の最後の {{-- を探し、対応する --}} がoffset以降にあるかチェック
        $before = substr($content, 0, $offset);
        $openPos = strrpos($before, '{{--');
        if ($openPos === false) {
            return false;
        }

        $closePos = strpos($content, '--}}', $openPos + 4);

        return $closePos !== false && $closePos > $offset;
    }

    /**
     * HTMLコメント内かどうかを判定
     */
    protected function isInsideHtmlComment(string $content, int $offset): bool
    {
        $before = substr($content, 0, $offset);
        $openPos = strrpos($before, '<!--');
        if ($openPos === false) {
            return false;
        }

        // Bladeコメントの場合はスキップ（別メソッドで処理）
        if (substr($content, $openPos, 4) === '{{--') {
            return false;
        }

        $closePos = strpos($content, '-->', $openPos + 4);

        return $closePos !== false && $closePos > $offset;
    }

    /**
     * PHPコメント内かどうかを判定
     */
    protected function isInsidePhpComment(string $content, int $offset): bool
    {
        // 対象行を取得
        $before = substr($content, 0, $offset);
        $lineStart = strrpos($before, "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;
        $line = substr($content, $lineStart, $offset - $lineStart);

        // 行コメント
        if (preg_match('/\/\//', $line) || preg_match('/^\s*\*/', $line) || preg_match('/^\s*#/', $line)) {
            return true;
        }

        // ブロックコメント
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
     * オフセットから行番号を算出
     */
    protected function getLineNumber(string $content, int $offset): int
    {
        return substr_count($content, "\n", 0, min($offset, strlen($content))) + 1;
    }

    /**
     * マッチ文字列を表示用に切り詰め
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
