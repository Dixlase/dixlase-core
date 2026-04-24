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

namespace Tests\Unit\Requests\Install;

use App\Http\Requests\Install\InstallSettingsRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class InstallSettingsRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new InstallSettingsRequest();

        return Validator::make($data, $request->rules());
    }

    private function validData(): array
    {
        return [
            'site_name' => 'My Site',
            'admin_account_name' => 'admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'SecurePass1',
            'admin_password_confirmation' => 'SecurePass1',
        ];
    }

    public function test_valid_data_passes(): void
    {
        $validator = $this->validate($this->validData());

        $this->assertTrue($validator->passes());
    }

    public function test_site_name_is_required(): void
    {
        $data = $this->validData();
        unset($data['site_name']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('site_name', $validator->errors()->toArray());
    }

    public function test_admin_email_must_be_valid(): void
    {
        $data = $this->validData();
        $data['admin_email'] = 'not-an-email';

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
    }

    public function test_admin_password_must_be_confirmed(): void
    {
        $data = $this->validData();
        $data['admin_password_confirmation'] = 'DifferentPass1';

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
    }

    public function test_admin_password_requires_min_8_chars(): void
    {
        $data = $this->validData();
        $data['admin_password'] = 'Short1';
        $data['admin_password_confirmation'] = 'Short1';

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
    }

    public function test_admin_password_requires_uppercase(): void
    {
        $data = $this->validData();
        $data['admin_password'] = 'nouppercase1';
        $data['admin_password_confirmation'] = 'nouppercase1';

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
    }

    public function test_admin_password_requires_number(): void
    {
        $data = $this->validData();
        $data['admin_password'] = 'NoNumberHere';
        $data['admin_password_confirmation'] = 'NoNumberHere';

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
    }

    public function test_admin_account_name_must_be_alphanumeric(): void
    {
        $data = $this->validData();
        $data['admin_account_name'] = 'admin user!';

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
    }

    public function test_admin_account_name_min_3_max_20(): void
    {
        $tooShort = $this->validData();
        $tooShort['admin_account_name'] = 'ab';
        $this->assertTrue($this->validate($tooShort)->fails());

        $tooLong = $this->validData();
        $tooLong['admin_account_name'] = str_repeat('a', 21);
        $this->assertTrue($this->validate($tooLong)->fails());

        $justRight = $this->validData();
        $justRight['admin_account_name'] = 'abc';
        $this->assertTrue($this->validate($justRight)->passes());
    }
}
