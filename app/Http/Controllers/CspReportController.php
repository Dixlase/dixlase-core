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

namespace App\Http\Controllers;

use App\Models\SecuritySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * CSP Report Controller
 *
 * Controller for receiving and processing CSP violation reports
 * Receives CSP violation reports from browsers and logs them
 */
class CspReportController extends Controller
{
    /**
     * Exclusion patterns for development tools
     * Vite dev server, Windsurf/MCP browser preview, etc.
     */
    protected array $devToolPatterns = [
        // Windsurf/MCP browser logger
        'browser-logger',
        'browser-logger-active',
        '_boost',
        // Vite dev server
        '@vite',
        'vite/client',
        '@react-refresh',
        'hot-update',
        ':5173',            // Default port for Vite dev server
        'node_modules/.vite',
        'node_modules/vite',
        // Other development tools
        'webpack-dev-server',
        '__webpack_hmr',
        'livereload',
        'browser-sync',
    ];

    /**
     * Receive CSP violation report
     */
    public function report(Request $request): JsonResponse
    {
        // Do nothing if logging is disabled
        if (! config('csp.log_violations', true)) {
            return response()->json(['status' => 'ignored']);
        }

        // Get report data
        $report = $this->parseReport($request);

        if (empty($report)) {
            return response()->json(['status' => 'empty']);
        }

        // Exclude development tool related violations
        if ($this->isDevToolViolation($report)) {
            return response()->json(['status' => 'excluded_dev_tool']);
        }

        // Log to file
        $this->logViolation($report, $request);

        return response()->json(['status' => 'received']);
    }

    /**
     * Determine if violation is related to development tools
     */
    protected function isDevToolViolation(array $report): bool
    {
        // Return false if exclusion is disabled in settings
        try {
            $excludeDevTools = SecuritySetting::get('csp_exclude_dev_tools', true);
            if (! $excludeDevTools) {
                return false;
            }
        } catch (\Exception $e) {
            // Exclude by default when database is not configured
        }

        // Fields to check
        $fieldsToCheck = [
            $report['blocked-uri'] ?? $report['blockedURL'] ?? '',
            $report['source-file'] ?? $report['sourceFile'] ?? '',
            $report['script-sample'] ?? '',
        ];

        foreach ($fieldsToCheck as $field) {
            if (empty($field)) {
                continue;
            }

            foreach ($this->devToolPatterns as $pattern) {
                if (stripos($field, $pattern) !== false) {
                    return true;
                }
            }
        }

        // Exclude inline script violations in local environment
        // Scripts injected by Windsurf/MCP are reported as blocked_uri=inline
        if ($this->isLocalDevInlineViolation($report)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if violation is an inline script violation in development environment
     * Detect browser-logger scripts injected by Windsurf/MCP, etc.
     */
    protected function isLocalDevInlineViolation(array $report): bool
    {
        // False in production (local and staging only)
        if (app()->environment('production')) {
            return false;
        }

        $blockedUri = $report['blocked-uri'] ?? $report['blockedURL'] ?? '';
        $directive = $report['violated-directive'] ?? $report['effectiveDirective'] ?? '';
        $sourceFile = $report['source-file'] ?? $report['sourceFile'] ?? '';

        // Whether it is an inline script violation
        if ($blockedUri !== 'inline') {
            return false;
        }

        // Only target script-src related violations
        if (! str_contains($directive, 'script-src')) {
            return false;
        }

        // Exclude only if source-file matches development tool related patterns
        foreach ($this->devToolPatterns as $pattern) {
            if (stripos($sourceFile, $pattern) !== false) {
                return true;
            }
        }

        // Record inline violations other than development tool related ones
        return false;
    }

    /**
     * Parse report
     */
    protected function parseReport(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::channel(config('csp.log_channel', 'csp'))->warning('CSP Report: Invalid JSON received', [
                'content' => substr($content, 0, 500),
            ]);

            return [];
        }

        // CSP Report format (csp-report key)
        if (isset($data['csp-report'])) {
            return $data['csp-report'];
        }

        // Reporting API format (array)
        if (is_array($data) && isset($data[0]['body'])) {
            return $data[0]['body'];
        }

        return $data;
    }

    /**
     * Log violation
     */
    protected function logViolation(array $report, Request $request): void
    {
        $channel = config('csp.log_channel', 'csp');

        // Extract important information
        $logData = [
            'violated_directive' => $report['violated-directive'] ?? $report['effectiveDirective'] ?? 'unknown',
            'blocked_uri' => $report['blocked-uri'] ?? $report['blockedURL'] ?? 'unknown',
            'document_uri' => $report['document-uri'] ?? $report['documentURL'] ?? 'unknown',
            'source_file' => $report['source-file'] ?? $report['sourceFile'] ?? null,
            'line_number' => $report['line-number'] ?? $report['lineNumber'] ?? null,
            'column_number' => $report['column-number'] ?? $report['columnNumber'] ?? null,
            'original_policy' => $report['original-policy'] ?? $report['originalPolicy'] ?? null,
            'disposition' => $report['disposition'] ?? null,
            'status_code' => $report['status-code'] ?? $report['statusCode'] ?? null,
            'referrer' => $report['referrer'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Remove null items
        $logData = array_filter($logData, fn ($v) => $v !== null);

        // Determine log level (raise warning level for dangerous violations such as eval)
        $level = $this->determineLogLevel($logData);

        Log::channel($channel)->log($level, 'CSP Violation', $logData);
    }

    /**
     * Determine log level
     */
    protected function determineLogLevel(array $logData): string
    {
        $directive = $logData['violated_directive'] ?? '';
        $blockedUri = $logData['blocked_uri'] ?? '';

        // Dangerous violation patterns
        $criticalPatterns = [
            'eval',
            'unsafe-inline',
            'data:',
        ];

        foreach ($criticalPatterns as $pattern) {
            if (str_contains($directive, $pattern) || str_contains($blockedUri, $pattern)) {
                return 'error';
            }
        }

        // script-src violations are warning level
        if (str_contains($directive, 'script-src')) {
            return 'warning';
        }

        // Others are info level
        return 'info';
    }
}
