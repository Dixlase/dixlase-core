<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Http\Controllers\Install;

use App\Enums\AdminMode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Install - Mode Selection
 */
class InstallModeController extends BaseInstallController
{
    /**
     * Display mode selection screen
     */
    public function create()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        return view('install.mode', array_merge(
            $this->getViewData(1),
            [
                'selectedMode' => session('install_data.install_mode', AdminMode::Simple->value),
            ]
        ));
    }

    /**
     * Save mode
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'install_mode' => 'required|integer|in:0,1',
        ]);

        session(['install_data.install_mode' => (int) $validated['install_mode']]);

        // Generate random admin panel URL only on first access (if not set in session)
        // Generate both prefix and suffix randomly
        if (! session()->has('install_data.admin_url')) {
            $prefixes = config('admin.url.admin_url_prefixes', ['admin']);
            $prefix = $prefixes[array_rand($prefixes)];
            $suffix = strtolower(Str::random(4));
            session(['install_data.admin_url' => $prefix.'-'.$suffix]);
        }

        return redirect()->route('install.settings');
    }
}
