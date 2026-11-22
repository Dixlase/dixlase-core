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

namespace Tests\Unit\DTO\Fields;

use App\DTO\Fields\FieldDefinition;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * A field described as data (#1105).
 */
class FieldDefinitionTest extends TestCase
{
    public function test_it_round_trips_through_an_array(): void
    {
        $field = new FieldDefinition('price', 'number', 'Price', ['min' => 0], true, 'In yen');

        $this->assertSame([
            'key' => 'price',
            'type' => 'number',
            'label' => 'Price',
            'options' => ['min' => 0],
            'required' => true,
            'help' => 'In yen',
        ], $field->toArray());

        $this->assertEquals($field, FieldDefinition::fromArray($field->toArray()));
        $this->assertSame(json_encode($field->toArray()), json_encode($field));
    }

    public function test_from_array_fills_the_defaults(): void
    {
        $field = FieldDefinition::fromArray(['key' => 'title', 'type' => 'text', 'label' => 'Title']);

        $this->assertSame([], $field->options);
        $this->assertFalse($field->required);
        $this->assertNull($field->help);
    }

    public function test_option_returns_the_default_when_unset(): void
    {
        $field = new FieldDefinition('title', 'text', 'Title', ['max_length' => 80]);

        $this->assertSame(80, $field->option('max_length'));
        $this->assertSame('x', $field->option('placeholder', 'x'));
    }

    public function test_a_key_that_cannot_be_an_input_name_is_refused(): void
    {
        foreach (['Title', '1st', 'my-field', 'a.b', 'fields[x]', ''] as $key) {
            try {
                new FieldDefinition($key, 'text', 'Label');
                $this->fail("Key [{$key}] should have been refused.");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_from_array_needs_key_type_and_label(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FieldDefinition::fromArray(['key' => 'title', 'type' => 'text']);
    }
}
