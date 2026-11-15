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

namespace Tests\Unit\DTO\Front;

use App\DTO\Front\FrontPageBodyDTO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FrontPageBodyDTOTest extends TestCase
{
    public function test_can_create_with_minimum_arguments(): void
    {
        $dto = new FrontPageBodyDTO(id: 'top', label: 'Blog top');

        $this->assertSame('top', $dto->id);
        $this->assertSame('Blog top', $dto->label);
        $this->assertNull($dto->description);
        $this->assertSame([], $dto->meta);
    }

    public function test_can_create_with_all_arguments(): void
    {
        $dto = new FrontPageBodyDTO(
            id: '12',
            label: 'About us',
            description: 'A published page',
            meta: ['path' => 'about/us'],
        );

        $this->assertSame('12', $dto->id);
        $this->assertSame('About us', $dto->label);
        $this->assertSame('A published page', $dto->description);
        $this->assertSame(['path' => 'about/us'], $dto->meta);
    }

    /**
     * The DTO describes a choice and nothing else. A provider cannot name the
     * provider a choice belongs to, so it cannot offer one that resolves to someone
     * else; FrontPageBodyResolver composes the stored form from the provider itself.
     */
    public function test_the_dto_carries_no_provider_key_and_composes_no_stored_value(): void
    {
        $reflection = new ReflectionClass(FrontPageBodyDTO::class);

        $this->assertFalse($reflection->hasProperty('providerKey'));
        $this->assertFalse($reflection->hasMethod('choiceKey'));
        $this->assertFalse($reflection->hasMethod('splitChoiceKey'));
    }

    public function test_json_serialize_contains_all_keys(): void
    {
        $dto = new FrontPageBodyDTO(
            id: 'top',
            label: 'Blog top',
            description: null,
            meta: ['foo' => 'bar'],
        );

        $this->assertSame([
            'id' => 'top',
            'label' => 'Blog top',
            'description' => null,
            'meta' => ['foo' => 'bar'],
        ], $dto->jsonSerialize());
    }

    public function test_dto_is_readonly(): void
    {
        $this->assertTrue((new ReflectionClass(FrontPageBodyDTO::class))->isReadOnly());
    }
}
