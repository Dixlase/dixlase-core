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

namespace App\Services\Csp;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * CSP diagnostic service
 *
 * Scans Blade files in themes and plugins to
 * detect inline scripts/styles that are not CSP-compliant
 */
class CspDiagnosticService
{
    /**
     * CSP-compliant patterns (these are skipped)
     */
    protected array $cspCompliantPatterns = [
        '/<script\s+[^>]*@cspNonce[^>]*>/i',
        '/<script\s+[^>]*nonce\s*=/i',
        '/<style\s+[^>]*@cspNonce[^>]*>/i',
        '/<style\s+[^>]*nonce\s*=/i',
    ];

    /**
     * External script/style patterns (these are skipped)
     */
    protected array $externalResourcePatterns = [
        '/<script\s+[^>]*src\s*=/i',
        '/<link\s+[^>]*href\s*=/i',
    ];

    /**
     * Inline script/style patterns (detection targets)
     */
    protected array $inlinePatterns = [
        'script' => '/<script(?:\s+[^>]*)?>(?!<\/script>)/i',
        'style' => '/<style(?:\s+[^>]*)?>(?!<\/style>)/i',
    ];

    /**
     * Scan directory to detect CSP issues
     *
     * @param  string  $directory  Directory to scan
     * @return array Detection results
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

        // Recursively retrieve Blade files
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
     * Scan a single file
     *
     * @param  string  $filePath  File path
     * @return array Detected issues
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
            // Check script tags
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

            // Check style tags
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
     * Check if CSP-compliant
     *
     * @param  string  $line  Line content
     * @param  string  $type  Type (script/style)
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
     * Check if external resource
     *
     * @param  string  $line  Line content
     * @param  string  $type  Type (script/style)
     */
    protected function isExternalResource(string $line, string $type): bool
    {
        if ($type === 'script') {
            return preg_match('/<script\s+[^>]*src\s*=/i', $line) === 1;
        }

        return false;
    }

    /**
     * Diagnose CSP compatibility of plugin
     *
     * @param  string  $pluginPath  Plugin path
     * @return array Diagnostic result
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
     * Diagnose CSP compatibility of theme
     *
     * @param  string  $themePath  Theme path
     * @return array Diagnostic result
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
     * Determine whether it affects the health score
     *
     * @param  array  $diagnosticResult  Diagnostic result
     * @return array Impact on health
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
