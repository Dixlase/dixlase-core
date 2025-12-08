<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminSystemsController extends AdminLoggedInController
{
    //

    protected $logPaths = [
        'activity' => 'admin_activity.log',
        'error'    => 'admin_error.log',
        'login'    => 'admin_login.log',
        'dixlase'  => 'dixlase.log',
        'front_activity' => 'front_activity.log',
        'front_error' => 'front_error.log',
        'extension' => 'extension_activity.log',
        'csp' => 'csp_violations.log',
        'audit' => 'audit.log',
    ];

    public function __construct()
    {
        // 親クラスのコンストラクタを呼び出す
        parent::__construct();
    }



    //システムログ
    public function logs(Request $request, $type = 'activity')
    {
        // 監査ログの場合は特別処理
        if ($type === 'audit') {
            $view = $request->input('view', 'db');
            
            if ($view === 'db') {
                return $this->auditLogsDb($request);
            }
            // view=file の場合は通常のファイルログ表示へ
        }

        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];
        $filePath = storage_path("logs/{$fileName}");

        $this->viewParams['logType'] = $type;


        if (!File::exists($filePath)) {
            // Return structured data even when file doesn't exist
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


        $lines = array_reverse(
            array_filter(
                explode("\n", File::get($filePath)),
                fn($line) => trim($line) !== ''
            )
        );

        // Parse log lines into structured data
        $parsedLogs = [];
        foreach ($lines as $line) {
            $parsedLog = $this->parseLogLine($line);
            if ($parsedLog) {
                $parsedLogs[] = $parsedLog;
            }
        }

        // Implement pagination
        $perPage = $request->input('per_page', 50);
        
        // 有効な表示件数かチェック
        $allowedPerPage = [25, 50, 100, 200];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 50;
        }
        
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $totalLogs = count($parsedLogs);
        $paginatedLogs = array_slice($parsedLogs, $offset, $perPage);

        // Create pagination data
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

    // ログファイルダウンロード
    public function downloadLog(Request $request, $type = 'activity')
    {
        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];
        $filePath = storage_path("logs/{$fileName}");

        if (!File::exists($filePath)) {
            return redirect()->route('admin.settings.systems.logs', ['type' => $type])
                ->with('error', __('admin.settings.systems.logs.messages.download_error', ['filename' => $fileName]));
        }

        return response()->download($filePath, $fileName);
    }

    // ログ内容消去
    public function clearLog(Request $request, $type = 'activity')
    {
        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];
        $filePath = storage_path("logs/{$fileName}");

        try {
            if (File::exists($filePath)) {
                File::put($filePath, '');
                return redirect()->route('admin.settings.systems.logs', ['type' => $type])
                    ->with('success', __('admin.settings.systems.logs.messages.clear_success', ['filename' => $fileName]));
            } else {
                return redirect()->route('admin.settings.systems.logs', ['type' => $type])
                    ->with('error', __('admin.settings.systems.logs.messages.clear_error', ['filename' => $fileName]));
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.systems.logs', ['type' => $type])
                ->with('error', __('admin.settings.systems.logs.messages.clear_failed', ['error' => $e->getMessage()]));
        }
    }

    // システム情報
    public function info()
    {

        $databaseVersion = DB::select("select version() as version")[0]->version ?? 'N/A';

        $info = [
            'software' => [
                'name' => config('app.name', 'Dixlase'),
                'version' => config('app.cms_version', '1.0.0'), // 独自CMSバージョン
            ],
            'Laravel' => [
                'version' => app()->version(),
            ],
            'PHP' => [
                'version' => PHP_VERSION,
                'sapi' => php_sapi_name(),
            ],
            'Server' => [
                'os' => php_uname(),
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'ip' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
                'hostname' => gethostname(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'datetime' => now()->toDateTimeString(),
            ],
            'Environment' => [
                'env' => app()->environment(),
                'debug' => config('app.debug'),
                'app_url' => config('app.url'),
            ],
            'Database' => [
                'driver' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
                'version' => $databaseVersion,
            ],
            'Cache' => [
                'driver' => config('cache.default'),
            ],
            'Session' => [
                'driver' => config('session.driver'),
            ],
            'Queue' => [
                'driver' => config('queue.default'),
            ],
        ];

        $this->viewParams['info'] = $info;

        return view('admin::settings.systems.info', $this->viewParams);
    }

    // キャッシュ管理画面
    public function cache()
    {
        $cacheInfo = [
            'config' => [
                'name' => __('admin.settings.systems.cache.config_cache.name'),
                'description' => __('admin.settings.systems.cache.config_cache.description'),
                'command' => 'config:clear'
            ],
            'route' => [
                'name' => __('admin.settings.systems.cache.route_cache.name'),
                'description' => __('admin.settings.systems.cache.route_cache.description'),
                'command' => 'route:clear'
            ],
            'view' => [
                'name' => __('admin.settings.systems.cache.view_cache.name'),
                'description' => __('admin.settings.systems.cache.view_cache.description'),
                'command' => 'view:clear'
            ],
            'application' => [
                'name' => __('admin.settings.systems.cache.application_cache.name'),
                'description' => __('admin.settings.systems.cache.application_cache.description'),
                'command' => 'cache:clear'
            ]
        ];

        $this->viewParams['cacheInfo'] = $cacheInfo;
        
        return view('admin::settings.systems.cache', $this->viewParams);
    }

    // 個別キャッシュクリア
    public function clearCache(Request $request)
    {
        $type = $request->input('type');
        $message = '';
        $success = true;

        try {
            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    $message = __('admin.settings.systems.cache.success_config');
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    $message = __('admin.settings.systems.cache.success_route');
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    $message = __('admin.settings.systems.cache.success_view');
                    break;
                case 'application':
                    Artisan::call('cache:clear');
                    $message = __('admin.settings.systems.cache.success_application');
                    break;
                case 'all':
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('view:clear');
                    Artisan::call('cache:clear');
                    $message = __('admin.settings.systems.cache.success_all');
                    break;
                default:
                    $success = false;
                    $message = __('admin.settings.systems.cache.error_invalid_type');
            }
        } catch (\Exception $e) {
            $success = false;
            $message = __('admin.settings.systems.cache.error_general', ['error' => $e->getMessage()]);
        }

        if ($success) {
            return redirect()->route('admin.settings.systems.cache')->with('success', $message);
        } else {
            return redirect()->route('admin.settings.systems.cache')->with('error', $message);
        }
    }

    // データベース管理画面
    public function database()
    {
        $cleanupInfo = [
            'login_attempts' => [
                'name' => __('admin.settings.systems.database.login_attempts.name'),
                'description' => __('admin.settings.systems.database.login_attempts.description'),
                'default_days' => 30,
                'command' => 'dls:admin:cleanup-login-attempts'
            ],
            'password_reset_tokens' => [
                'name' => __('admin.settings.systems.database.password_reset_tokens.name'),
                'description' => __('admin.settings.systems.database.password_reset_tokens.description'),
                'default_days' => 30,
                'command' => 'dls:admin:cleanup-password-reset-tokens'
            ],
            'two_factor_attempts' => [
                'name' => __('admin.settings.systems.database.two_factor_attempts.name'),
                'description' => __('admin.settings.systems.database.two_factor_attempts.description'),
                'default_days' => 30,
                'command' => 'dls:admin:cleanup-two-factor-attempts'
            ],
            'two_factor_tokens' => [
                'name' => __('admin.settings.systems.database.two_factor_tokens.name'),
                'description' => __('admin.settings.systems.database.two_factor_tokens.description'),
                'default_days' => 7,
                'command' => 'dls:admin:cleanup-two-factor-tokens'
            ],
            'recovery_codes' => [
                'name' => __('admin.settings.systems.database.recovery_codes.name'),
                'description' => __('admin.settings.systems.database.recovery_codes.description'),
                'default_days' => 90,
                'command' => 'dls:admin:cleanup-recovery-codes'
            ],
            'passkeys' => [
                'name' => __('admin.settings.systems.database.passkeys.name'),
                'description' => __('admin.settings.systems.database.passkeys.description'),
                'default_days' => 90,
                'command' => 'dls:admin:cleanup-passkeys'
            ],
            'cache_data' => [
                'name' => __('admin.settings.systems.database.cache_data.name'),
                'description' => __('admin.settings.systems.database.cache_data.description'),
                'default_days' => null, // 期限切れのみ
                'command' => 'dls:admin:cleanup-cache'
            ],
            'sessions' => [
                'name' => __('admin.settings.systems.database.sessions.name'),
                'description' => __('admin.settings.systems.database.sessions.description'),
                'default_days' => 7,
                'command' => 'dls:admin:cleanup-sessions'
            ]
        ];

        $this->viewParams['cleanupInfo'] = $cleanupInfo;
        
        return view('admin::settings.systems.database', $this->viewParams);
    }

    // 個別データベースクリーンアップ
    public function cleanupDatabase(Request $request)
    {
        $type = $request->input('type');
        $days = (int) $request->input('days');
        $message = '';
        $success = true;
        $count = 0;

        try {
            switch ($type) {
                case 'login_attempts':
                    $options = ['--days' => $days];
                    if ($days === 0) {
                        $options['--force'] = true;
                    }
                    $exitCode = Artisan::call('dls:admin:cleanup-login-attempts', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'password_reset_tokens':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    $exitCode = Artisan::call('dls:admin:cleanup-password-reset-tokens', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'trusted_devices':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    $exitCode = Artisan::call('dls:admin:cleanup-trusted-devices', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'two_factor_attempts':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    $exitCode = Artisan::call('dls:admin:cleanup-two-factor-attempts', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'two_factor_tokens':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    $exitCode = Artisan::call('dls:admin:cleanup-two-factor-tokens', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'recovery_codes':
                    $options = $days === 0 ? ['--days' => $days, '--force' => true] : ['--days' => $days];
                    $exitCode = Artisan::call('dls:admin:cleanup-recovery-codes', $options);
                    $output = Artisan::output();
                    $count = $this->extractCountFromOutput($output);
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $count]);
                    break;
                case 'all':
                    $totalCount = 0;
                    $allDays = (int) $request->input('all_days', 30);
                    
                    // クリーンアップコマンドのリスト
                    $cleanupTypes = [
                        'login_attempts',
                        'password_reset_tokens',
                        'two_factor_attempts',
                        'two_factor_tokens',
                        'recovery_codes',
                        'passkeys',
                        'sessions'
                    ];
                    
                    // 各クリーンアップコマンドを共通の日数で実行
                    foreach ($cleanupTypes as $cleanupType) {
                        $commandName = 'dls:admin:cleanup-' . str_replace('_', '-', $cleanupType);
                        $options = ['--days' => $allDays, '--force' => true];
                        
                        Artisan::call($commandName, $options);
                        $output = Artisan::output();
                        $totalCount += $this->extractCountFromOutput($output);
                    }
                    
                    // キャッシュクリーンアップ（日数指定なし）
                    Artisan::call('dls:admin:cleanup-cache', ['--expired-only' => true, '--force' => true]);
                    $output = Artisan::output();
                    $totalCount += $this->extractCountFromOutput($output);
                    
                    $message = __('admin.settings.systems.database.cleanup_success', ['count' => $totalCount]);
                    break;
                default:
                    $success = false;
                    $message = __('admin.settings.systems.database.cleanup_error', ['error' => 'Invalid cleanup type']);
            }
        } catch (\Exception $e) {
            $success = false;
            $message = __('admin.settings.systems.database.cleanup_error', ['error' => $e->getMessage()]);
        }

        if ($success) {
            return redirect()->route('admin.settings.systems.database')->with('success', $message);
        } else {
            return redirect()->route('admin.settings.systems.database')->with('error', $message);
        }
    }

    // コマンド出力から削除件数を抽出
    private function extractCountFromOutput($output)
    {
        // 新しいパターン: "DELETED_COUNT: X"
        if (preg_match('/DELETED_COUNT: (\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        // 英語パターン: "Successfully deleted X records"
        if (preg_match('/Successfully deleted (\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        // 英語パターン: "Successfully deleted all X records"
        if (preg_match('/Successfully deleted all (\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        // 日本語パターン: "X 件の古い...記録を正常に削除しました。"
        if (preg_match('/(\d+) 件の.*を正常に削除しました/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        // 日本語パターン: "X 件の...記録を正常に削除しました。"
        if (preg_match('/(\d+) 件の.*記録を正常に削除しました/', $output, $matches)) {
            return (int) $matches[1];
        }
        
        return 0;
    }

    /**
     * Parse a log line into structured data
     */
    private function parseLogLine($line)
    {
        // Pattern to match Laravel log format: [timestamp] level.LEVEL: message {"context"}
        $pattern = '/^\[([^\]]+)\]\s+([^:]+):\s+(.+?)(\s+\{.*\})?$/';
        
        if (!preg_match($pattern, $line, $matches)) {
            // If the line doesn't match the expected format, return it as raw text
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

        // Parse context JSON if present
        $context = [];
        if (!empty($contextJson)) {
            try {
                $context = json_decode($contextJson, true) ?? [];
            } catch (\Exception $e) {
                // If JSON parsing fails, keep as empty array
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

    /**
     * ログ出力テスト
     */
    public function testLogs(Request $request)
    {
        $type = $request->input('type', 'all');
        $results = [];

        try {
            if ($type === 'all' || $type === 'dixlase') {
                // 通常ログ（dixlase.log）のテスト
                Log::info('ログ出力テスト - 通常ログ', [
                    'test_type' => 'dixlase_log',
                    'timestamp' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                ]);
                $results['dixlase'] = 'success';
            }

            if ($type === 'all' || $type === 'activity') {
                // アクティビティログのテスト
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
                // エラーログのテスト
                Log::channel('admin_error')->error('ログ出力テスト - エラーログ', [
                    'test_type' => 'error_log',
                    'timestamp' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'error_message' => 'これはテスト用のエラーメッセージです',
                ]);
                $results['error'] = 'success';
            }

            if ($type === 'all' || $type === 'login') {
                // ログインログのテスト
                Log::channel('admin_login')->info('ログ出力テスト - ログインログ', [
                    'test_type' => 'login_log',
                    'timestamp' => now()->toDateTimeString(),
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'action' => 'test_login_log',
                    'ip' => request()->ip(),
                ]);
                $results['login'] = 'success';
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
    public function testErrorLog(Request $request)
    {
        $errorType = $request->input('error_type', 'exception');

        try {
            switch ($errorType) {
                case 'exception':
                    // 意図的に例外を発生させる
                    throw new \Exception('テスト用の例外エラーです - admin_error.logに記録されるかテスト中');
                    
                case 'database':
                    // 存在しないテーブルにアクセスしてDBエラーを発生
                    DB::table('non_existent_table')->get();
                    break;
                    
                case 'file':
                    // 存在しないファイルを読み込んでファイルエラーを発生
                    $content = file_get_contents('/path/to/non/existent/file.txt');
                    break;
                    
                case 'division':
                    // ゼロ除算エラーを発生
                    $result = 10 / 0;
                    break;
                    
                case 'manual_log':
                    // 手動でエラーログに記録（例外は発生させない）
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
            // エラーをadmin_errorチャンネルに記録
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
    public function testFrontLogs(Request $request)
    {
        try {
            // フロント操作ログのテスト
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
    public function testFrontErrorLog(Request $request)
    {
        try {
            // フロントエラーログのテスト
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

        // テーブルが存在しない場合
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

        // フィルタリング
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

        // フィルタ用の選択肢を取得
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
    public function auditLogShow(Request $request, $id)
    {
        $this->viewParams['logType'] = 'audit';
        $this->viewParams['auditView'] = 'db';

        if (!Schema::hasTable('audit_logs')) {
            return redirect()->route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db'])
                ->with('error', __('admin.settings.audit_logs.table_not_exists'));
        }

        $auditLog = AuditLog::findOrFail($id);
        
        // 同一リクエストIDの関連ログを取得
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
    public function auditLogExport(Request $request)
    {
        if (!Schema::hasTable('audit_logs')) {
            return redirect()->route('admin.settings.systems.logs', ['type' => 'audit', 'view' => 'db'])
                ->with('error', __('admin.settings.audit_logs.table_not_exists'));
        }

        $query = AuditLog::query()->orderByDesc('occurred_at');

        // フィルタリング（indexと同じ）
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
            // BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // ヘッダー
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
    public function auditLogCleanup(Request $request)
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
}
