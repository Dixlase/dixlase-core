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

namespace Tests\Feature\Admin;

use App\Contracts\PluginIntegration\TranslationUiProviderInterface;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * <x-admin.translate-button> links to whichever provider translates the
 * content type, and renders nothing when there is none to link to.
 */
class TranslateButtonComponentTest extends TestCase
{
    private const URL = 'https://example.test/translations/demo-plugin:post/5';

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
    }

    public function test_renders_nothing_without_a_provider(): void
    {
        $this->assertSame('', trim($this->render(5)));
    }

    public function test_links_to_the_provider_that_translates_the_type(): void
    {
        $this->registerProvider(['demo-plugin:post' => self::URL]);

        $html = $this->render(5, 'ja');

        $this->assertStringContainsString('href="'.self::URL.'"', $html);
        $this->assertStringContainsString('Translate', $html);
        $this->assertStringContainsString(__('common.languages.ja'), $html);
    }

    public function test_renders_nothing_for_an_unsaved_record(): void
    {
        $this->registerProvider(['demo-plugin:post' => self::URL]);

        $this->assertSame('', trim($this->render(null)));
    }

    public function test_renders_nothing_when_no_provider_translates_the_type(): void
    {
        $this->registerProvider(['other-plugin:item' => self::URL]);

        $this->assertSame('', trim($this->render(5)));
    }

    public function test_skips_an_unavailable_provider(): void
    {
        $this->registerProvider(['demo-plugin:post' => self::URL], available: false);

        $this->assertSame('', trim($this->render(5)));
    }

    private function render(?int $id, ?string $sourceLocale = null): string
    {
        return Blade::render(
            '<x-admin.translate-button type="demo-plugin:post" :id="$id" :source-locale="$sourceLocale" />',
            ['id' => $id, 'sourceLocale' => $sourceLocale],
        );
    }

    /**
     * @param  array<string, string>  $urls
     */
    private function registerProvider(array $urls, bool $available = true): void
    {
        // Registered the way a plugin's ServiceProvider does it.
        $this->app->instance('test.translation-ui', new class($urls, $available) implements TranslationUiProviderInterface
        {
            /**
             * @param  array<string, string>  $urls
             */
            public function __construct(private array $urls, private bool $available) {}

            public function getPluginSlug(): string
            {
                return 'demo-plugin';
            }

            public function isCapabilityAvailable(): bool
            {
                return $this->available;
            }

            public function getTranslationEditUrl(string $type, int|string $id): ?string
            {
                return $this->urls[$type] ?? null;
            }
        });
        $this->app->tag(['test.translation-ui'], PluginServiceResolver::CAPABILITY_TAG);
    }
}
