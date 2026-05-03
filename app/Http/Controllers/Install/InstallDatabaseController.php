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

use App\Http\Requests\Install\InstallDatabaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * インストール - ステップ3: データベース設定
 */
class InstallDatabaseController extends BaseInstallController
{
    /**
     * データベース設定画面を表示
     */
    public function create()
    {
        return view('install.database', $this->getViewData(4));
    }

    /**
     * データベース設定を保存
     */
    public function store(InstallDatabaseRequest $request)
    {
        $data = $request->only([
            'db_connection',
            'db_host',
            'db_port',
            'db_database',
            'db_username',
            'db_password',
        ]);

        if ($request->filled('db_password')) {
            $data['db_password'] = Crypt::encryptString($request->db_password);
        }

        // preserve_dataを明示的にブール値として保存
        // トグルコンポーネントはON時に'1'、OFF時に'0'を送信する
        $data['preserve_data'] = $request->input('preserve_data') === '1';

        session(['install_data' => array_merge(session('install_data', []), $data)]);

        return redirect()->route('install.mail');
    }

    /**
     * データベース接続テスト
     */
    public function testConnection(Request $request)
    {
        try {
            $connection = $request->input('db_connection', 'mysql');
            $host = $request->input('db_host');
            $port = $request->input('db_port');
            $database = $request->input('db_database');
            $username = $request->input('db_username');
            $password = $request->input('db_password');

            config([
                'database.connections.test_connection' => [
                    'driver' => $connection,
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ],
            ]);

            DB::connection('test_connection')->getPdo();
            DB::purge('test_connection');

            return response()->json([
                'success' => true,
                'message' => __('install/step3.db_connection_success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('install/step3.db_connection_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }
}
