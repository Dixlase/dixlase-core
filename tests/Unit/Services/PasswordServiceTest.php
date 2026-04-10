<?php

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
