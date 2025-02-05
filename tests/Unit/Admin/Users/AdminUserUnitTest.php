<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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


namespace Tests\Unit\Admin\Users;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Admin\Users\AdminUserStoreRequest;


class AdminUserUnitTest extends TestCase
{
    // ユーザー作成のバリデーションテスト
    public function test_user_creation_validation()
    {
        $request = new AdminUserStoreRequest(); // FormRequestクラス
        $validator = Validator::make([
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors());
        $this->assertArrayHasKey('email', $validator->errors());
        $this->assertArrayHasKey('password', $validator->errors());
    }
}
