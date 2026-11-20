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

use App\Contracts\TranslationSearch;
use App\Helpers\LocaleHelper;
use App\Models\Member;
use Tests\TestCase;

/**
 * LocaleHelper::findTranslatedKeys() asks the translation provider which
 * models' translations contain the words, and answers an empty list when
 * no provider is installed.
 */
class TranslationSearchTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app->offsetUnset(TranslationSearch::class);
        parent::tearDown();
    }

    public function test_is_empty_when_no_provider_is_bound(): void
    {
        $this->app->offsetUnset(TranslationSearch::class);

        $this->assertSame([], LocaleHelper::findTranslatedKeys(Member::class, ['title'], 'en', ['word']));
    }

    public function test_ignores_a_binding_that_does_not_implement_the_contract(): void
    {
        $this->app->instance(TranslationSearch::class, new \stdClass());

        $this->assertSame([], LocaleHelper::findTranslatedKeys(Member::class, ['title'], 'en', ['word']));
    }

    public function test_passes_the_query_to_the_provider_and_returns_its_keys(): void
    {
        $search = $this->bindSearch([3, 7]);

        $keys = LocaleHelper::findTranslatedKeys(Member::class, ['title', 'content'], 'en', ['alpha', 'beta']);

        $this->assertSame([3, 7], $keys);
        $this->assertSame([[Member::class, ['title', 'content'], 'en', ['alpha', 'beta']]], $search->calls);
    }

    public function test_drops_empty_and_duplicate_words_and_fields(): void
    {
        $search = $this->bindSearch([1]);

        LocaleHelper::findTranslatedKeys(Member::class, ['title', '', 'title', 5], 'en', ['alpha', '', 'alpha', null]);

        $this->assertSame([[Member::class, ['title'], 'en', ['alpha']]], $search->calls);
    }

    public function test_does_not_ask_the_provider_when_nothing_is_left_to_search(): void
    {
        $search = $this->bindSearch([1]);

        $this->assertSame([], LocaleHelper::findTranslatedKeys(Member::class, ['title'], 'en', ['', '']));
        $this->assertSame([], LocaleHelper::findTranslatedKeys(Member::class, [], 'en', ['alpha']));
        $this->assertSame([], LocaleHelper::findTranslatedKeys(Member::class, ['title'], '', ['alpha']));
        $this->assertSame([], $search->calls);
    }

    public function test_keeps_only_distinct_usable_keys(): void
    {
        $this->bindSearch([4, 'uuid-1', 4, '', null, 1.5, 'uuid-1']);

        $this->assertSame([4, 'uuid-1'], LocaleHelper::findTranslatedKeys(Member::class, ['title'], 'en', ['alpha']));
    }

    /**
     * @param  array<mixed>  $keys
     */
    private function bindSearch(array $keys): object
    {
        $search = new class($keys) implements TranslationSearch
        {
            /** @var list<array{string, array<mixed>, string, array<mixed>}> */
            public array $calls = [];

            /**
             * @param  array<mixed>  $keys
             */
            public function __construct(private array $keys) {}

            public function findKeys(string $modelClass, array $fields, string $locale, array $words): array
            {
                $this->calls[] = [$modelClass, $fields, $locale, $words];

                /** @var list<int|string> */
                return $this->keys;
            }
        };

        $this->app->instance(TranslationSearch::class, $search);

        return $search;
    }
}
