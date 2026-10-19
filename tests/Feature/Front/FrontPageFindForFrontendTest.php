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

namespace Tests\Feature\Front;

use App\Contracts\Site\SiteContextInterface;
use App\Contracts\TranslationResolver;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Models\FrontPage;
use Database\Seeders\SitesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FrontPage::findForFrontend() picks the row the public front should render.
 *
 * Without a translation provider each locale is its own row (the installer
 * seeds one per shipped locale), so the front must prefer the row matching
 * the request locale — not simply the first row, which left a Japanese site
 * showing the English front page. With a provider bound the model is one
 * canonical row plus overlays, so the canonical row is kept.
 */
class FrontPageFindForFrontendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
        putenv('INSTALLED=true');

        $this->seed(SitesSeeder::class);

        // Default to a non-multilingual site: no translation provider bound.
        // Individual tests re-bind one to cover the provider path.
        unset($this->app[TranslationResolver::class]);
    }

    protected function tearDown(): void
    {
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        putenv('INSTALLED=false');
        parent::tearDown();
    }

    private function createRow(string $lang, string $content): FrontPage
    {
        return FrontPage::create([
            'page_type' => 'main_content',
            'lang' => $lang,
            'content' => $content,
            'editor_type' => ContentEditorType::HTML,
            'storage_type' => ContentStorageType::DATABASE,
            'status' => ContentStatus::PUBLISHED,
        ]);
    }

    private function setPrimaryLocale(string $locale): void
    {
        DB::table('sites')->where('is_primary', true)->update(['primary_locale' => $locale]);
        // SiteContext caches the Site model; re-prime it so getSiteDefaultLocale() sees the change.
        app(SiteContextInterface::class)->setCurrent(1);
    }

    public function test_it_prefers_the_row_matching_the_request_locale(): void
    {
        $this->createRow('en', '<h1>EN</h1>');
        $this->createRow('ja', '<h1>JA</h1>');

        App::setLocale('ja');
        $this->assertSame('ja', FrontPage::findForFrontend('main_content')?->lang);

        App::setLocale('en');
        $this->assertSame('en', FrontPage::findForFrontend('main_content')?->lang);
    }

    public function test_an_explicit_locale_argument_wins_over_the_app_locale(): void
    {
        $this->createRow('en', '<h1>EN</h1>');
        $this->createRow('ja', '<h1>JA</h1>');

        App::setLocale('en');
        $this->assertSame('ja', FrontPage::findForFrontend('main_content', 'ja')?->lang);
    }

    public function test_it_falls_back_to_the_site_primary_locale(): void
    {
        $this->createRow('ja', '<h1>JA</h1>');
        $this->setPrimaryLocale('ja');

        App::setLocale('en'); // no en row exists
        $this->assertSame('ja', FrontPage::findForFrontend('main_content')?->lang);
    }

    public function test_it_falls_back_to_the_first_row_when_no_locale_matches(): void
    {
        $first = $this->createRow('en', '<h1>EN</h1>');
        $this->createRow('fr', '<h1>FR</h1>');
        $this->setPrimaryLocale('de');

        App::setLocale('ja'); // neither ja nor de rows exist
        $this->assertSame($first->id, FrontPage::findForFrontend('main_content')?->id);
    }

    public function test_it_returns_null_when_there_are_no_rows(): void
    {
        $this->assertNull(FrontPage::findForFrontend('main_content'));
    }

    public function test_it_keeps_the_canonical_row_when_a_translation_provider_is_bound(): void
    {
        $first = $this->createRow('en', '<h1>EN</h1>');
        $this->createRow('ja', '<h1>JA</h1>');

        // A bound provider means one canonical row + per-locale overlays, so
        // findForFrontend must defer to findByType (the first row) and let the
        // provider do the localization — not swap to the orphaned seeded row.
        $this->mock(TranslationResolver::class);

        App::setLocale('ja');
        $this->assertSame($first->id, FrontPage::findForFrontend('main_content')?->id);
    }

    public function test_admin_lookup_find_by_type_stays_first_row_regardless_of_locale(): void
    {
        $first = $this->createRow('en', '<h1>EN</h1>');
        $this->createRow('ja', '<h1>JA</h1>');

        App::setLocale('ja');
        $this->assertSame($first->id, FrontPage::findByType('main_content')?->id);
    }
}
