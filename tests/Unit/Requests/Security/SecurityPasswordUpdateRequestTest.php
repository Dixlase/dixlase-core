<?php

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
