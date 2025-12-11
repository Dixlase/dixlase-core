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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminSystemLogsController extends AdminLoggedInController
{
    protected $logPaths = [
        'activity' => 'admin_activity.log',
        'error'    => 'admin_error.log',
        'dixlase'  => 'dixlase.log',
        'front_activity' => 'front_activity.log',
        'front_error' => 'front_error.log',
        'csp' => 'csp_violations.log',
        'audit' => 'audit.log',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * システムログ
     */
    public function index(Request $request, $type = 'activity')
    {
        // 監査ログの場合は特別処理
        if ($type === 'audit') {
            $view = $request->input('view', 'db');
            
            if ($view === 'db') {
                return $this->auditLogsDb($request);
            }
        }

        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];
        $filePath = storage_path("logs/{$fileName}");

        // dailyドライバーの場合、日付付きファイル名を探す
        if (!File::exists($filePath)) {
            $filePath = $this->findDailyLogFile($fileName);
        }

        $this->viewParams['logType'] = $type;

        if (!$filePath || !File::exists($filePath)) {
            $this->viewParams['logs'] = [[
                'timestamp' => '',
                'level' => '',
                'message' => __('admin.settings.systems.logs.messages.file_not_found', ['filename' => $fileName]),
                'context' => [],
                'parsed' => false
            ]];
            $this->viewParams['pagination'] = [
                'current_page' => 1,
                'per_page' => 50,
                'total' => 1,
                'last_page' => 1,
                'from' => 1,
                'to' => 1,
                'has_more_pages' => false,
                'prev_page' => null,
                'next_page' => null,
            ];
            $this->viewParams['logTypes'] = array_keys($this->logPaths);

            return response()->view('admin::settings.systems.logs', $this->viewParams);
        }

        $perPage = $request->input('per_page', 50);
        $allowedPerPage = [25, 50, 100, 200];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 50;
        }
        
        $currentPage = $request->get('page', 1);
        $maxLinesToRead = 5000;
        $lines = $this->readLogFileLines($filePath, $maxLinesToRead);

        $parsedLogs = [];
        foreach ($lines as $line) {
            $parsedLog = $this->parseLogLine($line);
            if ($parsedLog) {
                $parsedLogs[] = $parsedLog;
            }
        }

        $offset = ($currentPage - 1) * $perPage;
        $totalLogs = count($parsedLogs);
        $paginatedLogs = array_slice($parsedLogs, $offset, $perPage);

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalLogs,
            'last_page' => ceil($totalLogs / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $totalLogs),
            'has_more_pages' => $currentPage < ceil($totalLogs / $perPage),
            'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < ceil($totalLogs / $perPage) ? $currentPage + 1 : null,
        ];

        $this->viewParams['logs'] = $paginatedLogs;
        $this->viewParams['pagination'] = $pagination;
        $this->viewParams['logTypes'] = array_keys($this->logPaths);

        return view('admin::settings.systems.logs', $this->viewParams);
    }

    /**
     * ログファイルダウンロード
     */
    public function download(Request $request, $type = 'activity')
    {
        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];
        $filePath = storage_path("logs/{$fileName}");

        if (!File::exists($filePath)) {
            $filePath = $this->findDailyLogFile($fileName);
        }

        if (!$filePath || !File::exists($filePath)) {
            return redirect()->route('admin.settings.systems.logs', ['type' => $type])
                ->with('error', __('admin.settings.systems.logs.messages.download_error', ['filename' => $fileName]));
        }

        $downloadFileName = basename($filePath);
        return response()->download($filePath, $downloadFileName);
    }

    /**
     * ログ内容消去
     */
    public function clear(Request $request, $type = 'activity')
    {
        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];
        $filePath = storage_path("logs/{$fileName}");

        if (!File::exists($filePath)) {
            $filePath = $this->findDailyLogFile($fileName);
        }

        try {
            if ($filePath && File::exists($filePath)) {
                File::put($filePath, '');
                $clearedFileName = basename($filePath);
                return redirect()->route('admin.settings.systems.logs', ['type' => $type])
                    ->with('success', __('admin.settings.systems.logs.messages.clear_success', ['filename' => $clearedFileName]));
            } else {
                return redirect()->route('admin.settings.systems.logs', ['type' => $type])
                    ->with('error', __('admin.settings.systems.logs.messages.clear_error', ['filename' => $fileName]));
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.systems.logs', ['type' => $type])
                ->with('error', __('admin.settings.systems.logs.messages.clear_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * ログ出力テスト
     */
    public function test(Request $request)
    {
        $type = $request->input('type', 'all');
        $results = [];

        try {
            if ($type === 'all' || $type === 'dixlase') {
                Log::info('ログ出力テスト - 通常ログ', [
                    'test_type' => 'dixlase_log',
                    'timestamp' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                ]);
                $results['dixlase'] = 'success';
            }

            if ($type === 'all' || $type === 'activity') {
                Log::channel('admin_activity')->info('ログ出力テスト - アクティビティログ', [
                    'test_type' => 'activity_log',
                    'timestamp' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'action' => 'log_test',
                ]);
                $results['activity'] = 'success';
            }

            if ($type === 'all' || $type === 'error') {
                Log::channel('admin_error')->error('ログ出力テスト - エラーログ', [
                    'test_type' => 'error_log',
                    'timestamp' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'error_message' => 'これはテスト用のエラーメッセージです',
                ]);
                $results['error'] = 'success';
            }

            $message = __('admin.settings.systems.logs.test_success', ['results' => implode(', ', array_keys($results))]);
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('ログ出力テストでエラーが発生しました', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()->with('error', 'ログ出力テストでエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 意図的にエラーを発生させてadmin_error.logに出力をテスト
     */
    public function testError(Request $request)
    {
        $errorType = $request->input('error_type', 'exception');

        try {
            switch ($errorType) {
                case 'exception':
                    throw new \Exception('テスト用の例外エラーです - admin_error.logに記録されるかテスト中');
                    
                case 'database':
                    DB::table('non_existent_table')->get();
                    break;
                    
                case 'file':
                    $content = file_get_contents('/path/to/non/existent/file.txt');
                    break;
                    
                case 'division':
                    $result = 10 / 0;
                    break;
                    
                case 'manual_log':
                    Log::channel('admin_error')->error('手動エラーログテスト', [
                        'test_type' => 'manual_error_test',
                        'timestamp' => now()->toDateTimeString(),
                        'user_id' => auth()->id(),
                        'user_name' => auth()->user()->name,
                        'error_details' => 'これは手動で記録したテスト用エラーです',
                        'ip' => request()->ip(),
                    ]);
                    
                    return redirect()->back()->with('success', '手動エラーログをadmin_error.logに記録しました');
                    
                default:
                    throw new \InvalidArgumentException('無効なエラータイプです: ' . $errorType);
            }
            
        } catch (\Exception $e) {
            Log::channel('admin_error')->error('テストエラーが発生しました', [
                'error_type' => $errorType,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'timestamp' => now()->toDateTimeString(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()->with('success', 'エラーが発生し、admin_error.logに記録されました: ' . $e->getMessage());
        }
    }

    /**
     * フロントログをテスト
     */
    public function testFront(Request $request)
    {
        try {
            Log::channel('front_activity')->info('フロント操作テスト', [
                'action' => 'テスト操作',
                'user_id' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => 'http://localhost/test',
                'method' => 'GET',
                'timestamp' => now()->toDateTimeString(),
                'test_type' => 'manual_front_test',
                'admin_user' => auth()->user()->name,
                'details' => [
                    'page' => 'テストページ',
                    'form_type' => 'お問い合わせ',
                    'search_query' => 'テスト検索',
                ]
            ]);

            return redirect()->back()->with('success', 'フロント操作ログをfront_activity.logに記録しました');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'フロントログテストでエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * フロントエラーログをテスト
     */
    public function testFrontError(Request $request)
    {
        try {
            Log::channel('front_error')->error('フロントエラーテスト', [
                'error' => 'テストエラー',
                'user_id' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => 'http://localhost/test-error',
                'method' => 'POST',
                'timestamp' => now()->toDateTimeString(),
                'test_type' => 'manual_front_error_test',
                'admin_user' => auth()->user()->name,
                'context' => [
                    'error_type' => 'validation_error',
                    'form_data' => ['email' => 'invalid-email', 'name' => ''],
                    'database_error' => false,
                    'api_error' => false,
                ]
            ]);

            return redirect()->back()->with('success', 'フロントエラーログをfront_error.logに記録しました');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'フロントエラーログテストでエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 監査ログDB表示
     */
    protected function auditLogsDb(Request $request)
    {
        $this->viewParams['logType'] = 'audit';
        $this->viewParams['auditView'] = 'db';

        if (!Schema::hasTable('audit_logs')) {
            $this->viewParams['auditLogs'] = collect();
            $this->viewParams['categories'] = [];
            $this->viewParams['actions'] = [];
            $this->viewParams['severities'] = [];
            $this->viewParams['outcomes'] = [];
            $this->viewParams['tableExists'] = false;
            return view('admin::settings.systems.logs-audit', $this->viewParams);
        }

        $query = AuditLog::query()->orderByDesc('occurred_at');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('outcome')) {
            $query->where('outcome', $request->outcome);
        }
        if ($request->filled('actor_name')) {
            $query->where('actor_name', 'like', '%' . $request->actor_name . '%');
        }
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }
        if ($request->filled('plugin_name')) {
            $query->where('plugin_name', $request->plugin_name);
        }
        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', $request->date_from . ' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', '%' . $search . '%')
                  ->orWhere('target_label', 'like', '%' . $search . '%')
                  ->orWhere('action', 'like', '%' . $search . '%')
                  ->orWhere('ip_address', 'like', '%' . $search . '%');
            });
        }

        $perPage = $request->input('per_page', 50);
        $allowedPerPage = [25, 50, 100, 200];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 50;
        }

        $this->viewParams['auditLogs'] = $query->paginate($perPage)->withQueryString();
        $this->viewParams['categories'] = AuditLog::distinct()->pluck('category')->filter()->sort()->values()->toArray();
        $this->viewParams['actions'] = AuditLog::distinct()->pluck('action')->filter()->sort()->values()->toArray();
        $this->viewParams['severities'] = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        $this->viewParams['outcomes'] = ['success', 'failure', 'denied', 'pending', 'unknown'];
        $this->viewParams['tableExists'] = true;

        return view('admin::settings.systems.logs-audit', $this->viewParams);
    }

    /**
     * 監査ログ詳細表示
     */
    public function auditShow(Request $request, $id)
    {
        $this->viewParams['logType'] = 'audit';
        $this->viewParams['auditView'] = 'db';

        if (!Schema::hasTable('audit_logs')) {
            return redirect()->route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db'])
                ->with('error', __('admin.settings.audit_logs.table_not_exists'));
        }

        $auditLog = AuditLog::findOrFail($id);
        
        $relatedLogs = collect();
        if ($auditLog->request_id) {
            $relatedLogs = AuditLog::where('request_id', $auditLog->request_id)
                ->where('id', '!=', $auditLog->id)
                ->orderBy('occurred_at')
                ->get();
        }

        $this->viewParams['auditLog'] = $auditLog;
        $this->viewParams['relatedLogs'] = $relatedLogs;

        return view('admin::settings.systems.logs-audit-show', $this->viewParams);
    }

    /**
     * 監査ログCSVエクスポート
     */
    public function auditExport(Request $request)
    {
        if (!Schema::hasTable('audit_logs')) {
            return redirect()->route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db'])
                ->with('error', __('admin.settings.audit_logs.table_not_exists'));
        }

        $query = AuditLog::query()->orderByDesc('occurred_at');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('outcome')) {
            $query->where('outcome', $request->outcome);
        }
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
        }
        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', $request->date_from . ' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', '%' . $search . '%')
                  ->orWhere('target_label', 'like', '%' . $search . '%')
                  ->orWhere('action', 'like', '%' . $search . '%')
                  ->orWhere('ip_address', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->limit(10000)->get();

        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'ID', 'Category', 'Action', 'Severity', 'Outcome', 
                'Actor Type', 'Actor ID', 'Actor Name',
                'Target Type', 'Target ID', 'Target Label',
                'IP Address', 'User Agent', 'Plugin', 'Request ID',
                'Message', 'Occurred At'
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->category,
                    $log->action,
                    $log->severity,
                    $log->outcome,
                    $log->actor_type,
                    $log->actor_id,
                    $log->actor_name,
                    $log->target_type,
                    $log->target_id,
                    $log->target_label,
                    $log->ip_address,
                    $log->user_agent,
                    $log->plugin_name,
                    $log->request_id,
                    $log->message,
                    $log->occurred_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 監査ログクリーンアップ
     */
    public function auditCleanup(Request $request)
    {
        if (!Schema::hasTable('audit_logs')) {
            return redirect()->route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db'])
                ->with('error', __('admin.settings.audit_logs.table_not_exists'));
        }

        $days = (int) $request->input('days', 90);
        
        if ($days === 0) {
            $count = AuditLog::count();
            AuditLog::truncate();
        } else {
            $cutoff = now()->subDays($days);
            $count = AuditLog::where('occurred_at', '<', $cutoff)->count();
            AuditLog::where('occurred_at', '<', $cutoff)->delete();
        }

        return redirect()->route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db'])
            ->with('success', __('admin.settings.audit_logs.cleanup_success', ['count' => $count]));
    }

    /**
     * dailyドライバーの日付付きログファイルを探す
     */
    protected function findDailyLogFile(string $baseFileName): ?string
    {
        $logsPath = storage_path('logs');
        $baseName = pathinfo($baseFileName, PATHINFO_FILENAME);
        $extension = pathinfo($baseFileName, PATHINFO_EXTENSION);
        
        for ($i = 0; $i < 30; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyFileName = "{$baseName}-{$date}.{$extension}";
            $dailyFilePath = "{$logsPath}/{$dailyFileName}";
            
            if (File::exists($dailyFilePath)) {
                return $dailyFilePath;
            }
        }
        
        return null;
    }

    /**
     * ログファイルから末尾の行を効率的に読み込む
     */
    protected function readLogFileLines(string $filePath, int $maxLines = 5000): array
    {
        $fileSize = filesize($filePath);
        
        if ($fileSize < 1024 * 1024) {
            $lines = array_reverse(
                array_filter(
                    explode("\n", File::get($filePath)),
                    fn($line) => trim($line) !== ''
                )
            );
            return array_slice($lines, 0, $maxLines);
        }

        $lines = [];
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            return [];
        }

        $bufferSize = 8192;
        $buffer = '';
        $position = $fileSize;

        while ($position > 0 && count($lines) < $maxLines) {
            $readSize = min($bufferSize, $position);
            $position -= $readSize;
            
            fseek($handle, $position);
            $chunk = fread($handle, $readSize);
            $buffer = $chunk . $buffer;
            
            $bufferLines = explode("\n", $buffer);
            $buffer = array_shift($bufferLines);
            
            foreach (array_reverse($bufferLines) as $line) {
                if (trim($line) !== '' && count($lines) < $maxLines) {
                    $lines[] = $line;
                }
            }
        }

        if (trim($buffer) !== '' && count($lines) < $maxLines) {
            $lines[] = $buffer;
        }

        fclose($handle);
        
        return $lines;
    }

    /**
     * Parse a log line into structured data
     */
    private function parseLogLine($line)
    {
        $pattern = '/^\[([^\]]+)\]\s+([^:]+):\s+(.+?)(\s+\{.*\})?$/';
        
        if (!preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => '',
                'level' => '',
                'message' => $line,
                'context' => [],
                'parsed' => false
            ];
        }

        $timestamp = $matches[1];
        $level = $matches[2];
        $message = $matches[3];
        $contextJson = isset($matches[4]) ? trim($matches[4]) : '';

        $context = [];
        if (!empty($contextJson)) {
            try {
                $context = json_decode($contextJson, true) ?? [];
            } catch (\Exception $e) {
                $context = [];
            }
        }

        return [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'parsed' => true
        ];
    }
}
