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

namespace App\Http\Controllers\Install;

use App\Enums\AdminMode;
use App\Http\Requests\Install\InstallEnvironmentRequest;

/**
 * Install - Step 2: Environment Settings
 */
class InstallEnvironmentController extends BaseInstallController
{
    /**
     * Display environment settings screen
     */
    public function create()
    {
        return view('install.environment', $this->getViewData(3));
    }

    /**
     * Save environment settings
     */
    public function store(InstallEnvironmentRequest $request)
    {
        $data = $request->validated();
        $isSimpleMode = (int) session('install_data.install_mode', 0) === AdminMode::Simple->value;

        // Apply default values in simple mode
        if ($isSimpleMode) {
            $data['app_env'] = 'production';
            $data['app_debug'] = false;
            $data['admin_url'] = session('install_data.admin_url', 'admin');
            // Auto-detect forced SSL from current access protocol
            $data['force_ssl'] = $request->isSecure()
                || $request->header('X-Forwarded-Proto') === 'https';
        } else {
            // Advanced mode: Combine prefix and suffix
            $data['admin_url'] = $data['admin_url_prefix'].'-'.$data['admin_url_suffix'];
            unset($data['admin_url_prefix'], $data['admin_url_suffix']);
        }

        // Remove protocol
        $data['app_url'] = preg_replace('/^(http:\/\/|https:\/\/)/', '', $data['app_url']);

        // Simple check if domain format
        if (! preg_match('/^[\w.\-]+(:\d+)?$/', $data['app_url'])) {
            return back()->withErrors([
                'app_url' => __('validation.url', ['attribute' => __('install/step2.app_url')]),
            ])->withInput();
        }

        // Set APP_DEBUG to false if production environment
        if ($data['app_env'] === 'production') {
            $data['app_debug'] = false;
        }

        // Save settings to session
        session(['install_data' => array_merge(session('install_data', []), $data)]);

        return redirect()->route('install.database');
    }
}
