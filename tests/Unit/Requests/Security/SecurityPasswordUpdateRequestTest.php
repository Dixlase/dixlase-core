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

namespace Tests\Unit\Requests\Security;

use App\Http\Requests\Admin\Settings\Security\AdminSecurityPasswordUpdateRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurityPasswordUpdateRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new AdminSecurityPasswordUpdateRequest();

        return Validator::make($data, $request->rules());
    }

    public function test_valid_data_passes(): void
    {
        $validator = $this->validate([
            'password_min_length' => 8,
            'password_require_uppercase' => true,
            'password_require_number' => true,
            'password_require_symbol' => false,
            'pwned_password_check_enabled' => true,
            'password_reset_enabled' => true,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_min_length_must_be_at_least_4(): void
    {
        $validator = $this->validate([
            'password_min_length' => 3,
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_min_length_cannot_exceed_128(): void
    {
        $validator = $this->validate([
            'password_min_length' => 129,
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_min_length_is_required(): void
    {
        $validator = $this->validate([
            'password_require_uppercase' => true,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password_min_length', $validator->errors()->toArray());
    }
}
