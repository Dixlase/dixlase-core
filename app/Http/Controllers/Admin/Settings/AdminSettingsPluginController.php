<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plugin;

class AdminSettingsPluginController extends AdminController
{
    public function index()
    {
        $plugins = Plugin::all();
        $this->viewParams['heading'] = 'プラグインマスター';
        $this->viewParams['plugins'] = $plugins;
        return view('admin::settings.plugins.index', $this->viewParams);
    }

    public function install(Request $request)
    {
        $pluginName = $request->input('name');
        $namespace = "Plugins\\$pluginName";

        // プラグインを登録
        Plugin::create([
            'name' => $pluginName,
            'namespace' => $namespace,
            'status' => 'disabled',
        ]);

        return redirect()->route('admin::settings.plugins.index')->with('success', 'プラグインをインストールしました');
    }

    public function enable($id)
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->update(['status' => 'enabled']);

        return redirect()->route('admin::settings.plugins.index')->with('success', 'プラグインを有効化しました');
    }

    public function disable($id)
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->update(['status' => 'disabled']);

        return redirect()->route('admin::settings.plugins.index')->with('success', 'プラグインを無効化しました');
    }

    public function uninstall($id)
    {
        $plugin = Plugin::findOrFail($id);
        $plugin->delete();

        return redirect()->route('admin::settings.plugins.index')->with('success', 'プラグインをアンインストールしました');
    }
}
