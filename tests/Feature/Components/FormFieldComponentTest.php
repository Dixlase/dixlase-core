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

namespace Tests\Feature\Components;

use App\DTO\Fields\FieldDefinition;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * <x-form-field> draws each core field type's input (#1105).
 */
class FormFieldComponentTest extends TestCase
{
    private function render(FieldDefinition $field, mixed $value = null, ?MessageBag $errors = null): string
    {
        $bag = new ViewErrorBag();
        if ($errors) {
            $bag->put('default', $errors);
        }
        view()->share('errors', $bag);

        return Blade::render('<x-form-field :field="$field" :value="$value" />', [
            'field' => $field,
            'value' => $value,
        ]);
    }

    public function test_each_core_type_draws_its_input_under_the_label(): void
    {
        $cases = [
            ['text', [], 'Hello', 'type="text"'],
            ['textarea', [], 'Hello', '<textarea'],
            ['number', ['min' => 1], 3, 'type="number"'],
            ['toggle', [], true, 'type="checkbox"'],
            ['select', ['choices' => ['s' => 'Small']], 's', '<select'],
            ['date', [], '2026-11-22', 'type="date"'],
            ['datetime', [], '2026-11-22T00:30:00+00:00', 'type="datetime-local"'],
            ['url', [], 'https://example.com', 'type="url"'],
        ];

        foreach ($cases as [$type, $options, $value, $marker]) {
            $html = $this->render(new FieldDefinition('the_field', $type, 'The label', $options), $value);

            $this->assertStringContainsString('The label', $html, $type);
            $this->assertStringContainsString($marker, $html, $type);
            $this->assertStringContainsString('name="fields[the_field]"', $html, $type);
        }
    }

    public function test_it_shows_the_value_help_and_errors(): void
    {
        $html = $this->render(
            new FieldDefinition('title', 'text', 'Title', help: 'Shown <b>escaped</b>'),
            'Hello',
            new MessageBag(['fields.title' => ['The title is too long.']]),
        );

        $this->assertStringContainsString('value="Hello"', $html);
        $this->assertStringContainsString('Shown &lt;b&gt;escaped&lt;/b&gt;', $html);
        $this->assertStringContainsString('The title is too long.', $html);
        $this->assertStringContainsString('aria-describedby="field-fields-title-help"', $html);
    }

    public function test_an_optional_select_offers_no_choice(): void
    {
        $html = $this->render(new FieldDefinition('size', 'select', 'Size', ['choices' => ['s' => 'Small']]));

        $this->assertStringContainsString('value=""', $html);
    }

    public function test_an_unknown_type_draws_nothing(): void
    {
        $this->assertSame('', trim($this->render(new FieldDefinition('x', 'gone.type', 'X'))));
    }
}
