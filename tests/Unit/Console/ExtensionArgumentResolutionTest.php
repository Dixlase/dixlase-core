<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Unit\Console;

use PHPUnit\Framework\TestCase;

/**
 * Commands that take a theme or plugin argument must not build the
 * directory from Str::studly() alone.
 *
 * Str::studly('dixlase-onepage') is 'DixlaseOnepage', but the directory is
 * DixlaseOnePage (likewise dixlase-seo / DixlaseSEO), so dls:theme:migrate,
 * :migrate:refresh, :migrate:rollback and :seed failed for the canonical
 * slug. Resolve through Theme / Plugin::resolveDirectoryFromSlug() first;
 * Str::studly() may stay only as its fallback.
 */
class ExtensionArgumentResolutionTest extends TestCase
{
    public function test_no_command_resolves_an_extension_argument_with_str_studly_alone(): void
    {
        $offenders = [];

        foreach (glob(__DIR__.'/../../../app/Console/Commands/*.php') ?: [] as $file) {
            $source = (string) file_get_contents($file);

            if (! preg_match_all('/^.*Str::studly\(\$this->argument\(\'(theme|plugin)\'\)\).*$/m', $source, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[0] as [$line, $offset]) {
                // Allowed only as the fallback of resolveDirectoryFromSlug().
                $context = substr($source, max(0, $offset - 200), 200 + strlen($line));
                if (! str_contains($context, 'resolveDirectoryFromSlug(')) {
                    $offenders[] = basename($file).': '.trim($line);
                }
            }
        }

        $this->assertSame([], $offenders);
    }
}
