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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Services\Plugin;

use Illuminate\Support\Facades\File;

/**
 * @internal Core-only service. Plugins/themes must not depend on this directly.
 *
 * Statically inspects a plugin's or theme's `database/migrations/*.php` files
 * and extracts the table names it owns (i.e. tables declared via Schema::create).
 *
 * Used by the admin scan/detail screens to surface "this extension creates the
 * following tables" without having to read migration sources by hand.
 */
class PluginTableInspector
{
    /**
     * Inspect an extension directory and return its owned table names.
     *
     * @return array{
     *     tables: array<int, string>,
     *     dynamic_count: int,
     *     has_migrations: bool,
     * }
     */
    public function inspect(string $extensionDir): array
    {
        $migrationsDir = "{$extensionDir}/database/migrations";

        if (! File::isDirectory($migrationsDir)) {
            return [
                'tables' => [],
                'dynamic_count' => 0,
                'has_migrations' => false,
            ];
        }

        $files = File::files($migrationsDir);
        if (empty($files)) {
            return [
                'tables' => [],
                'dynamic_count' => 0,
                'has_migrations' => false,
            ];
        }

        $tables = [];
        $dynamicCount = 0;

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = $this->stripComments(File::get($file->getPathname()));

            // Schema::create('table_name', ...)
            if (preg_match_all('/Schema::create\s*\(\s*([\'"])([^\'"]+)\1/', $source, $matches)) {
                foreach ($matches[2] as $name) {
                    $tables[$name] = true;
                }
            }

            // Schema::create($var, ...) / Schema::create(constant, ...) — first arg is not a string literal
            if (preg_match_all('/Schema::create\s*\(\s*(?![\'"])/', $source, $dynamicMatches)) {
                $dynamicCount += count($dynamicMatches[0]);
            }
        }

        return [
            'tables' => array_values(array_keys($tables)),
            'dynamic_count' => $dynamicCount,
            'has_migrations' => true,
        ];
    }

    /**
     * Strip PHP block and line comments so they don't pollute the regex matches.
     *
     * Conservative: doesn't try to handle string-literal corner cases (e.g. a
     * literal "// not a comment" inside a string), which don't appear in
     * realistic migration files.
     */
    protected function stripComments(string $source): string
    {
        $source = preg_replace('/\/\*.*?\*\//s', '', $source) ?? $source;
        $source = preg_replace('/(^|\s)\/\/[^\n]*/m', '$1', $source) ?? $source;

        return $source;
    }
}
