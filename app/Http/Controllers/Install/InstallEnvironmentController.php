<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Http\Controllers\Install;

use App\Http\Requests\Install\InstallEnvironmentRequest;

/**
 * インストール - ステップ2: 環境設定
 */
class InstallEnvironmentController extends BaseInstallController
{
    /**
     * 環境設定画面を表示
     */
    public function create()
    {
        return view('install.environment', $this->getViewData(2));
    }

    /**
     * 環境設定を保存
     */
    public function store(InstallEnvironmentRequest $request)
    {
        $data = $request->validated();

        // プロトコル除去
        $data['app_url'] = preg_replace('/^(http:\/\/|https:\/\/)/', '', $data['app_url']);

        // ドメイン形式か簡易チェック
        if (!preg_match('/^[\w.\-]+(:\d+)?$/', $data['app_url'])) {
            return back()->withErrors([
                'app_url' => __('validation.url', ['attribute' => __('install/step2.app_url')])
            ])->withInput();
        }

        // 本番環境なら APP_DEBUG は false 固定
        if ($data['app_env'] === 'production') {
            $data['app_debug'] = false;
        }

        // 設定をセッションに保存
        session(['install_data' => array_merge(session('install_data', []), $data)]);

        return redirect()->route('install.database');
    }
}
