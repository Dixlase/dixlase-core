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

namespace Tests\Unit\Services\Fields;

use App\Contracts\PluginIntegration\FieldTypeInterface;
use App\DTO\Fields\FieldDefinition;
use App\Fields\Types\TextFieldType;
use App\Services\Fields\FieldTypeRegistry;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Collecting field types from core and extensions (#1105).
 */
class FieldTypeRegistryTest extends TestCase
{
    /**
     * Tag a field type the way an extension's service provider would
     */
    private function registerExtensionType(string $slug, string $key): FieldTypeInterface
    {
        $type = new class($slug, $key) extends TextFieldType
        {
            public function __construct(private string $slug, private string $typeKey) {}

            public function getPluginSlug(): string
            {
                return $this->slug;
            }

            public function key(): string
            {
                return $this->typeKey;
            }

            public function label(): string
            {
                return 'Map';
            }
        };

        // Anonymous classes from one statement share a class string, so each
        // gets its own binding key.
        $binding = 'test.field-type.'.$slug.'.'.$key;
        $this->app->instance($binding, $type);
        $this->app->tag([$binding], PluginServiceResolver::CAPABILITY_TAG);
        app(FieldTypeRegistry::class)->refresh();

        return $type;
    }

    public function test_it_lists_the_core_types_in_order(): void
    {
        $this->assertSame(
            ['text', 'textarea', 'number', 'toggle', 'select', 'date', 'datetime', 'url'],
            array_keys(app(FieldTypeRegistry::class)->all()),
        );
        $this->assertSame([], app(FieldTypeRegistry::class)->leftOut());
    }

    public function test_it_is_a_singleton(): void
    {
        $this->assertSame(app(FieldTypeRegistry::class), app(FieldTypeRegistry::class));
    }

    public function test_an_extension_type_is_listed_after_the_core_types(): void
    {
        $type = $this->registerExtensionType('acme-maps', 'acme-maps.map');
        $registry = app(FieldTypeRegistry::class);

        $this->assertSame('acme-maps.map', array_key_last($registry->all()));
        $this->assertSame($type, $registry->get('acme-maps.map'));
        $this->assertTrue($registry->has('text'));
    }

    public function test_a_duplicate_key_keeps_the_first_and_is_reported(): void
    {
        Log::spy();
        $first = $this->registerExtensionType('acme-maps', 'acme-maps.map');
        $this->registerExtensionType('acme-maps-fork', 'acme-maps.map');

        // The second one is refused for its prefix before it can clash, so
        // clash two types from the same extension instead.
        $this->app->instance('test.field-type.again', new class extends TextFieldType
        {
            public function getPluginSlug(): string
            {
                return 'acme-maps';
            }

            public function key(): string
            {
                return 'acme-maps.map';
            }
        });
        $this->app->tag(['test.field-type.again'], PluginServiceResolver::CAPABILITY_TAG);

        $registry = app(FieldTypeRegistry::class);
        $registry->refresh();

        $this->assertSame($first, $registry->get('acme-maps.map'));
        $reasons = array_column($registry->leftOut(), 'reason');
        $this->assertContains('duplicate', $reasons);
        $this->assertContains('unprefixed', $reasons);
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context) => $message === 'Field type left out' && $context['reason'] === 'duplicate'
        )->once();
    }

    public function test_an_extension_cannot_take_a_core_key(): void
    {
        $this->registerExtensionType('acme-maps', 'text');
        $registry = app(FieldTypeRegistry::class);

        $this->assertInstanceOf(TextFieldType::class, $registry->get('text'));
        $this->assertSame('core', $registry->get('text')->getPluginSlug());
        $this->assertSame('unprefixed', $registry->leftOut()[0]['reason']);
    }

    public function test_get_refuses_an_unknown_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(FieldTypeRegistry::class)->get('nope');
    }

    public function test_rules_for_prefixes_each_field(): void
    {
        $rules = app(FieldTypeRegistry::class)->rulesFor([
            new FieldDefinition('title', 'text', 'Title', ['max_length' => 10], true),
            new FieldDefinition('qty', 'number', 'Qty'),
        ]);

        $this->assertSame(['required', 'string', 'max:10'], $rules['fields.title']);
        $this->assertSame(['nullable', 'numeric'], $rules['fields.qty']);
    }

    public function test_api_values_skip_a_field_whose_type_is_gone(): void
    {
        $values = app(FieldTypeRegistry::class)->apiValues([
            new FieldDefinition('title', 'text', 'Title'),
            new FieldDefinition('where', 'acme-maps.map', 'Where'),
        ], ['title' => 'Hi', 'where' => 'x']);

        $this->assertSame(['title' => 'Hi'], $values);
    }
}
