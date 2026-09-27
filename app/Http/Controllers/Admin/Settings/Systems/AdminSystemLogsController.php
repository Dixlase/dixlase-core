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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Enums\LogLevel;
use App\Enums\MemberRole;
use App\Facades\Audit;
use App\Helpers\AdminHelper;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminSystemLogsController extends AdminLoggedInController
{
    protected $logPaths = [
        'activity' => 'admin_activity.log',
        'error' => 'admin_error.log',
        'dixlase' => 'dixlase.log',
        'front_activity' => 'front_activity.log',
        'front_error' => 'front_error.log',
        'browser' => 'browser.log',
        'csp' => 'csp_violations.log',
        'audit' => 'audit.log',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * File log
     */
    public function files(Request $request, $type = 'activity')
    {
        // File log access is not allowed in simple mode
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.settings.systems.logs.index');
        }

        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];
        $selectedDate = $request->input('date');

        // Get list of available dates
        $availableDates = $this->getAvailableLogDates($fileName);
        $this->viewParams['availableDates'] = $availableDates;
        $this->viewParams['selectedDate'] = $selectedDate;

        // Use the file for the specified date if a date is provided
        if ($selectedDate) {
            $filePath = $this->getLogFilePathByDate($fileName, $selectedDate);
        } else {
            $filePath = storage_path("logs/{$fileName}");
            // For daily driver, look for dated file names
            if (! File::exists($filePath)) {
                $filePath = $this->findDailyLogFile($fileName);
            }
        }

        $this->viewParams['logType'] = $type;

        if (! $filePath || ! File::exists($filePath)) {
            $this->viewParams['logs'] = [[
                'timestamp' => '',
                'level' => '',
                'message' => __('admin/settings/systems/logs/files.messages.file_not_found', ['filename' => $fileName]),
                'context' => [],
                'parsed' => false,
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
            $this->viewParams['levelFilters'] = ['error', 'warning', 'normal', 'debug'];
            $this->viewParams['availableLevelFilters'] = [
                'error' => __('admin/settings/systems/logs/files.level_filter.error'),
                'warning' => __('admin/settings/systems/logs/files.level_filter.warning'),
                'normal' => __('admin/settings/systems/logs/files.level_filter.normal'),
                'debug' => __('admin/settings/systems/logs/files.level_filter.debug'),
            ];
            $this->viewParams['tableExists'] = true; // Always true for file logs
            $this->addNavigationData($type);

            return response()->view('admin::settings.systems.logs.files', $this->viewParams);
        }

        $perPage = $request->input('per_page', 50);
        $allowedPerPage = [25, 50, 100, 200];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 50;
        }

        // Log level filter (multiple selection allowed)
        $levelFilters = $request->input('levels', ['error', 'warning', 'normal', 'debug']);
        if (! is_array($levelFilters)) {
            $levelFilters = [$levelFilters];
        }
        $this->viewParams['levelFilters'] = $levelFilters;
        $this->viewParams['availableLevelFilters'] = [
            'error' => __('admin/settings/systems/logs/files.level_filter.error'),
            'warning' => __('admin/settings/systems/logs/files.level_filter.warning'),
            'normal' => __('admin/settings/systems/logs/files.level_filter.normal'),
            'debug' => __('admin/settings/systems/logs/files.level_filter.debug'),
        ];

        $currentPage = $request->get('page', 1);
        $maxLinesToRead = 5000;
        $lines = $this->readLogFileLines($filePath, $maxLinesToRead);

        $parsedLogs = [];

        foreach ($lines as $line) {
            $parsedLog = $this->parseLogLine($line);
            if ($parsedLog) {
                // Log level filtering
                $logLevel = strtolower($parsedLog['level'] ?? '');
                $shouldInclude = false;

                foreach ($levelFilters as $filter) {
                    if (LogLevel::levelBelongsToGroup($logLevel, $filter)) {
                        $shouldInclude = true;
                        break;
                    }
                }

                if ($shouldInclude) {
                    $parsedLogs[] = $parsedLog;
                }
            }
        }

        $offset = ($currentPage - 1) * $perPage;
        $totalLogs = count($parsedLogs);
        $paginatedLogs = array_slice($parsedLogs, $offset, $perPage);

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $totalLogs,
            'last_page' => max(1, ceil($totalLogs / $perPage)),
            'from' => $totalLogs > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $totalLogs),
            'has_more_pages' => $currentPage < ceil($totalLogs / $perPage),
            'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < ceil($totalLogs / $perPage) ? $currentPage + 1 : null,
        ];

        // Pre-calculate level-based colors for each log entry
        foreach ($paginatedLogs as &$log) {
            if ($log['parsed']) {
                $log['levelColors'] = self::getLevelColors(strtolower($log['level'] ?? ''));
            }
        }
        unset($log);

        $this->viewParams['logs'] = $paginatedLogs;
        $this->viewParams['pagination'] = $pagination;
        $this->viewParams['logTypes'] = array_keys($this->logPaths);
        $this->addNavigationData($type);

        return view('admin::settings.systems.logs.files', $this->viewParams);
    }

    /**
     * Log file download
     */
    public function download(Request $request, $type = 'activity')
    {
        // File log access is not allowed in simple mode
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.settings.systems.logs.index');
        }

        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];
        $selectedDate = $request->input('date');

        // Use the file for the specified date if a date is provided
        if ($selectedDate) {
            $filePath = $this->getLogFilePathByDate($fileName, $selectedDate);
        } else {
            $filePath = storage_path("logs/{$fileName}");
            if (! File::exists($filePath)) {
                $filePath = $this->findDailyLogFile($fileName);
            }
        }

        if (! $filePath || ! File::exists($filePath)) {
            return redirect()->route('admin.settings.systems.logs.files', ['type' => $type])
                ->with('error', __('admin/settings/systems/logs/files.messages.download_error', ['filename' => $fileName]));
        }

        $downloadFileName = basename($filePath);

        return response()->download($filePath, $downloadFileName);
    }

    /**
     * Clear log contents
     */
    public function clear(Request $request, $type = 'activity')
    {
        // File log access is not allowed in simple mode
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.settings.systems.logs.index');
        }

        $days = (int) $request->input('days', 0);
        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];

        try {
            if ($days === 0) {
                // Delete all log files if 0 days
                $clearedCount = $this->clearAllLogFiles($fileName);

                return redirect()->route('admin.settings.systems.logs.files', ['type' => $type])
                    ->with('success', __('admin/settings/systems/logs/files.clear_all_success', ['count' => $clearedCount]));
            } else {
                // Delete log files older than specified days
                $clearedCount = $this->clearOldLogFiles($fileName, $days);

                return redirect()->route('admin.settings.systems.logs.files', ['type' => $type])
                    ->with('success', __('admin/settings/systems/logs/files.clear_old_success', ['days' => $days, 'count' => $clearedCount]));
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.systems.logs.files', ['type' => $type])
                ->with('error', __('admin/settings/systems/logs/files.clear_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Clear all log files
     */
    protected function clearAllLogFiles(string $fileName): int
    {
        $logsPath = storage_path('logs');
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $clearedCount = 0;

        // Clear files without dates
        $baseFilePath = "{$logsPath}/{$fileName}";
        if (File::exists($baseFilePath)) {
            File::put($baseFilePath, '');
            $clearedCount++;
        }

        // Clear dated files (past 90 days)
        for ($i = 0; $i < 90; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyFileName = "{$baseName}-{$date}.{$extension}";
            $dailyFilePath = "{$logsPath}/{$dailyFileName}";

            if (File::exists($dailyFilePath)) {
                File::put($dailyFilePath, '');
                $clearedCount++;
            }
        }

        return $clearedCount;
    }

    /**
     * Delete log files older than specified days
     */
    protected function clearOldLogFiles(string $fileName, int $days): int
    {
        $logsPath = storage_path('logs');
        $baseName = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $cutoffDate = now()->subDays($days);
        $clearedCount = 0;

        // Search and delete dated files (past 90 days)
        for ($i = $days; $i < 90; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyFileName = "{$baseName}-{$date}.{$extension}";
            $dailyFilePath = "{$logsPath}/{$dailyFileName}";

            if (File::exists($dailyFilePath)) {
                File::delete($dailyFilePath);
                $clearedCount++;
            }
        }

        return $clearedCount;
    }

    /**
     * Log output test
     */
    public function test(Request $request)
    {
        // File log operations are not allowed in simple mode
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.settings.systems.logs.index');
        }

        $type = $request->input('type', 'all');
        $results = [];

        try {
            if ($type === 'all' || $type === 'dixlase') {
                if ($request->input('test_type') === 'info') {
                    Log::info('Log output test - normal log');
                }
                $results['dixlase'] = 'success';
            }

            if ($type === 'all' || $type === 'activity') {
                Log::channel('admin_activity')->info(__('http/controllers/admin/settings/systems/admin_system_logs_controller.log_output_test_activity_log'), [
                    'test_type' => 'activity_log',
                    'timestamp' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'action' => 'log_test',
                ]);
                $results['activity'] = 'success';
            }

            if ($type === 'all' || $type === 'error') {
                Log::channel('admin_error')->error(__('http/controllers/admin/settings/systems/admin_system_logs_controller.log_output_test_error_log'), [
                    'test_type' => 'error_log',
                    'timestamp' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'error_message' => __('http/controllers/admin/settings/systems/admin_system_logs_controller.test_error_message'),
                ]);
                $results['error'] = 'success';
            }

            $message = __('admin/settings/systems/logs/files.test_success', ['results' => implode(', ', array_keys($results))]);

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Log output test encountered an error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', __('http/controllers/admin/settings/systems/admin_system_logs_controller.log_output_test_error_occurred').$e->getMessage());
        }
    }

    /**
     * Intentionally trigger an error to test output to admin_error.log
     */
    public function testError(Request $request)
    {
        // File log operations are not allowed in simple mode
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.settings.systems.logs.index');
        }

        $errorType = $request->input('error_type', 'exception');

        try {
            switch ($errorType) {
                case 'exception':
                    throw new \Exception('Test exception error - testing whether it gets logged to admin_error.log');
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
                    Log::channel('admin_error')->error(__('http/controllers/admin/settings/systems/admin_system_logs_controller.manual_error_log_test'), [
                        'test_type' => 'manual_error_test',
                        'timestamp' => now()->toDateTimeString(),
                        'user_id' => auth()->id(),
                        'user_name' => auth()->user()->name,
                        'error_details' => __('http/controllers/admin/settings/systems/admin_system_logs_controller.manually_recorded_test_error'),
                        'ip' => request()->ip(),
                    ]);

                    return redirect()->back()->with('success', __('http/controllers/admin/settings/systems/admin_system_logs_controller.manual_error_logged_to_admin'));

                default:
                    throw new \InvalidArgumentException('Invalid error type: '.$errorType);
            }
        } catch (\Exception $e) {
            Log::channel('admin_error')->error(__('http/controllers/admin/settings/systems/admin_system_logs_controller.test_error_occurred'), [
                'error_type' => $errorType,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'timestamp' => now()->toDateTimeString(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('success', __('http/controllers/admin/settings/systems/admin_system_logs_controller.error_recorded_to_admin_log').$e->getMessage());
        }
    }

    /**
     * Test front log
     */
    public function testFront(Request $request)
    {
        // File log operations are not allowed in simple mode
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.settings.systems.logs.index');
        }

        try {
            Log::channel('front_activity')->info(__('http/controllers/admin/settings/systems/admin_system_logs_controller.front_operation_test'), [
                'action' => __('http/controllers/admin/settings/systems/admin_system_logs_controller.test_operation'),
                'user_id' => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => 'http://localhost/test',
                'method' => 'GET',
                'timestamp' => now()->toDateTimeString(),
                'test_type' => 'manual_front_test',
                'admin_user' => auth()->user()->name,
                'details' => [
                    'page' => __('http/controllers/admin/settings/systems/admin_system_logs_controller.test_page'),
                    'form_type' => __('http/controllers/admin/settings/systems/admin_system_logs_controller.contact'),
                    'search_query' => __('http/controllers/admin/settings/systems/admin_system_logs_controller.test_search'),
                ],
            ]);

            return redirect()->back()->with('success', __('http/controllers/admin/settings/systems/admin_system_logs_controller.front_operation_logged_to_activity'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('http/controllers/admin/settings/systems/admin_system_logs_controller.front_log_test_error_occurred').$e->getMessage());
        }
    }

    /**
     * Test front error log
     */
    public function testFrontError(Request $request)
    {
        // File log operations are not allowed in simple mode
        if (AdminModeHelper::isSimpleMode()) {
            return redirect()->route('admin.settings.systems.logs.index');
        }

        try {
            Log::channel('front_error')->error(__('http/controllers/admin/settings/systems/admin_system_logs_controller.front_error_test'), [
                'error' => __('http/controllers/admin/settings/systems/admin_system_logs_controller.test_error'),
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
                ],
            ]);

            return redirect()->back()->with('success', __('http/controllers/admin/settings/systems/admin_system_logs_controller.front_error_logged_to_front'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('http/controllers/admin/settings/systems/admin_system_logs_controller.front_error_log_test_error_occurred').$e->getMessage());
        }
    }

    /**
     * Audit log list (database)
     */
    public function index(Request $request)
    {
        return $this->auditLogsDb($request);
    }

    /**
     * Display audit log DB
     */
    protected function auditLogsDb(Request $request)
    {
        $this->viewParams['logType'] = 'audit';
        $this->viewParams['auditView'] = 'db';

        if (! Schema::hasTable('audit_logs')) {
            $this->viewParams['auditLogs'] = collect();
            $this->viewParams['categories'] = [];
            $this->viewParams['actions'] = [];
            $this->viewParams['severities'] = [];
            $this->viewParams['outcomes'] = [];
            $this->viewParams['tableExists'] = false;

            return view('admin::settings.systems.logs.audit', $this->viewParams);
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
            $query->where('actor_name', 'like', '%'.$request->actor_name.'%');
        }
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%'.$request->ip_address.'%');
        }
        if ($request->filled('plugin_name')) {
            $query->where('plugin_name', $request->plugin_name);
        }
        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', $request->date_from.' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', $request->date_to.' 23:59:59');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', '%'.$search.'%')
                    ->orWhere('target_label', 'like', '%'.$search.'%')
                    ->orWhere('action', 'like', '%'.$search.'%')
                    ->orWhere('ip_address', 'like', '%'.$search.'%');
            });
        }

        $perPage = $request->input('per_page', 50);
        $allowedPerPage = [25, 50, 100, 200];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 50;
        }

        $this->viewParams['auditLogs'] = $query->paginate($perPage)->withQueryString();
        $this->viewParams['categories'] = AuditLog::distinct()->pluck('category')->filter()->sort()->values()->toArray();
        $this->viewParams['actions'] = AuditLog::distinct()->pluck('action')->filter()->sort()->values()->toArray();
        $this->viewParams['severities'] = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        $this->viewParams['outcomes'] = ['success', 'failure', 'denied', 'pending', 'unknown'];
        $this->viewParams['tableExists'] = true;
        $this->addLogColorMaps();

        return view('admin::settings.systems.logs.index', $this->viewParams);
    }

    /**
     * Audit log details
     */
    public function show(Request $request, $id)
    {
        $this->viewParams['logType'] = 'audit';
        $this->viewParams['auditView'] = 'db';

        if (! Schema::hasTable('audit_logs')) {
            return redirect()->route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db'])
                ->with('error', __('admin/settings/systems/logs/index.table_not_exists'));
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
        // Integrity state shown in the Meta Information card (maintained by `audit:integrity`)
        $this->viewParams['integrityStatus'] = $auditLog->record_hash === null
            ? 'unchained'
            : ($auditLog->verification_status ?? 'unverified');
        $this->addLogColorMaps();

        return view('admin::settings.systems.logs.show', $this->viewParams);
    }

    /**
     * Audit log CSV export
     */
    public function auditExport(Request $request)
    {
        if (! Schema::hasTable('audit_logs')) {
            return redirect()->route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db'])
                ->with('error', __('admin/settings/systems/logs/index.table_not_exists'));
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
            $query->where('ip_address', 'like', '%'.$request->ip_address.'%');
        }
        if ($request->filled('date_from')) {
            $query->where('occurred_at', '>=', $request->date_from.' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('occurred_at', '<=', $request->date_to.' 23:59:59');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', '%'.$search.'%')
                    ->orWhere('target_label', 'like', '%'.$search.'%')
                    ->orWhere('action', 'like', '%'.$search.'%')
                    ->orWhere('ip_address', 'like', '%'.$search.'%');
            });
        }

        $logs = $query->limit(10000)->get();

        $filename = 'audit_logs_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'ID', 'Category', 'Action', 'Severity', 'Outcome',
                'Actor Type', 'Actor ID', 'Actor Name',
                'Target Type', 'Target ID', 'Target Label',
                'IP Address', 'User Agent', 'Plugin', 'Request ID',
                'Message', 'Occurred At',
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
     * Audit log cleanup
     */
    public function auditCleanup(Request $request)
    {
        if (! Schema::hasTable('audit_logs')) {
            return redirect()->route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db'])
                ->with('error', __('admin/settings/systems/logs/index.table_not_exists'));
        }

        // Deleting audit evidence is SUPER_ADMIN-only. The route's menu key
        // (settings.systems.logs) has no definition of its own and resolves
        // through its ADMIN-level children, so the gate alone let an ADMIN
        // wipe the log of what they had done.
        $member = AdminHelper::getMember();
        if ($member === null || $member->getAttribute('role') !== MemberRole::SUPER_ADMIN) {
            abort(403);
        }

        // At least one day is always kept. `0` used to truncate the whole
        // table, and a negative value put the cutoff in the future, which
        // deleted everything as well.
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:36500'],
        ]);
        $days = (int) $validated['days'];

        $cutoff = now()->subDays($days);
        $count = AuditLog::where('occurred_at', '<', $cutoff)->count();

        // Record the cleanup itself before the rows go, so the deletion is
        // part of the chain it shortens.
        Audit::log([
            'category' => 'system',
            'action' => 'audit_log.cleanup',
            'actor' => $member,
            'severity' => 'high',
            'context' => ['older_than_days' => $days, 'deleted' => $count],
        ]);

        AuditLog::where('occurred_at', '<', $cutoff)->delete();

        return redirect()->route('admin.settings.systems.logs.index')
            ->with('success', __('admin/settings/systems/logs/index.cleanup_success', ['count' => $count]));
    }

    /**
     * Find dated log files for daily driver
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
     * Get list of available dates for the specified log file
     */
    protected function getAvailableLogDates(string $baseFileName): array
    {
        $logsPath = storage_path('logs');
        $baseName = pathinfo($baseFileName, PATHINFO_FILENAME);
        $extension = pathinfo($baseFileName, PATHINFO_EXTENSION);

        $dates = [];

        // Add as 'latest' if file without date exists
        $baseFilePath = "{$logsPath}/{$baseFileName}";
        if (File::exists($baseFilePath)) {
            $dates[''] = __('admin/settings/systems/logs/files.date_latest');
        }

        // Search for dated files (past 90 days)
        for ($i = 0; $i < 90; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyFileName = "{$baseName}-{$date}.{$extension}";
            $dailyFilePath = "{$logsPath}/{$dailyFileName}";

            if (File::exists($dailyFilePath)) {
                $fileSize = File::size($dailyFilePath);
                $sizeLabel = $this->formatFileSize($fileSize);
                $dates[$date] = $date." ({$sizeLabel})";
            }
        }

        return $dates;
    }

    /**
     * Get log file path for the specified date
     */
    protected function getLogFilePathByDate(string $baseFileName, string $date): ?string
    {
        $logsPath = storage_path('logs');
        $baseName = pathinfo($baseFileName, PATHINFO_FILENAME);
        $extension = pathinfo($baseFileName, PATHINFO_EXTENSION);

        $dailyFileName = "{$baseName}-{$date}.{$extension}";
        $dailyFilePath = "{$logsPath}/{$dailyFileName}";

        if (File::exists($dailyFilePath)) {
            return $dailyFilePath;
        }

        return null;
    }

    /**
     * Format file size in human-readable format
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 1).' '.$units[$unitIndex];
    }

    /**
     * Efficiently read trailing lines from log file
     */
    protected function readLogFileLines(string $filePath, int $maxLines = 5000): array
    {
        $fileSize = filesize($filePath);

        if ($fileSize < 1024 * 1024) {
            $lines = array_reverse(
                array_filter(
                    explode("\n", File::get($filePath)),
                    fn ($line) => trim($line) !== ''
                )
            );

            return array_slice($lines, 0, $maxLines);
        }

        $lines = [];
        $handle = fopen($filePath, 'r');

        if (! $handle) {
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
            $buffer = $chunk.$buffer;

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
        // Pattern: [timestamp] level: message {json}
        // Capture JSON only if it exists at end of line
        $pattern = '/^\[([^\]]+)\]\s+([^:]+):\s+(.+?)(\s+\{.+\})?\s*$/';

        if (! preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => '',
                'level' => '',
                'message' => $line,
                'context' => [],
                'parsed' => false,
            ];
        }

        $timestamp = $matches[1];
        $levelRaw = $matches[2];
        $messageWithContext = $matches[3];
        $contextJson = isset($matches[4]) ? trim($matches[4]) : '';

        // Extract level from "local.INFO" format
        $level = $levelRaw;
        if (strpos($levelRaw, '.') !== false) {
            $parts = explode('.', $levelRaw);
            $level = end($parts);
        }

        // Handle case where JSON is included in message
        // Separate JSON from message part
        $message = $messageWithContext;
        $context = [];

        // If there is JSON starting with { in the message
        if (preg_match('/^(.+?)\s+(\{.+\})$/', $messageWithContext, $jsonMatches)) {
            $message = trim($jsonMatches[1]);
            $contextJson = $jsonMatches[2];
        }

        if (! empty($contextJson)) {
            $decoded = json_decode($contextJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $context = $decoded;
            }
        }

        return [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'parsed' => true,
        ];
    }

    /**
     * Add color map for audit log to viewParams
     */
    private function addLogColorMaps(): void
    {
        $this->viewParams['severityColors'] = [
            'debug' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'notice' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'error' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'critical' => 'bg-red-200 text-red-900 dark:bg-red-800 dark:text-red-100',
            'alert' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'emergency' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        ];
        $this->viewParams['outcomeColors'] = [
            'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'failure' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'denied' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'unknown' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        ];
        $this->viewParams['integrityColors'] = [
            'valid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'invalid' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'unverified' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'unchained' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        ];
    }

    /**
     * Calculate level-specific colors for file logs
     *
     * @return array{bg: string, border: string, dot: string, label: string, text: string}
     */
    private static function getLevelColors(string $level): array
    {
        return match (true) {
            str_contains($level, 'error'), str_contains($level, 'critical'), str_contains($level, 'alert'), str_contains($level, 'emergency') => [
                'bg' => 'bg-red-50 dark:bg-red-900/20',
                'border' => 'border-l-red-400',
                'dot' => 'bg-red-500',
                'label' => 'text-red-700 dark:text-red-300',
                'text' => 'text-red-600 dark:text-red-200',
            ],
            str_contains($level, 'warning'), str_contains($level, 'notice') => [
                'bg' => 'bg-yellow-50 dark:bg-yellow-900/20',
                'border' => 'border-l-yellow-400',
                'dot' => 'bg-yellow-500',
                'label' => 'text-yellow-700 dark:text-yellow-300',
                'text' => 'text-yellow-600 dark:text-yellow-200',
            ],
            str_contains($level, 'debug') => [
                'bg' => 'bg-gray-50 dark:bg-gray-800/50',
                'border' => 'border-l-gray-400',
                'dot' => 'bg-gray-500',
                'label' => 'text-gray-700 dark:text-gray-300',
                'text' => 'text-gray-600 dark:text-gray-400',
            ],
            default => [
                'bg' => 'bg-blue-50 dark:bg-blue-900/20',
                'border' => 'border-l-blue-400',
                'dot' => 'bg-blue-500',
                'label' => 'text-blue-700 dark:text-blue-300',
                'text' => 'text-blue-600 dark:text-blue-200',
            ],
        };
    }

    /**
     * Add category data for navigation to viewParams
     */
    private function addNavigationData(string $logType): void
    {
        $adminTypes = ['activity', 'error', 'dixlase'];
        $frontTypes = ['front_activity', 'front_error'];
        $securityTypes = ['csp', 'audit'];
        $browserTypes = ['browser'];

        $this->viewParams['adminTypes'] = $adminTypes;
        $this->viewParams['frontTypes'] = $frontTypes;
        $this->viewParams['securityTypes'] = $securityTypes;
        $this->viewParams['browserTypes'] = $browserTypes;
        $this->viewParams['currentCategory'] = match (true) {
            in_array($logType, $adminTypes) => 'admin',
            in_array($logType, $frontTypes) => 'front',
            in_array($logType, $securityTypes) => 'security',
            in_array($logType, $browserTypes) => 'browser',
            default => 'admin',
        };
    }
}
