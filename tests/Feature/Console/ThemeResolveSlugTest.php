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

namespace Tests\Feature\Console;

use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Round 8 re-verification found that theme migration rollback left drift for
 * themes whose declared slug does not round-trip through the directory name:
 * `DixlaseOnePage` → Str::kebab / Str::headline yields `dixlase-one-page`,
 * but theme.json declares `dixlase-onepage`. The update path records the
 * ledger under the declared (model) slug, so a rollback that re-derives the
 * slug looks under the wrong key and reverses nothing.
 *
 * `Theme::resolveSlug()` is the single resolver the migrate / migrate:rollback
 * / install commands now share. It must return the canonical declared slug —
 * never a re-derived one — whenever the theme is installed (DB row present).
 */
class ThemeResolveSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_installed_theme_resolves_to_its_recorded_slug_not_a_derived_one(): void
    {
        Theme::create([
            'name' => 'Dixlase One Page',
            'directory' => 'DixlaseOnePage',
            'slug' => 'dixlase-onepage',
        ]);

        // The directory would derive to 'dixlase-one-page'; the resolver must
        // return the recorded canonical slug instead.
        $this->assertSame('dixlase-onepage', Theme::resolveSlug('DixlaseOnePage'));
        $this->assertNotSame(Str::kebab('DixlaseOnePage'), Theme::resolveSlug('DixlaseOnePage'));
        $this->assertSame('dixlase-one-page', Str::kebab('DixlaseOnePage'), 'guards the premise of this test');
    }

    public function test_unknown_directory_with_no_row_or_manifest_falls_back_to_kebab(): void
    {
        $this->assertSame(
            Str::kebab('SomeUninstalledTheme'),
            Theme::resolveSlug('SomeUninstalledTheme'),
        );
    }
}
