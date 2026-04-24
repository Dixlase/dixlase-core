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

use App\Http\Requests\Admin\Settings\Security\AdminSecurityTwoFaUpdateRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurityTwoFaUpdateRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new AdminSecurityTwoFaUpdateRequest();

        return Validator::make($data, $request->rules());
    }

    public function test_valid_data_passes(): void
    {
        $validator = $this->validate([
            'two_fa_mode' => 1,
            'two_fa_expire_minutes' => 5,
            'two_fa_max_attempts' => 5,
            'two_fa_attempt_window' => 15,
            'two_fa_lockout_duration' => 30,
            'two_fa_recovery_codes_count' => 10,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_mode_must_be_valid_value(): void
    {
        $validator = $this->validate(['two_fa_mode' => 99]);

        $this->assertTrue($validator->fails());
    }

    public function test_expire_minutes_min_is_1(): void
    {
        $validator = $this->validate(['two_fa_expire_minutes' => 0]);

        $this->assertTrue($validator->fails());
    }

    public function test_expire_minutes_max_is_60(): void
    {
        $validator = $this->validate(['two_fa_expire_minutes' => 61]);

        $this->assertTrue($validator->fails());
    }

    public function test_max_attempts_min_is_3(): void
    {
        $validator = $this->validate(['two_fa_max_attempts' => 2]);

        $this->assertTrue($validator->fails());
    }

    public function test_lockout_duration_max_is_1440(): void
    {
        $validator = $this->validate(['two_fa_lockout_duration' => 1441]);

        $this->assertTrue($validator->fails());
    }

    public function test_resend_interval_min_is_60(): void
    {
        $validator = $this->validate(['two_fa_resend_interval_seconds' => 59]);

        $this->assertTrue($validator->fails());
    }

    public function test_recovery_codes_count_range(): void
    {
        $tooFew = $this->validate(['two_fa_recovery_codes_count' => 4]);
        $this->assertTrue($tooFew->fails());

        $tooMany = $this->validate(['two_fa_recovery_codes_count' => 21]);
        $this->assertTrue($tooMany->fails());

        $valid = $this->validate(['two_fa_recovery_codes_count' => 10]);
        $this->assertTrue($valid->passes());
    }
}
