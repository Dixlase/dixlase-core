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

use App\Exceptions\Install\CoreIntegrityBlockedException;
use App\Exceptions\Install\SqliteDatabaseUnusableException;
use App\Services\Install\InstallRunner;
use App\Support\Install\InstallRunLock;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Install - Confirmation screen
 */
class InstallConfirmController extends BaseInstallController
{
    /**
     * Display confirmation screen for input content
     */
    public function show()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        $data = session('install_data', []);

        // Output debug information to log
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.session_data_at_confirmation_display'), [
            'session_id' => session()->getId(),
            'session_keys' => array_keys($data),
            'has_site_name' => isset($data['site_name']),
            'has_admin_account_name' => isset($data['admin_account_name']),
            'has_admin_email' => isset($data['admin_email']),
            'has_admin_password' => isset($data['admin_password']),
            'has_app_env' => isset($data['app_env']),
            'has_app_url' => isset($data['app_url']),
            'has_admin_url' => isset($data['admin_url']),
            'has_app_timezone' => isset($data['app_timezone']),
            'has_db_connection' => isset($data['db_connection']),
            'has_db_host' => isset($data['db_host']),
            'has_db_port' => isset($data['db_port']),
            'has_db_database' => isset($data['db_database']),
            'has_db_username' => isset($data['db_username']),
            'full_data_keys' => $data ? array_keys($data) : 'empty',
        ]);

        // Check required fields and redirect to appropriate step based on missing fields.
        //
        // SQLite has no server to reach, so the database step neither shows nor
        // submits host / port / username, and InstallDatabaseController blanks
        // them in .env on purpose (a leftover DB_HOST=mysql from the docker
        // preset would otherwise make other code paths attempt a TCP connect).
        // Requiring them here made every SQLite installation fail this check.
        $steps = [
            'settings' => ['site_name', 'admin_account_name', 'admin_email', 'admin_password'],
            'environment' => ['app_env', 'app_url', 'admin_url', 'app_timezone'],
            'database' => $this->requiredDatabaseFields($data['db_connection'] ?? null),
        ];

        // Start over if session data is completely empty
        if (empty($data)) {
            Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.session_data_completely_empty'));

            return redirect()->route('install.index')
                ->with('error', __('http/controllers/install/install_confirm_controller.session_data_lost_restart_installation'));
        }

        // Check required fields for each step
        $missingFields = [];
        foreach ($steps as $step => $fields) {
            foreach ($fields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = ['step' => $step, 'field' => $field];
                }
            }
        }

        // If there are missing fields
        if (! empty($missingFields)) {
            $firstMissing = $missingFields[0];
            Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.required_fields_missing'), [
                'missing_fields' => $missingFields,
                'session_keys' => array_keys($data),
                'session_id' => session()->getId(),
                'redirecting_to_step' => $firstMissing['step'],
            ]);

            // Route names are `install.settings` / `install.environment` /
            // `install.database` — the GET step screens. Appending `.create`
            // (the controller method name) produced undefined route names, so
            // this recovery path threw RouteNotFoundException instead of
            // sending the operator back to fix the field.
            return redirect()->route('install.'.$firstMissing['step'])
                ->with('error', __('install/common.missing_required_fields').__('http/controllers/install/install_confirm_controller.missing_field', ['field' => $firstMissing['field']]));
        }

        // A marker older than the run window is what a killed execution
        // leaves behind (the built-in server restarting on the .env write,
        // a request timeout, a closed tab). Say so once, then clear it, so
        // the operator knows why they are back here.
        if (InstallRunLock::wasInterrupted()) {
            Log::channel('install')->warning('A previous installation attempt did not finish', [
                'started_at' => InstallRunLock::startedAt(),
            ]);
            InstallRunLock::release();
            session()->flash('warning', __('install/confirm.previous_run_interrupted'));
        }

        // Retrieve email test results from session
        $mailTestStatus = [
            'connection_tested' => (bool) ($data['mail_connection_tested'] ?? false),
            'connection_test_date' => $data['mail_connection_test_date'] ?? null,
            'send_tested' => (bool) ($data['mail_send_tested'] ?? false),
            'send_test_date' => $data['mail_send_test_date'] ?? null,
            'receive_tested' => (bool) ($data['mail_receive_tested'] ?? false),
            'receive_test_date' => $data['mail_receive_test_date'] ?? null,
        ];

        return view('install.confirm', [
            'data' => $data,
            'mailTestStatus' => $mailTestStatus,
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
        ]);

        // NOTE: the visible core-integrity pre-flight panel is intentionally not
        // shown on the confirm screen for the initial release (the core
        // signature-check feature is not surfaced to users yet). The store()
        // INVALID guard below is kept as a dormant safety net — it only ever
        // triggers for a genuinely tampered *signed* core, which cannot happen
        // while core is unsigned. Re-introduce coreIntegrityPanel() + the
        // confirm.blade.php panel when shipping core signing.
        // See .backlog/core-signing-deferred.md.
    }

    /**
     * Required database fields for the chosen driver.
     *
     * SQLite only needs the file path; every other driver needs the server
     * coordinates as well. Kept as its own method so the confirm screen and
     * its tests share one definition.
     *
     * @return array<int, string>
     */
    protected function requiredDatabaseFields(?string $connection): array
    {
        if ($connection === 'sqlite') {
            return ['db_connection', 'db_database'];
        }

        return ['db_connection', 'db_host', 'db_port', 'db_database', 'db_username'];
    }

    /**
     * Execute installation
     */
    public function store()
    {
        // One execution at a time. This method rewrites .env, migrates,
        // seeds and creates the first administrator; a double-submitted
        // form, an impatient reload or a proxy retry used to start a
        // second run straight through the middle of the first, and two
        // concurrent `migrate:fresh` calls against one database is the
        // worst possible way to discover that.
        if (! InstallRunLock::acquire()) {
            Log::channel('install')->warning('Installation already running; refused a concurrent execution', [
                'started_at' => InstallRunLock::startedAt(),
            ]);

            return redirect()->route('install.confirm')
                ->with('error', __('install/confirm.already_running'));
        }

        $data = [];

        try {
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.installation_started'));

            $data = session('install_data');
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.session_data_retrieved'), [
                'keys' => array_keys($data ?? []),
                'session_id' => session()->getId(),
                'full_data' => $data,
            ]);

            // Redirect to confirmation screen if session data is empty
            if (empty($data)) {
                Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.session_empty_redirect_to_confirm'));

                return redirect()->route('install.confirm')
                    ->with('error', '');
            }

            // Decrypt administrator password
            $adminPassword = Crypt::decryptString($data['admin_password']);
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.admin_password_decrypted'));

            // Decrypt DB password (do not decrypt if empty string)
            $dbPassword = (! empty($data['db_password'])) ? Crypt::decryptString($data['db_password']) : '';
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.db_password_decrypted'));

            // Decrypt email password (do not decrypt if empty string)
            $mailPassword = (! empty($data['mail_password'])) ? Crypt::decryptString($data['mail_password']) : '';
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.mail_password_decrypted'));

            // The service does not read the session; hand it the locale the
            // wizard is running in.
            $data['app_locale'] ??= session('install_locale', 'ja');

            $runner = app(InstallRunner::class);

            try {
                $data = $runner->prepare($data);
            } catch (SqliteDatabaseUnusableException $e) {
                return redirect()->route('install.database')
                    ->with('error', __('install/step3.db_sqlite_file_error', ['path' => $e->path]));
            }

            // Keep the normalisation, so a retry starts from the resolved
            // path rather than the raw field.
            session(['install_data' => array_merge(session('install_data', []), $data)]);

            // Keep this request's session off the database while the
            // migrations run — `migrate:fresh` drops the sessions table
            // underneath it. In-memory only: .env is written once, with the
            // value the installed site boots with.
            config(['session.driver' => 'file']);
            app()->forgetInstance('session');
            app()->forgetInstance('session.store');
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.session_driver_changed_to_file'), ['original' => 'in-memory only']);

            try {
                $runner->run($data, $adminPassword, $dbPassword, $mailPassword);
            } catch (CoreIntegrityBlockedException $e) {
                return redirect()->route('install.confirm')
                    ->with('error', __('install/confirm.integrity.blocked'));
            }

            // Delete session data
            session()->forget('install_data');
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.installation_completed'));
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.redirecting_to_install_complete'));

            return redirect()->route('install.complete');
        } catch (\Exception $e) {
            Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.installation_error'), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'session_data_exists' => ! empty($data),
                'session_keys' => array_keys($data ?? []),
            ]);

            // Verify session data is not lost
            if (empty(session('install_data'))) {
                Log::channel('install')->warning(__('http/controllers/install/install_confirm_controller.session_data_lost_attempting_recovery'));
                if (! empty($data)) {
                    session(['install_data' => $data]);
                    Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.session_data_restored'));
                }
            }

            // Create user-friendly error message
            $errorMessage = $this->getInstallationErrorMessage($e);

            return redirect()->route('install.mode')
                ->with('error', $errorMessage)
                ->with('error_details', $e->getMessage());
        } finally {
            // Whichever way this ended, the run is over. A crash that never
            // reaches here leaves the marker behind on purpose: the next
            // attempt reports the interruption instead of pretending
            // nothing happened.
            InstallRunLock::release();
        }
    }

    /**
     * Get user-friendly error message for installation errors
     */
    private function getInstallationErrorMessage(\Exception $e)
    {
        $errorMessage = $e->getMessage();

        if (strpos($errorMessage, 'database') !== false) {
            return __('install/error.database_general');
        }

        if (strpos($errorMessage, 'file') !== false || strpos($errorMessage, 'directory') !== false) {
            return __('install/error.file_system');
        }

        if (strpos($errorMessage, 'env') !== false || strpos($errorMessage, 'environment') !== false) {
            return __('install/error.environment');
        }

        return __('install/error.unknown');
    }
}
