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

namespace Tests\Unit\Helpers;

use App\Contracts\Multilingual\TranslatableContentTypes;
use App\Helpers\LocaleHelper;
use Tests\TestCase;

/**
 * Plugins ask LocaleHelper whether their translatable content type is
 * registered; the bound translation provider answers.
 */
class TranslatableTypeRegisteredTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app->offsetUnset(TranslatableContentTypes::class);
        parent::tearDown();
    }

    public function test_is_false_when_no_provider_is_bound(): void
    {
        $this->app->offsetUnset(TranslatableContentTypes::class);

        $this->assertFalse(LocaleHelper::translatableTypeRegistered('example:menu'));
    }

    public function test_follows_the_bound_provider(): void
    {
        $this->bindTypes(['example:menu']);

        $this->assertTrue(LocaleHelper::translatableTypeRegistered('example:menu'));
        $this->assertFalse(LocaleHelper::translatableTypeRegistered('example:other'));
        $this->assertFalse(LocaleHelper::translatableTypeRegistered(''));
    }

    public function test_ignores_a_binding_that_does_not_implement_the_contract(): void
    {
        $this->app->instance(TranslatableContentTypes::class, new \stdClass());

        $this->assertFalse(LocaleHelper::translatableTypeRegistered('example:menu'));
    }

    /**
     * @param  list<string>  $keys
     */
    private function bindTypes(array $keys): void
    {
        $this->app->instance(TranslatableContentTypes::class, new class($keys) implements TranslatableContentTypes
        {
            /**
             * @param  list<string>  $keys
             */
            public function __construct(private array $keys) {}

            public function isRegistered(string $key): bool
            {
                return in_array($key, $this->keys, true);
            }
        });
    }
}
