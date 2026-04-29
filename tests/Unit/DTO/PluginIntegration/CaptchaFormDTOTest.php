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

use App\DTO\PluginIntegration\CaptchaFormDTO;
use Tests\TestCase;

class CaptchaFormDTOTest extends TestCase
{
    public function test_constructs_with_all_fields(): void
    {
        $dto = new CaptchaFormDTO(
            key: 'inquiry_contact',
            name: 'dixlase-inquiry::captcha.forms.inquiry_contact',
            route: 'inquiry.send',
            category: 'contact',
            defaultEnabled: true,
            priority: 200,
        );

        $this->assertSame('inquiry_contact', $dto->key);
        $this->assertSame('dixlase-inquiry::captcha.forms.inquiry_contact', $dto->name);
        $this->assertSame('inquiry.send', $dto->route);
        $this->assertSame('contact', $dto->category);
        $this->assertTrue($dto->defaultEnabled);
        $this->assertSame(200, $dto->priority);
    }

    public function test_uses_safe_defaults(): void
    {
        $dto = new CaptchaFormDTO(
            key: 'foo',
            name: 'bar',
            route: 'baz',
            category: 'misc',
        );

        $this->assertFalse($dto->defaultEnabled);
        $this->assertSame(1000, $dto->priority);
    }

    public function test_to_array_round_trip(): void
    {
        $dto = new CaptchaFormDTO(
            key: 'user_login',
            name: 'name.key',
            route: 'users.login',
            category: 'users',
            defaultEnabled: false,
            priority: 100,
        );

        $restored = CaptchaFormDTO::fromArray($dto->toArray());

        $this->assertEquals($dto, $restored);
    }

    public function test_json_serialize_uses_snake_case(): void
    {
        $dto = new CaptchaFormDTO(
            key: 'k',
            name: 'n',
            route: 'r',
            category: 'c',
            defaultEnabled: true,
            priority: 50,
        );

        $payload = $dto->jsonSerialize();

        $this->assertArrayHasKey('default_enabled', $payload);
        $this->assertSame(true, $payload['default_enabled']);
        $this->assertArrayNotHasKey('defaultEnabled', $payload);
    }
}
