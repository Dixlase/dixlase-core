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

namespace Tests\Unit\Requests\Settings;

use App\Http\Requests\Admin\Settings\Systems\AdminSystemApiGenerateKeyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApiGenerateKeyRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new AdminSystemApiGenerateKeyRequest();

        return Validator::make($data, $request->rules());
    }

    public function test_valid_data_passes(): void
    {
        $validator = $this->validate([
            'name' => 'My API Key',
            'environment' => 'live',
            'scopes' => ['read:content'],
            'rate_limit' => 1000,
            'expires_at' => now()->addYear()->format('Y-m-d'),
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_name_is_required(): void
    {
        $validator = $this->validate([
            'environment' => 'live',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_name_max_100_characters(): void
    {
        $validator = $this->validate([
            'name' => str_repeat('a', 101),
            'environment' => 'live',
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_environment_must_be_live_or_test(): void
    {
        $validator = $this->validate([
            'name' => 'Key',
            'environment' => 'production',
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_rate_limit_cannot_exceed_10000(): void
    {
        $validator = $this->validate([
            'name' => 'Key',
            'environment' => 'live',
            'rate_limit' => 10001,
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_expires_at_must_be_future_date(): void
    {
        $validator = $this->validate([
            'name' => 'Key',
            'environment' => 'live',
            'expires_at' => now()->subDay()->format('Y-m-d'),
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_description_max_500_characters(): void
    {
        $validator = $this->validate([
            'name' => 'Key',
            'environment' => 'live',
            'description' => str_repeat('a', 501),
        ]);

        $this->assertTrue($validator->fails());
    }
}
