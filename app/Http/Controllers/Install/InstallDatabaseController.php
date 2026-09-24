<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

use App\Http\Requests\Install\InstallDatabaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Installation - Step 3: Database settings
 */
class InstallDatabaseController extends BaseInstallController
{
    /**
     * Display database settings screen
     */
    public function create()
    {
        // Build the driver option list from the actually loaded PDO extensions
        // so users cannot pick a driver that the host PHP cannot connect with.
        $availableDrivers = [];
        if (extension_loaded('pdo_mysql')) {
            $availableDrivers['mysql'] = 'MySQL';
        }
        if (extension_loaded('pdo_sqlite')) {
            $availableDrivers['sqlite'] = 'SQLite';
        }

        // Default to the first available driver if the previously stored
        // choice is no longer installable on this host.
        $sessionDriver = session('install_data.db_connection');
        if ($sessionDriver !== null && ! isset($availableDrivers[$sessionDriver])) {
            $sessionDriver = null;
        }
        $defaultDriver = $sessionDriver ?? array_key_first($availableDrivers);

        return view('install.database', array_merge(
            $this->getViewData(4),
            [
                'availableDrivers' => $availableDrivers,
                'defaultDriver' => $defaultDriver,
            ]
        ));
    }

    /**
     * Save database settings
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

        // Keep the raw password for the immediate .env write below; the
        // session copy is encrypted to match the existing wizard contract.
        $rawDbPassword = (string) $request->input('db_password', '');
        if ($request->filled('db_password')) {
            $data['db_password'] = Crypt::encryptString($request->db_password);
        }

        // Save preserve_data explicitly as a boolean value
        // Toggle component sends '1' when ON, '0' when OFF
        $data['preserve_data'] = $request->input('preserve_data') === '1';

        session(['install_data' => array_merge(session('install_data', []), $data)]);

        // Write the freshly-chosen DB settings to .env immediately so the
        // wizard's later screens (confirm / mail) and any code that opens
        // a DB connection during this same request lifecycle stop using
        // the boot-time defaults (e.g. DB_HOST=mysql when the host is
        // actually running natively, or after switching to sqlite). The
        // final install step still rewrites .env with the same values,
        // so this is purely a "make the next page work" patch.
        $this->persistDbEnv($request, $rawDbPassword);

        return redirect()->route('install.mail');
    }

    /**
     * Persist the selected DB driver/credentials to .env and refresh the
     * runtime config so the next request — and any DB connection opened
     * later in this request — uses the user's choice instead of the
     * boot-time defaults.
     */
    private function persistDbEnv(Request $request, string $rawPassword): void
    {
        $connection = (string) $request->input('db_connection', 'mysql');

        if ($connection === 'sqlite') {
            // Resolve exactly like InstallConfirmController and the
            // testConnection() endpoint, so the .env value matches
            // the file the wizard actually opened during the test.
            $database = $this->resolveSqliteDatabasePath($request->input('db_database'));

            $envData = [
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $database,
                // SQLite ignores these but leaving stale values (DB_HOST=mysql
                // from the docker preset) lets other code paths still try to
                // open a TCP connection. Blank them out.
                'DB_HOST' => '',
                'DB_PORT' => '',
                'DB_USERNAME' => '',
                'DB_PASSWORD' => '',
            ];
        } else {
            $envData = [
                'DB_CONNECTION' => $connection,
                'DB_HOST' => (string) $request->input('db_host', ''),
                'DB_PORT' => (string) $request->input('db_port', ''),
                'DB_DATABASE' => (string) $request->input('db_database', ''),
                'DB_USERNAME' => (string) $request->input('db_username', ''),
                'DB_PASSWORD' => $rawPassword,
            ];
        }

        try {
            $this->updateEnv($envData);
            // Clear the cached config + refresh the runtime $app['config']
            // so the new DB_* values take effect from this point on.
            Artisan::call('config:clear');
            config([
                'database.default' => $envData['DB_CONNECTION'],
                'database.connections.'.$envData['DB_CONNECTION'].'.database' => $envData['DB_DATABASE'],
                'database.connections.'.$envData['DB_CONNECTION'].'.host' => $envData['DB_HOST'] ?? null,
                'database.connections.'.$envData['DB_CONNECTION'].'.port' => $envData['DB_PORT'] ?? null,
                'database.connections.'.$envData['DB_CONNECTION'].'.username' => $envData['DB_USERNAME'] ?? null,
                'database.connections.'.$envData['DB_CONNECTION'].'.password' => $envData['DB_PASSWORD'] ?? null,
            ]);
            DB::purge();
        } catch (\Throwable $e) {
            // Don't block the wizard if .env write fails — InstallConfirmController
            // re-writes the same values at the final step and that path has its
            // own logging. Just leave a breadcrumb here.
            Log::channel('install')->warning('Failed to persist DB settings to .env mid-wizard', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Test database connection
     */
    public function testConnection(Request $request)
    {
        try {
            $connection = $request->input('db_connection', 'mysql');
            $database = $request->input('db_database');

            if ($connection === 'sqlite') {
                // Laravel's SQLite connector requires an absolute path and an
                // existing file, so resolve and create before connecting.
                $database = $this->resolveSqliteDatabasePath($database);
                $this->ensureSqliteDatabaseFile($database);
                $config = [
                    'driver' => 'sqlite',
                    'database' => $database,
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ];
            } else {
                $config = [
                    'driver' => $connection,
                    'host' => $request->input('db_host'),
                    'port' => $request->input('db_port'),
                    'database' => $database,
                    'username' => $request->input('db_username'),
                    'password' => $request->input('db_password'),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ];
            }

            config(['database.connections.test_connection' => $config]);

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
