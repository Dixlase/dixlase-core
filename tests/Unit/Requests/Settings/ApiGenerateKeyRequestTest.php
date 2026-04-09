<?php

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
