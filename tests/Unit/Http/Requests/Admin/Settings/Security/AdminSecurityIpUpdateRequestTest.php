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

namespace Tests\Unit\Http\Requests\Admin\Settings\Security;

use App\Http\Requests\Admin\Settings\Security\AdminSecurityIpUpdateRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AdminSecurityIpUpdateRequestTest extends TestCase
{
    /**
     * Run the request's validation (including the lockout guard) for the given input.
     *
     * @param  array<string, mixed>  $data
     */
    private function validateWith(array $data, string $remoteAddr): \Illuminate\Validation\Validator
    {
        $request = AdminSecurityIpUpdateRequest::create('/', 'POST', $data, server: ['REMOTE_ADDR' => $remoteAddr]);
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        return $validator;
    }

    public function test_allowlist_save_is_blocked_when_current_ip_is_missing(): void
    {
        $validator = $this->validateWith([
            'enable_allowed_admin_ips' => '1',
            'allowed_admin_ips' => '203.0.113.50',
        ], '198.51.100.10');

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('allowed_admin_ips', $validator->errors()->toArray());
    }

    public function test_empty_allowlist_save_is_blocked(): void
    {
        $validator = $this->validateWith([
            'enable_allowed_admin_ips' => '1',
            'allowed_admin_ips' => '',
        ], '198.51.100.10');

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('allowed_admin_ips', $validator->errors()->toArray());
    }

    public function test_allowlist_save_is_allowed_when_current_ip_is_present(): void
    {
        $validator = $this->validateWith([
            'enable_allowed_admin_ips' => '1',
            'allowed_admin_ips' => "203.0.113.50\n198.51.100.10",
        ], '198.51.100.10');

        $this->assertFalse($validator->fails());
    }

    public function test_allowlist_check_is_skipped_when_disabled(): void
    {
        $validator = $this->validateWith([
            'enable_allowed_admin_ips' => '0',
            'allowed_admin_ips' => '203.0.113.50',
        ], '198.51.100.10');

        $this->assertFalse($validator->fails());
    }

    public function test_blocklist_save_is_blocked_when_current_ip_is_present(): void
    {
        $validator = $this->validateWith([
            'enable_blocked_admin_ips' => '1',
            'blocked_admin_ips' => '198.51.100.10, 203.0.113.9',
        ], '198.51.100.10');

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('blocked_admin_ips', $validator->errors()->toArray());
    }

    public function test_blocklist_save_is_allowed_when_current_ip_is_absent(): void
    {
        $validator = $this->validateWith([
            'enable_blocked_admin_ips' => '1',
            'blocked_admin_ips' => '203.0.113.9',
        ], '198.51.100.10');

        $this->assertFalse($validator->fails());
    }

    public function test_cidr_allowlist_save_is_allowed_when_current_ip_in_range(): void
    {
        $validator = $this->validateWith([
            'enable_allowed_admin_ips' => '1',
            'allowed_admin_ips' => '198.51.100.0/24',
        ], '198.51.100.10');

        $this->assertFalse($validator->fails());
    }

    public function test_cidr_allowlist_save_is_blocked_when_current_ip_outside_range(): void
    {
        $validator = $this->validateWith([
            'enable_allowed_admin_ips' => '1',
            'allowed_admin_ips' => '203.0.113.0/24',
        ], '198.51.100.10');

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('allowed_admin_ips', $validator->errors()->toArray());
    }

    public function test_save_fails_when_list_contains_invalid_entry(): void
    {
        $validator = $this->validateWith([
            'allowed_admin_ips' => "198.51.100.10\nnot_an_ip",
        ], '198.51.100.10');

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('allowed_admin_ips', $errors);
        $this->assertStringContainsString('not_an_ip', $errors['allowed_admin_ips'][0]);
    }

    public function test_save_fails_when_blocklist_contains_invalid_cidr(): void
    {
        $validator = $this->validateWith([
            'blocked_front_ips' => '10.0.0.0/99',
        ], '198.51.100.10');

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('blocked_front_ips', $validator->errors()->toArray());
    }
}
