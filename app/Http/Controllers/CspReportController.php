<?php
/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\SecuritySetting;

/**
 * CSP Report Controller
 * 
 * CSP違反レポートを受信・処理するコントローラー。
 * ブラウザからのCSP違反レポートを受け取り、ログに記録する。
 */
class CspReportController extends Controller
{
    /**
     * 開発ツール関連の除外パターン
     * Vite開発サーバー、Windsurf/MCPブラウザプレビュー等
     */
    protected array $devToolPatterns = [
        // Windsurf/MCP browser logger
        'browser-logger',
        'browser-logger-active',
        '_boost',
        // Vite開発サーバー
        '@vite',
        'vite/client',
        '@react-refresh',
        'hot-update',
        ':5173',            // Vite開発サーバーのデフォルトポート
        'node_modules/.vite',
        'node_modules/vite',
        // その他の開発ツール
        'webpack-dev-server',
        '__webpack_hmr',
        'livereload',
        'browser-sync',
    ];

    /**
     * CSP違反レポートを受信
     */
    public function report(Request $request): JsonResponse
    {
        // ログ記録が無効な場合は何もしない
        if (!config('csp.log_violations', true)) {
            return response()->json(['status' => 'ignored']);
        }

        // レポートデータを取得
        $report = $this->parseReport($request);

        if (empty($report)) {
            return response()->json(['status' => 'empty']);
        }

        // 開発ツール関連の違反を除外
        if ($this->isDevToolViolation($report)) {
            return response()->json(['status' => 'excluded_dev_tool']);
        }

        // ログに記録
        $this->logViolation($report, $request);

        return response()->json(['status' => 'received']);
    }

    /**
     * 開発ツール関連の違反かどうかを判定
     */
    protected function isDevToolViolation(array $report): bool
    {
        // 設定で除外が無効な場合はfalse
        try {
            $excludeDevTools = SecuritySetting::get('csp_exclude_dev_tools', true);
            if (!$excludeDevTools) {
                return false;
            }
        } catch (\Exception $e) {
            // データベース未設定時はデフォルトで除外
        }

        // チェック対象のフィールド
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

        // ローカル環境でのインラインスクリプト違反を除外
        // Windsurf/MCPが注入するスクリプトはblocked_uri=inlineで報告される
        if ($this->isLocalDevInlineViolation($report)) {
            return true;
        }
        return false;
    }

    /**
     * 開発環境でのインラインスクリプト違反かどうかを判定
     * Windsurf/MCPが注入するbrowser-loggerスクリプト等を検出
     */
    protected function isLocalDevInlineViolation(array $report): bool
    {
        // 本番環境ではfalse（ローカル・ステージングのみ対象）
        if (app()->environment('production')) {
            return false;
        }

        $blockedUri = $report['blocked-uri'] ?? $report['blockedURL'] ?? '';
        $directive = $report['violated-directive'] ?? $report['effectiveDirective'] ?? '';
        $sourceFile = $report['source-file'] ?? $report['sourceFile'] ?? '';

        // インラインスクリプト違反かどうか
        if ($blockedUri !== 'inline') {
            return false;
        }

        // script-src関連の違反のみ対象
        if (!str_contains($directive, 'script-src')) {
            return false;
        }

        // source-fileが開発ツール関連のパターンに一致する場合は除外
        foreach ($this->devToolPatterns as $pattern) {
            if (stripos($sourceFile, $pattern) !== false) {
                return true;
            }
        }

        // 開発環境（local/staging）では、インラインスクリプト違反を除外
        // （MCP/Windsurf等の開発ツールが注入するスクリプトを考慮）
        // 本番環境では正常にログに記録される
        // 注: CSP標準モードのテストのため、一時的に無効化
        return false; // 元の値: true
    }

    /**
     * レポートをパース
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

        // CSP Report形式（csp-report キー）
        if (isset($data['csp-report'])) {
            return $data['csp-report'];
        }

        // Reporting API形式（配列）
        if (is_array($data) && isset($data[0]['body'])) {
            return $data[0]['body'];
        }

        return $data;
    }

    /**
     * 違反をログに記録
     */
    protected function logViolation(array $report, Request $request): void
    {
        $channel = config('csp.log_channel', 'csp');

        // 重要な情報を抽出
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

        // nullの項目を除去
        $logData = array_filter($logData, fn($v) => $v !== null);

        // ログレベルを決定（eval等の危険な違反は警告レベルを上げる）
        $level = $this->determineLogLevel($logData);

        Log::channel($channel)->log($level, 'CSP Violation', $logData);
    }

    /**
     * ログレベルを決定
     */
    protected function determineLogLevel(array $logData): string
    {
        $directive = $logData['violated_directive'] ?? '';
        $blockedUri = $logData['blocked_uri'] ?? '';

        // 危険な違反パターン
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

        // script-src違反は警告レベル
        if (str_contains($directive, 'script-src')) {
            return 'warning';
        }

        // その他は情報レベル
        return 'info';
    }
}
