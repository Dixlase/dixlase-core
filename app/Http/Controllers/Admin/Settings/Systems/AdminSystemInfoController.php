<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
use Illuminate\Support\Facades\DB;

class AdminSystemInfoController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * システム情報
     */
    public function index()
    {
        $databaseVersion = DB::select('select version() as version')[0]->version ?? 'N/A';

        $info = [
            'software' => [
                'name' => config('app.name', 'Dixlase'),
                'version' => config('app.cms_version', '1.0.0'),
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
                'host' => config('database.connections.'.config('database.default').'.host'),
                'database' => config('database.connections.'.config('database.default').'.database'),
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
