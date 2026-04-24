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

namespace Tests\Unit\Services;

use App\Services\PasswordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_hash_produces_bcrypt_hash(): void
    {
        $hash = PasswordService::hash('password123');

        $this->assertNotEquals('password123', $hash);
        $this->assertTrue(Hash::check('password123', $hash));
    }

    public function test_verify_correct_password(): void
    {
        $hash = PasswordService::hash('correct');

        $this->assertTrue(PasswordService::verify('correct', $hash));
    }

    public function test_verify_incorrect_password(): void
    {
        $hash = PasswordService::hash('correct');

        $this->assertFalse(PasswordService::verify('wrong', $hash));
    }

    public function test_hash_password_if_present_hashes_field(): void
    {
        $data = ['name' => 'Test', 'password' => 'plain'];

        PasswordService::hashPasswordIfPresent($data);

        $this->assertNotEquals('plain', $data['password']);
        $this->assertTrue(Hash::check('plain', $data['password']));
    }

    public function test_hash_password_if_present_removes_empty_password(): void
    {
        $data = ['name' => 'Test', 'password' => ''];

        PasswordService::hashPasswordIfPresent($data);

        $this->assertArrayNotHasKey('password', $data);
    }

    public function test_hash_password_if_present_ignores_missing_field(): void
    {
        $data = ['name' => 'Test'];

        PasswordService::hashPasswordIfPresent($data);

        $this->assertArrayNotHasKey('password', $data);
    }

    public function test_build_password_rules_required(): void
    {
        $rules = PasswordService::buildPasswordRules(8, true, true, true, true, true);

        $this->assertIsArray($rules);
        $this->assertContains('required', $rules);
    }

    public function test_build_password_rules_returns_non_empty_array(): void
    {
        $rules = PasswordService::buildPasswordRules(12, true, true, true, true, true);

        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
        // Password ルールオブジェクトが含まれること
        $hasPasswordRule = false;
        foreach ($rules as $rule) {
            if ($rule instanceof \Illuminate\Validation\Rules\Password) {
                $hasPasswordRule = true;
                break;
            }
        }
        $this->assertTrue($hasPasswordRule, 'Rules should contain a Password rule object');
    }

    public function test_build_password_rules_optional(): void
    {
        $rules = PasswordService::buildPasswordRules(8, false, false, false, false, false);

        $this->assertNotContains('required', $rules);
        $this->assertContains('nullable', $rules);
    }
}
