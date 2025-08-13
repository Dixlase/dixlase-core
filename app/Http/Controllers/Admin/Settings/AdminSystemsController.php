<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class AdminSystemsController extends AdminLoggedInController
{
    //

    protected $logPaths = [
        'activity' => 'admin_activity.log',
        'error'    => 'admin_error.log',
        'login'    => 'admin_login.log',
        'laravel'  => 'laravel.log',
    ];

    public function __construct()
    {
        // 親クラスのコンストラクタを呼び出す
        parent::__construct();
    }



    //システムログ
    public function logs(Request $request, $type = 'activity')
    {

        $fileName = $this->logPaths[$type] ?? $this->logPaths['activity'];
        $filePath = storage_path("logs/{$fileName}");

        $this->viewParams['logType'] = $type;


        if (!File::exists($filePath)) {

            $this->viewParams['logs'] = ["ログファイルが存在しません：{$fileName}"];
            $this->viewParams['logTypes'] = array_keys($this->logPaths);

            return response()->view('admin::settings.systems.logs', $this->viewParams);
        }


        $lines = array_reverse(
            array_filter(
                explode("\n", File::get($filePath)),
                fn($line) => trim($line) !== ''
            )
        );

        $this->viewParams['logs'] = $lines;
        $this->viewParams['logTypes'] = array_keys($this->logPaths);


        return view('admin::settings.systems.logs', $this->viewParams);
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
}
