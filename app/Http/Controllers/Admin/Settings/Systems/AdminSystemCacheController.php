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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminSystemCacheController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * キャッシュ管理画面
     */
    public function index()
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

    /**
     * 個別キャッシュクリア
     */
    public function clear(Request $request)
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
