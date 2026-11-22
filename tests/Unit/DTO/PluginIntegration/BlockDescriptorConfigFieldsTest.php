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

namespace Tests\Unit\DTO\PluginIntegration;

use App\DTO\Fields\FieldDefinition;
use App\DTO\PluginIntegration\BlockDescriptor;
use Tests\TestCase;

/**
 * Block settings described with the shared field types (#1105).
 */
class BlockDescriptorConfigFieldsTest extends TestCase
{
    public function test_config_fields_accepts_definitions_and_arrays_and_skips_the_rest(): void
    {
        $definition = new FieldDefinition('heading', 'text', 'Heading');
        $descriptor = new BlockDescriptor(
            key: 'acme.hero',
            label: 'Hero',
            configSchema: [
                $definition,
                ['key' => 'count', 'type' => 'number', 'label' => 'Count'],
                ['type' => 'object', 'properties' => []],
                'stray',
            ],
        );

        $fields = $descriptor->configFields();

        $this->assertCount(2, $fields);
        $this->assertSame($definition, $fields[0]);
        $this->assertSame('count', $fields[1]->key);
        $this->assertSame('heading', $descriptor->jsonSerialize()['config_schema'][0]->key);
        $this->assertSame('heading', json_decode(json_encode($descriptor), true)['config_schema'][0]['key']);
    }
}
