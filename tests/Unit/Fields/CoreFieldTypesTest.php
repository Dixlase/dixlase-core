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

namespace Tests\Unit\Fields;

use App\DTO\Fields\FieldDefinition;
use App\Services\Fields\FieldTypeRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * The field types core ships (#1105): validation, the stored round trip and
 * the API value.
 */
class CoreFieldTypesTest extends TestCase
{
    private FieldTypeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = app(FieldTypeRegistry::class);
    }

    private function passes(FieldDefinition $field, mixed $input): bool
    {
        return Validator::make(['v' => $input], ['v' => $this->registry->rules($field)])->passes();
    }

    /**
     * Input -> stored -> PHP value -> API value, as an extension would run it
     *
     * @return array{stored: mixed, api: mixed}
     */
    private function roundTrip(FieldDefinition $field, mixed $input): array
    {
        $stored = $this->registry->storeInput([$field], [$field->key => $input])[$field->key];
        // Storage is JSON in practice, so the stored form must survive it.
        $stored = json_decode(json_encode($stored), true);

        return [
            'stored' => $stored,
            'api' => $this->registry->apiValues([$field], [$field->key => $stored])[$field->key],
        ];
    }

    public function test_text_validates_length_and_round_trips(): void
    {
        $field = new FieldDefinition('title', 'text', 'Title', ['max_length' => 5]);

        $this->assertTrue($this->passes($field, 'Hello'));
        $this->assertFalse($this->passes($field, 'Hello!'));
        $this->assertTrue($this->passes($field, null));
        $this->assertSame(['stored' => 'Hello', 'api' => 'Hello'], $this->roundTrip($field, 'Hello'));
    }

    public function test_a_required_field_refuses_an_empty_value(): void
    {
        $field = new FieldDefinition('title', 'text', 'Title', required: true);

        $this->assertFalse($this->passes($field, ''));
        $this->assertFalse($this->passes($field, null));
    }

    public function test_textarea_keeps_line_breaks(): void
    {
        $field = new FieldDefinition('body', 'textarea', 'Body');

        $this->assertSame("a\nb", $this->roundTrip($field, "a\nb")['api']);
    }

    public function test_url_accepts_only_http_and_https(): void
    {
        $field = new FieldDefinition('site', 'url', 'Site');

        $this->assertTrue($this->passes($field, 'https://example.com/a'));
        $this->assertFalse($this->passes($field, 'javascript:alert(1)'));
        $this->assertFalse($this->passes($field, 'ftp://example.com'));
        $this->assertFalse($this->passes($field, 'not a url'));
    }

    public function test_number_validates_bounds_and_casts(): void
    {
        $field = new FieldDefinition('qty', 'number', 'Qty', ['min' => 1, 'max' => 10, 'integer' => true]);

        $this->assertTrue($this->passes($field, '3'));
        $this->assertFalse($this->passes($field, '0'));
        $this->assertFalse($this->passes($field, '11'));
        $this->assertFalse($this->passes($field, '2.5'));
        $this->assertSame(['stored' => 3, 'api' => 3], $this->roundTrip($field, '3'));

        $decimal = new FieldDefinition('price', 'number', 'Price');
        $this->assertSame(2.5, $this->roundTrip($decimal, '2.5')['api']);
    }

    public function test_toggle_stores_a_boolean(): void
    {
        $field = new FieldDefinition('featured', 'toggle', 'Featured');

        $this->assertTrue($this->passes($field, '1'));
        $this->assertFalse($this->passes($field, 'yes please'));
        $this->assertSame(['stored' => true, 'api' => true], $this->roundTrip($field, '1'));
        $this->assertSame(['stored' => false, 'api' => false], $this->roundTrip($field, '0'));
    }

    public function test_select_accepts_only_a_listed_choice(): void
    {
        $field = new FieldDefinition('size', 'select', 'Size', ['choices' => ['s' => 'Small', 'l' => 'Large']]);

        $this->assertTrue($this->passes($field, 's'));
        $this->assertFalse($this->passes($field, 'Small'));
        $this->assertFalse($this->passes($field, 'm'));
        $this->assertSame(['stored' => 'l', 'api' => 'l'], $this->roundTrip($field, 'l'));
    }

    public function test_select_drops_a_stored_choice_that_was_removed(): void
    {
        $field = new FieldDefinition('size', 'select', 'Size', ['choices' => ['s' => 'Small']]);

        $this->assertSame(['size' => null], $this->registry->apiValues([$field], ['size' => 'l']));
    }

    public function test_date_validates_format_and_bounds(): void
    {
        $field = new FieldDefinition('day', 'date', 'Day', ['min' => '2026-01-01', 'max' => '2026-12-31']);

        $this->assertTrue($this->passes($field, '2026-11-22'));
        $this->assertFalse($this->passes($field, '2025-12-31'));
        $this->assertFalse($this->passes($field, '22/11/2026'));
        $this->assertSame(['stored' => '2026-11-22', 'api' => '2026-11-22'], $this->roundTrip($field, '2026-11-22'));
        $this->assertInstanceOf(
            CarbonImmutable::class,
            $this->registry->get('date')->fromStorage('2026-11-22', $field),
        );
    }

    public function test_datetime_reads_the_wall_clock_in_the_display_timezone_and_stores_utc(): void
    {
        config(['app.timezone' => 'UTC']);
        $field = new FieldDefinition('starts_at', 'datetime', 'Starts');
        $tz = \App\Helpers\DateTimeHelper::fieldTimezone();
        $expected = CarbonImmutable::parse('2026-11-22 09:30', $tz)->utc()->format(\DateTimeInterface::ATOM);

        $this->assertTrue($this->passes($field, '2026-11-22T09:30'));
        $this->assertFalse($this->passes($field, 'tomorrow-ish'));
        $this->assertSame(['stored' => $expected, 'api' => $expected], $this->roundTrip($field, '2026-11-22T09:30'));
    }

    public function test_an_empty_input_is_stored_as_null(): void
    {
        $fields = [
            new FieldDefinition('a', 'text', 'A'),
            new FieldDefinition('b', 'number', 'B'),
            new FieldDefinition('c', 'date', 'C'),
        ];

        $this->assertSame(['a' => null, 'b' => null, 'c' => null], $this->registry->storeInput($fields, ['a' => '']));
    }

    public function test_every_core_type_has_a_label_and_an_existing_view(): void
    {
        foreach (FieldTypeRegistry::CORE_TYPES as $class) {
            $type = app($class);

            $this->assertNotSame('components/form-field.types.'.$type->key(), $type->label(), $class);
            $this->assertTrue(view()->exists($type->view()), $class);
        }
    }
}
