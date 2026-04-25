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

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
    }
}
