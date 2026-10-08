<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

declare(strict_types=1);

namespace Tests\Unit\Audit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Guards the deprecation window of App\Traits\AuditableTrait.
 *
 * The trait is deprecated and scheduled for removal in v0.2.0. Auditing goes through
 * the Action layer, so core must not start using the trait in the meantime — a single
 * adoption would turn the removal into a behaviour change instead of a doc change.
 */
class AuditableTraitDeprecationTest extends TestCase
{
    public function test_the_trait_is_still_marked_deprecated(): void
    {
        $source = file_get_contents(self::repoPath('app/Traits/AuditableTrait.php'));

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '@deprecated',
            $source,
            'AuditableTrait must stay marked @deprecated until it is removed in v0.2.0.'
        );
    }

    public function test_no_core_class_uses_the_deprecated_trait(): void
    {
        $adopters = [];

        foreach (self::phpFilesUnder(self::repoPath('app')) as $path) {
            if (str_ends_with($path, '/Traits/AuditableTrait.php')) {
                continue;
            }

            $source = file_get_contents($path);

            if ($source === false) {
                continue;
            }

            // A real adoption is a `use AuditableTrait;` statement inside a class body.
            // Mentions in comments and docblocks are fine, so strip them first.
            if (preg_match('/^\s*use\s+(\\\\?App\\\\Traits\\\\)?AuditableTrait\s*;/m', self::withoutComments($source)) === 1) {
                $adopters[] = $path;
            }
        }

        $this->assertSame(
            [],
            $adopters,
            "The Action layer is the only audit producer. Do not adopt the deprecated AuditableTrait:\n"
            .implode("\n", $adopters)
        );
    }

    /**
     * The repository root, three levels above `tests/Unit/Audit`.
     */
    private static function repoPath(string $relative): string
    {
        return \dirname(__DIR__, 3).'/'.$relative;
    }

    /**
     * @return list<string>
     */
    private static function phpFilesUnder(string $dir): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private static function withoutComments(string $source): string
    {
        $out = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $out .= is_array($token) ? $token[1] : $token;
        }

        return $out;
    }
}
