<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
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

namespace Tests\Feature\Site;

use Database\Seeders\SitesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Site::name is the canonical site name -- CoreSettingDefinitions says so on
 * both the site_name setting ("Site::name on the model holds the canonical
 * site name") and the app_name setting ("Site-facing display names live on
 * the Site model itself").
 *
 * The implementation did not follow. SitesSeeder created the row with the
 * placeholder 'Main Site' and the installer never replaced it, so the
 * canonical column disagreed with the operator's own site name from the
 * first request onwards. Measured on a working installation before the fix:
 *
 *     config('app.name') = 'Dixlase Dev'
 *     sites.name         = 'Main Site'
 *
 * The seeder made it worse: updateOrInsert() passed every column, so
 * re-running db:seed on a live installation reset name, slug, host, locale
 * and timezone to their defaults. Measured: a site named '運用中のサイト名'
 * in Asia/Tokyo came back as 'Main Site' in UTC.
 */
class CanonicalSiteNameTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seeders are expected to be re-runnable. This one owns a row an operator
     * edits, so a second run has to leave it alone.
     */
    public function test_reseeding_does_not_overwrite_an_operators_site(): void
    {
        DB::table('sites')->where('id', 1)->update([
            'name' => '運用中のサイト名',
            'slug' => 'production',
            'timezone' => 'Asia/Tokyo',
        ]);

        (new SitesSeeder())->run();

        $site = DB::table('sites')->where('id', 1)->first();

        $this->assertSame('運用中のサイト名', $site->name, 'Re-seeding must not reset the site name.');
        $this->assertSame('production', $site->slug, 'Re-seeding must not reset the slug.');
        $this->assertSame('Asia/Tokyo', $site->timezone, 'Re-seeding must not reset the timezone.');
    }

    /**
     * The insert path still has to work, otherwise a fresh installation has
     * no primary site at all.
     */
    public function test_seeding_creates_the_primary_site_when_it_is_missing(): void
    {
        DB::table('sites')->where('id', 1)->delete();

        (new SitesSeeder())->run();

        $site = DB::table('sites')->where('id', 1)->first();

        $this->assertNotNull($site, 'A fresh installation needs the primary site row.');
        $this->assertSame('main', $site->slug);
        $this->assertTrue((bool) $site->is_primary);
        $this->assertTrue((bool) $site->is_active);
    }

    /**
     * The placeholder is only meant to survive until the installer writes the
     * operator's name over it. If this ever becomes the value a live site
     * runs with, the canonical column is wrong again.
     */
    public function test_the_seeded_name_is_only_a_placeholder(): void
    {
        DB::table('sites')->where('id', 1)->delete();
        (new SitesSeeder())->run();

        $this->assertSame(
            'Main Site',
            DB::table('sites')->where('id', 1)->value('name'),
            'InstallRunner::initializeDatabase() replaces this with the operator input; '
            .'if the placeholder changes, update that expectation too.'
        );
    }

    /**
     * The installer writes the operator's site name to the canonical column.
     * Pinned at the source level because initializeDatabase() runs a full
     * installation and cannot be invoked from a test.
     *
     * The pipeline moved out of InstallConfirmController into InstallRunner
     * when `dls:install` started sharing it; the assertion follows it.
     */
    public function test_the_installer_writes_the_canonical_column(): void
    {
        $source = file_get_contents(
            base_path('app/Services/Install/InstallRunner.php')
        );

        $this->assertStringContainsString(
            "->update(['name' => \$data['site_name']])",
            $source,
            'The installer must write the operator site name to Site::name, not only to .env and the settings tables.'
        );
    }
}
