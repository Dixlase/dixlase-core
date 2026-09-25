<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace Tests\Unit\Services\Site;

use App\Contracts\Multilingual\SingletonTranslationResolver;
use App\Services\Site\PageTitleBuilder;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * PageTitleBuilder::tagline() consults DixlaseMultilingual's singleton
 * translation resolver so /ja and /en can show different taglines when
 * the operator has authored per-locale values. Fall-through order:
 *   1. Resolver bound + returns non-empty translation → translation.
 *   2. Resolver not bound / returns null / returns '' → primary value
 *      from ConfigHelper (site_settings.site_tagline).
 *
 * The primary value is driven through `config('app.tagline')` here so
 * the tests do not need a settings table.
 */
class PageTitleBuilderTaglineTranslationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Give ConfigHelper::getSiteTagline() a deterministic primary
        // value without touching site_settings. app.tagline is the
        // config-level fallback consulted by that helper.
        config(['app.tagline' => 'Primary tagline (JA)']);
        // Neutralise siteName so the assertions read only what the
        // tagline branch produced.
        config(['app.name' => 'Dixlase']);
    }

    public function test_bound_resolver_translation_replaces_primary_tagline_in_root_title(): void
    {
        $this->bindResolver(['en' => 'Translated tagline (EN)']);
        App::setLocale('en');

        $this->assertSame(
            'Dixlase - Translated tagline (EN)',
            (new PageTitleBuilder())->build(),
        );
    }

    public function test_bound_resolver_returning_empty_falls_back_to_primary_tagline(): void
    {
        // Explicitly-empty translation must not blank out the title;
        // it means "no locale-specific override" and the primary value
        // stays in the composed title.
        $this->bindResolver(['en' => '']);
        App::setLocale('en');

        $this->assertSame(
            'Dixlase - Primary tagline (JA)',
            (new PageTitleBuilder())->build(),
        );
    }

    public function test_unbound_resolver_leaves_primary_tagline_intact(): void
    {
        // Multilingual plugin absent / disabled: the container has no
        // SingletonTranslationResolver binding, and PageTitleBuilder
        // must degrade gracefully to the stored primary value.
        $this->app->forgetInstance(SingletonTranslationResolver::class);
        $this->app->offsetUnset(SingletonTranslationResolver::class);
        App::setLocale('en');

        $this->assertSame(
            'Dixlase - Primary tagline (JA)',
            (new PageTitleBuilder())->build(),
        );
    }

    /**
     * Bind a stand-in SingletonTranslationResolver that serves a fixed
     * locale => value map for the `core:site-tagline` / `tagline` pair
     * and nothing else. Mimics how DixlaseMultilingual answers a lookup
     * once the operator has stored per-locale translations.
     *
     * @param  array<string, string>  $localeMap
     */
    private function bindResolver(array $localeMap): void
    {
        $resolver = new class($localeMap) implements SingletonTranslationResolver
        {
            /** @param array<string, string> $map */
            public function __construct(private array $map) {}

            public function resolve(string $typeKey, string $field, string $locale): mixed
            {
                if ($typeKey !== 'core:site-tagline' || $field !== 'tagline') {
                    return null;
                }

                return $this->map[$locale] ?? null;
            }

            public function store(string $typeKey, string $field, mixed $value, string $locale): void {}

            public function all(string $typeKey, string $field): array
            {
                return $typeKey === 'core:site-tagline' && $field === 'tagline'
                    ? $this->map
                    : [];
            }

            public function exists(string $typeKey, string $field, string $locale): bool
            {
                return $typeKey === 'core:site-tagline'
                    && $field === 'tagline'
                    && isset($this->map[$locale])
                    && $this->map[$locale] !== '';
            }

            public function delete(string $typeKey, string $field, ?string $locale = null): void {}

            public function getAvailableLocales(string $typeKey): array
            {
                return $typeKey === 'core:site-tagline' ? array_keys($this->map) : [];
            }
        };

        $this->app->instance(SingletonTranslationResolver::class, $resolver);
    }
}
