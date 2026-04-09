<?php

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
