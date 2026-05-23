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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class InstallDatabaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'preserve_data' => filter_var($this->input('preserve_data'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // SQLite is a file-based driver: host / port / username / password
        // are meaningless and Laravel's SQLite connector ignores them.
        // Keep them optional so the wizard accepts a SQLite install even
        // when those fields are left blank.
        $isSqlite = $this->input('db_connection') === 'sqlite';

        // Restrict the allowed drivers to those whose PDO extension is
        // actually loaded on this host so a forged form value cannot
        // bypass the dynamically filtered select on the UI.
        $allowedDrivers = [];
        if (extension_loaded('pdo_mysql')) {
            $allowedDrivers[] = 'mysql';
        }
        if (extension_loaded('pdo_sqlite')) {
            $allowedDrivers[] = 'sqlite';
        }

        return [
            'db_connection' => ['required', 'string', \Illuminate\Validation\Rule::in($allowedDrivers)],
            'db_host' => $isSqlite ? 'nullable|string' : 'required|string',
            'db_port' => $isSqlite ? 'nullable|integer' : 'required|integer',
            'db_database' => 'required|string',
            'db_username' => $isSqlite ? 'nullable|string' : 'required|string',
            'db_password' => 'nullable|string',
            'preserve_data' => 'nullable|boolean',
        ];
    }
}
