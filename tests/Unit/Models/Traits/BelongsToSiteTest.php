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

namespace Tests\Unit\Models\Traits;

use App\Contracts\Site\SiteContextInterface;
use App\Models\Site;
use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BelongsToSiteTest extends TestCase
{
    use RefreshDatabase;

    private Site $primarySite;

    private Site $secondarySite;

    protected function setUp(): void
    {
        parent::setUp();

        // Primary site is auto-seeded by TestCase::ensurePrimarySiteSeeded()
        $this->primarySite = Site::query()->where('is_primary', true)->firstOrFail();

        $this->secondarySite = Site::create([
            'slug' => 'second',
            'name' => 'Second Site',
            'primary_locale' => 'en',
            'timezone' => 'UTC',
            'is_primary' => false,
            'is_active' => true,
        ]);

        Schema::create('belongs_to_site_fixtures', function ($table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('label');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('belongs_to_site_fixtures');
        BelongsToSiteFixture::clearBootedModels();

        parent::tearDown();
    }

    public function test_global_scope_filters_queries_to_current_site(): void
    {
        $this->bindSiteContextTo($this->primarySite->id);

        BelongsToSiteFixture::withoutSiteContext(function () {
            BelongsToSiteFixture::create([
                'site_id' => $this->primarySite->id,
                'label' => 'primary-row',
            ]);
            BelongsToSiteFixture::create([
                'site_id' => $this->secondarySite->id,
                'label' => 'secondary-row',
            ]);
        });

        $rows = BelongsToSiteFixture::query()->pluck('label')->all();

        $this->assertSame(['primary-row'], $rows);
    }

    public function test_creating_event_auto_fills_site_id_from_context(): void
    {
        $this->bindSiteContextTo($this->secondarySite->id);

        $row = BelongsToSiteFixture::create(['label' => 'auto-filled']);

        $this->assertSame($this->secondarySite->id, $row->site_id);
    }

    public function test_creating_event_honors_explicit_site_id(): void
    {
        $this->bindSiteContextTo($this->primarySite->id);

        $row = BelongsToSiteFixture::create([
            'site_id' => $this->secondarySite->id,
            'label' => 'explicit',
        ]);

        $this->assertSame($this->secondarySite->id, $row->site_id);
    }

    public function test_without_site_context_allows_null_site_id(): void
    {
        $this->bindSiteContextTo($this->primarySite->id);

        $row = BelongsToSiteFixture::withoutSiteContext(
            fn () => BelongsToSiteFixture::create([
                'site_id' => null,
                'label' => 'network-level',
            ])
        );

        $this->assertNull($row->site_id);
    }

    public function test_for_site_scope_queries_a_specific_site(): void
    {
        BelongsToSiteFixture::withoutSiteContext(function () {
            BelongsToSiteFixture::create([
                'site_id' => $this->primarySite->id,
                'label' => 'primary-row',
            ]);
            BelongsToSiteFixture::create([
                'site_id' => $this->secondarySite->id,
                'label' => 'secondary-row',
            ]);
        });

        $rows = BelongsToSiteFixture::query()
            ->forSite($this->secondarySite->id)
            ->pluck('label')
            ->all();

        $this->assertSame(['secondary-row'], $rows);
    }

    public function test_all_sites_scope_bypasses_filter(): void
    {
        BelongsToSiteFixture::withoutSiteContext(function () {
            BelongsToSiteFixture::create([
                'site_id' => $this->primarySite->id,
                'label' => 'a',
            ]);
            BelongsToSiteFixture::create([
                'site_id' => $this->secondarySite->id,
                'label' => 'b',
            ]);
            BelongsToSiteFixture::create([
                'site_id' => null,
                'label' => 'c',
            ]);
        });

        $rows = BelongsToSiteFixture::query()->allSites()->pluck('label')->all();

        $this->assertEqualsCanonicalizing(['a', 'b', 'c'], $rows);
    }

    private function bindSiteContextTo(int $siteId): void
    {
        $context = $this->createMock(SiteContextInterface::class);
        $context->method('currentSiteId')->willReturn($siteId);

        $this->app->instance(SiteContextInterface::class, $context);
    }
}

/**
 * Anonymous-ish fixture model. Lives in this file so its global scope is
 * registered fresh per test run and does not leak into production code.
 */
class BelongsToSiteFixture extends Model
{
    use BelongsToSite;

    protected $table = 'belongs_to_site_fixtures';

    protected $guarded = [];
}
