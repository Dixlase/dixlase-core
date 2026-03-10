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

namespace App\Http\Controllers\Install;

use App\Enums\AdminMode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * インストール - モード選択
 */
class InstallModeController extends BaseInstallController
{
    /**
     * モード選択画面を表示
     */
    public function create()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        return view('install.mode', [
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
            'selectedMode' => session('install_data.install_mode', AdminMode::Simple->value),
        ]);
    }

    /**
     * モードを保存
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'install_mode' => 'required|integer|in:0,1',
        ]);

        session(['install_data.install_mode' => (int) $validated['install_mode']]);

        // ランダムな管理画面URLを初回のみ生成（セッションに未設定の場合）
        if (! session()->has('install_data.admin_url')) {
            session(['install_data.admin_url' => 'admin-'.strtolower(Str::random(4))]);
        }

        return redirect()->route('install.settings');
    }
}
