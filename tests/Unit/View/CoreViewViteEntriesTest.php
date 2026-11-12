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

namespace Tests\Unit\View;

use PHPUnit\Framework\TestCase;

/**
 * Core views load their scripts from the core build
 *
 * Core builds into public/assets/build, so a bare @vite() looks for a
 * manifest that does not exist and the page fails wherever built assets
 * are used.
 */
class CoreViewViteEntriesTest extends TestCase
{
    private const VIEWS_DIR = __DIR__.'/../../../resources/views';

    public function test_every_vite_call_in_core_views_names_the_core_build(): void
    {
        $offenders = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::VIEWS_DIR));
        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            preg_match_all('/@vite\((.*?)\)\s*$/m', $source, $calls);

            foreach ($calls[1] as $arguments) {
                if (! str_contains($arguments, "'assets/build'")) {
                    $offenders[] = substr($file->getPathname(), strlen(self::VIEWS_DIR) + 1).': @vite('.$arguments.')';
                }
            }
        }

        $this->assertSame([], $offenders);
    }

    public function test_the_csp_confirmation_dialog_is_pushed_to_the_modals_stack(): void
    {
        $source = (string) file_get_contents(self::VIEWS_DIR.'/admin/settings/security/csp.blade.php');

        $push = strpos($source, "@push('modals')");
        $dialog = strpos($source, 'id="cspConfirmationModal"');

        $this->assertNotFalse($push);
        $this->assertNotFalse($dialog);
        $this->assertGreaterThan($push, $dialog);
        $this->assertLessThan(strpos($source, '@endpush', $dialog), $dialog);
    }
}
