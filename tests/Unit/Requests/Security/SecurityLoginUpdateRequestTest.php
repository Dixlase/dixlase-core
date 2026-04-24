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

use App\Http\Requests\Admin\Settings\Security\AdminSecurityLoginUpdateRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurityLoginUpdateRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new AdminSecurityLoginUpdateRequest();

        return Validator::make($data, $request->rules());
    }

    public function test_valid_data_passes(): void
    {
        $validator = $this->validate([
            'login_identifier_mode' => 0,
            'login_attempt_limit_enabled' => true,
            'login_attempt_max_attempts' => 5,
            'login_attempt_max_attempts_ip' => 10,
            'login_attempt_time_window' => 15,
            'login_attempt_lockout_duration' => 30,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_max_attempts_must_be_positive(): void
    {
        $validator = $this->validate([
            'login_attempt_max_attempts' => 0,
            'login_attempt_max_attempts_ip' => 10,
            'login_attempt_time_window' => 15,
            'login_attempt_lockout_duration' => 30,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('login_attempt_max_attempts', $validator->errors()->toArray());
    }

    public function test_max_attempts_cannot_exceed_100(): void
    {
        $validator = $this->validate([
            'login_attempt_max_attempts' => 101,
            'login_attempt_max_attempts_ip' => 10,
            'login_attempt_time_window' => 15,
            'login_attempt_lockout_duration' => 30,
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_lockout_duration_cannot_exceed_10080(): void
    {
        $validator = $this->validate([
            'login_attempt_max_attempts' => 5,
            'login_attempt_max_attempts_ip' => 10,
            'login_attempt_time_window' => 15,
            'login_attempt_lockout_duration' => 10081,
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_identifier_mode_must_be_valid_value(): void
    {
        $validator = $this->validate([
            'login_identifier_mode' => 99,
            'login_attempt_max_attempts' => 5,
            'login_attempt_max_attempts_ip' => 10,
            'login_attempt_time_window' => 15,
            'login_attempt_lockout_duration' => 30,
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_notification_email_must_be_valid(): void
    {
        $validator = $this->validate([
            'login_notification_system_email' => 'not-an-email',
            'login_attempt_max_attempts' => 5,
            'login_attempt_max_attempts_ip' => 10,
            'login_attempt_time_window' => 15,
            'login_attempt_lockout_duration' => 30,
        ]);

        $this->assertTrue($validator->fails());
    }
}
