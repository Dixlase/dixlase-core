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
 */

namespace App\Services\Csp;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * CSP診断サービス
 *
 * テーマやプラグインのBladeファイルをスキャンして、
 * CSP非対応のインラインスクリプト/スタイルを検出します。
 */
class CspDiagnosticService
{
    /**
     * CSP対応済みのパターン（これらはスキップ）
     */
    protected array $cspCompliantPatterns = [
        '/<script\s+[^>]*@cspNonce[^>]*>/i',
        '/<script\s+[^>]*nonce\s*=/i',
        '/<style\s+[^>]*@cspNonce[^>]*>/i',
        '/<style\s+[^>]*nonce\s*=/i',
    ];

    /**
     * 外部スクリプト/スタイルのパターン（これらはスキップ）
     */
    protected array $externalResourcePatterns = [
        '/<script\s+[^>]*src\s*=/i',
        '/<link\s+[^>]*href\s*=/i',
    ];

    /**
     * インラインスクリプト/スタイルのパターン（検出対象）
     */
    protected array $inlinePatterns = [
        'script' => '/<script(?:\s+[^>]*)?>(?!<\/script>)/i',
        'style' => '/<style(?:\s+[^>]*)?>(?!<\/style>)/i',
    ];

    /**
     * ディレクトリをスキャンしてCSP問題を検出
     *
     * @param  string  $directory  スキャン対象ディレクトリ
     * @return array 検出結果
     */
    public function scanDirectory(string $directory): array
    {
        $results = [
            'compliant' => true,
            'issues' => [],
            'summary' => [
                'total_files' => 0,
                'files_with_issues' => 0,
                'total_issues' => 0,
                'inline_scripts' => 0,
                'inline_styles' => 0,
            ],
        ];

        if (! File::isDirectory($directory)) {
            return $results;
        }

        // Bladeファイルを再帰的に取得
        $files = File::allFiles($directory);
        $bladeFiles = array_filter($files, function ($file) {
            return Str::endsWith($file->getFilename(), '.blade.php');
        });

        $results['summary']['total_files'] = count($bladeFiles);

        foreach ($bladeFiles as $file) {
            $fileIssues = $this->scanFile($file->getPathname());

            if (! empty($fileIssues)) {
                $relativePath = Str::after($file->getPathname(), $directory.'/');
                $results['issues'][$relativePath] = $fileIssues;
                $results['summary']['files_with_issues']++;
                $results['summary']['total_issues'] += count($fileIssues);

                foreach ($fileIssues as $issue) {
                    if ($issue['type'] === 'script') {
                        $results['summary']['inline_scripts']++;
                    } else {
                        $results['summary']['inline_styles']++;
                    }
                }
            }
        }

        $results['compliant'] = $results['summary']['total_issues'] === 0;

        return $results;
    }

    /**
     * 単一ファイルをスキャン
     *
     * @param  string  $filePath  ファイルパス
     * @return array 検出された問題
     */
    public function scanFile(string $filePath): array
    {
        $issues = [];

        if (! File::exists($filePath)) {
            return $issues;
        }

        $content = File::get($filePath);
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            // スクリプトタグをチェック
            if (preg_match($this->inlinePatterns['script'], $line)) {
                if (! $this->isCspCompliant($line, 'script') && ! $this->isExternalResource($line, 'script')) {
                    $issues[] = [
                        'type' => 'script',
                        'line' => $lineNumber + 1,
                        'content' => trim($line),
                        'suggestion' => '<script @cspNonce>',
                    ];
                }
            }

            // スタイルタグをチェック
            if (preg_match($this->inlinePatterns['style'], $line)) {
                if (! $this->isCspCompliant($line, 'style') && ! $this->isExternalResource($line, 'style')) {
                    $issues[] = [
                        'type' => 'style',
                        'line' => $lineNumber + 1,
                        'content' => trim($line),
                        'suggestion' => '<style @cspNonce>',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * CSP対応済みかチェック
     *
     * @param  string  $line  行内容
     * @param  string  $type  タイプ（script/style）
     */
    protected function isCspCompliant(string $line, string $type): bool
    {
        foreach ($this->cspCompliantPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 外部リソースかチェック
     *
     * @param  string  $line  行内容
     * @param  string  $type  タイプ（script/style）
     */
    protected function isExternalResource(string $line, string $type): bool
    {
        if ($type === 'script') {
            return preg_match('/<script\s+[^>]*src\s*=/i', $line) === 1;
        }

        return false;
    }

    /**
     * プラグインのCSP対応状況を診断
     *
     * @param  string  $pluginPath  プラグインのパス
     * @return array 診断結果
     */
    public function diagnosePlugin(string $pluginPath): array
    {
        $viewsPath = $pluginPath.'/resources/views';

        if (! File::isDirectory($viewsPath)) {
            return [
                'compliant' => true,
                'issues' => [],
                'summary' => [
                    'total_files' => 0,
                    'files_with_issues' => 0,
                    'total_issues' => 0,
                    'inline_scripts' => 0,
                    'inline_styles' => 0,
                ],
                'message' => 'No views directory found',
            ];
        }

        return $this->scanDirectory($viewsPath);
    }

    /**
     * テーマのCSP対応状況を診断
     *
     * @param  string  $themePath  テーマのパス
     * @return array 診断結果
     */
    public function diagnoseTheme(string $themePath): array
    {
        $viewsPath = $themePath.'/resources/views';

        if (! File::isDirectory($viewsPath)) {
            return [
                'compliant' => true,
                'issues' => [],
                'summary' => [
                    'total_files' => 0,
                    'files_with_issues' => 0,
                    'total_issues' => 0,
                    'inline_scripts' => 0,
                    'inline_styles' => 0,
                ],
                'message' => 'No views directory found',
            ];
        }

        return $this->scanDirectory($viewsPath);
    }

    /**
     * 健全度スコアに影響するかどうかを判定
     *
     * @param  array  $diagnosticResult  診断結果
     * @return array 健全度への影響
     */
    public function getHealthImpact(array $diagnosticResult): array
    {
        if ($diagnosticResult['compliant']) {
            return [
                'level' => 'good',
                'message' => 'csp_compliant',
                'details' => null,
            ];
        }

        $totalIssues = $diagnosticResult['summary']['total_issues'] ?? 0;

        if ($totalIssues <= 2) {
            return [
                'level' => 'warning',
                'message' => 'csp_minor_issues',
                'details' => [
                    'count' => $totalIssues,
                    'scripts' => $diagnosticResult['summary']['inline_scripts'] ?? 0,
                    'styles' => $diagnosticResult['summary']['inline_styles'] ?? 0,
                ],
            ];
        }

        return [
            'level' => 'danger',
            'message' => 'csp_major_issues',
            'details' => [
                'count' => $totalIssues,
                'scripts' => $diagnosticResult['summary']['inline_scripts'] ?? 0,
                'styles' => $diagnosticResult['summary']['inline_styles'] ?? 0,
            ],
        ];
    }
}
