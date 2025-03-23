<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

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
                'name' => config('app.name', 'MyCMS'),
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
}
