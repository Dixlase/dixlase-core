<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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

namespace Tests;

use App\Contracts\Site\SiteContextInterface;
use Database\Seeders\SitesSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            $basename = class_basename($modelName);

            // Check DixlaseCoreDevKit factories first (core model factories for testing)
            $coreDevFactory = 'Plugins\\DixlaseCoreDevKit\\Database\\Factories\\'.$basename.'Factory';
            if (class_exists($coreDevFactory)) {
                return $coreDevFactory;
            }

            // Check DixlaseDevKit factories
            $devKitFactory = 'Plugins\\DixlaseDevKit\\Database\\Factories\\'.$basename.'Factory';
            if (class_exists($devKitFactory)) {
                return $devKitFactory;
            }

            return 'Database\\Factories\\'.$basename.'Factory';
        });

        $this->ensurePrimarySiteSeeded();
    }

    /**
     * Ensure the primary site exists and SiteContext is primed.
     *
     * Multi-site foundation (v0.1.0) adds NOT NULL site_id to most tables and
     * BelongsToSite auto-fills it from SiteContext. Tests need a primary site
     * seeded so that model creates do not violate the NOT NULL constraint.
     */
    protected function ensurePrimarySiteSeeded(): void
    {
        if (! Schema::hasTable('sites')) {
            return;
        }

        (new SitesSeeder())->run();

        try {
            app(SiteContextInterface::class)->setCurrent(1);
        } catch (\Throwable) {
            // SiteContext binding may be unavailable in narrow Unit tests; ignore.
        }
    }
}
