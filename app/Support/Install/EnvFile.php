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

declare(strict_types=1);

namespace App\Support\Install;

use Illuminate\Support\Facades\File;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Reads and writes the installation's .env file.
 *
 * The install path rewrites .env and then keeps working in the same
 * process, where `env()` and the cached config have already gone stale,
 * so it reads the file back rather than the environment. Shared by the
 * wizard's controllers and by `dls:install`.
 */
class EnvFile
{
    /**
     * Set or add entries, leaving everything else in the file untouched.
     *
     * @param  array<string, mixed>  $values
     */
    public static function update(array $values): void
    {
        $envPath = base_path('.env');

        // Copy from .env.example if .env does not exist
        if (! File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $env = File::get($envPath);

        foreach ($values as $key => $value) {
            $formattedValue = self::formatValue($value);

            if (preg_match("/^{$key}=/m", $env)) {
                // Update existing value
                $env = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$formattedValue}",
                    $env
                );
            } else {
                // Append to end if not present in .env
                $env .= "\n{$key}={$formattedValue}";
            }
        }

        File::put($envPath, $env);
    }

    /**
     * Read a single value, or '' when the file or the entry is absent.
     */
    public static function read(string $key, ?string $envPath = null): string
    {
        $envPath ??= base_path('.env');

        if (! File::exists($envPath)) {
            return '';
        }

        if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', File::get($envPath), $matches)) {
            return '';
        }

        return trim(trim($matches[1]), "\"'");
    }

    /**
     * Quote a value where .env syntax needs it.
     */
    public static function formatValue(mixed $value): string
    {
        // Empty string if null
        if ($value === null) {
            return '';
        }

        // Convert to string if boolean
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        // Enclose in quotes if empty string, space, or special characters are included
        if ($value === '' ||
            preg_match('/[\s"\'#$]/', $value) ||
            str_contains($value, '=')) {
            // Leave as-is if already enclosed in quotes
            if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
                return $value;
            }

            // Enclose in double quotes (escape internal double quotes)
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
