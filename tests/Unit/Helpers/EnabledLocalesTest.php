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

use App\Contracts\I18n\EnabledLocales;
use App\Helpers\LocaleHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LocaleHelper answers the enabled-locales questions from the bound
 * translation provider, and for a single-language site when none is bound.
 */
class EnabledLocalesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->app->offsetUnset(EnabledLocales::class);
        parent::tearDown();
    }

    public function test_answers_for_a_single_language_site_when_nothing_is_bound(): void
    {
        $this->app->offsetUnset(EnabledLocales::class);
        $primary = LocaleHelper::getSiteDefaultLocale();

        $this->assertSame([$primary], LocaleHelper::enabledLocales());
        $this->assertSame($primary, LocaleHelper::defaultEnabledLocale());
        $this->assertTrue(LocaleHelper::isLocaleEnabled($primary));
        $this->assertFalse(LocaleHelper::isLocaleEnabled('xx'));
        $this->assertFalse(LocaleHelper::defaultLocaleIsPinned());
    }

    public function test_follows_the_bound_provider(): void
    {
        $this->bindProvider(['ja', 'en', 'fr'], 'en', true);

        $this->assertSame(['ja', 'en', 'fr'], LocaleHelper::enabledLocales());
        $this->assertSame('en', LocaleHelper::defaultEnabledLocale());
        $this->assertTrue(LocaleHelper::isLocaleEnabled('fr'));
        $this->assertFalse(LocaleHelper::isLocaleEnabled('de'));
        $this->assertTrue(LocaleHelper::defaultLocaleIsPinned());

        $this->bindProvider(['ja', 'en'], 'ja', false);
        $this->assertFalse(LocaleHelper::defaultLocaleIsPinned());
    }

    public function test_puts_a_missing_default_first_and_drops_duplicates_and_blanks(): void
    {
        $this->bindProvider(['en', '', 'fr', 'en'], 'ja', false);

        $this->assertSame(['ja', 'en', 'fr'], LocaleHelper::enabledLocales());
    }

    public function test_falls_back_to_the_primary_locale_when_the_provider_gives_no_default(): void
    {
        $this->bindProvider(['fr'], '', false);
        $primary = LocaleHelper::getSiteDefaultLocale();

        $this->assertSame($primary, LocaleHelper::defaultEnabledLocale());
        $this->assertSame([$primary, 'fr'], LocaleHelper::enabledLocales());
    }

    public function test_ignores_a_binding_that_does_not_implement_the_contract(): void
    {
        $this->app->instance(EnabledLocales::class, new \stdClass());

        $this->assertSame([LocaleHelper::getSiteDefaultLocale()], LocaleHelper::enabledLocales());
        $this->assertFalse(LocaleHelper::defaultLocaleIsPinned());
    }

    /**
     * @param  list<string>  $locales
     */
    private function bindProvider(array $locales, string $default, bool $pinned): void
    {
        $this->app->instance(EnabledLocales::class, new class($locales, $default, $pinned) implements EnabledLocales
        {
            /**
             * @param  list<string>  $locales
             */
            public function __construct(private array $locales, private string $default, private bool $pinned) {}

            public function locales(): array
            {
                return $this->locales;
            }

            public function defaultLocale(): string
            {
                return $this->default;
            }

            public function defaultLocaleIsPinned(): bool
            {
                return $this->pinned;
            }
        });
    }
}
