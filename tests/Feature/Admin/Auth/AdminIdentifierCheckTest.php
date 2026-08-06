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

namespace Tests\Feature\Admin\Auth;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Models\Member;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * ログイン識別子チェックのセキュリティテスト
 *
 * - ユーザー列挙攻撃の防御検証
 * - レスポンス形式の一貫性
 * - バリデーション
 */
class AdminIdentifierCheckTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [
            resource_path("views/{$adminTheme}"),
        ]);

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secure-password-123'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        SecuritySetting::setValue('login_attempt_limit_enabled', false);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    // =========================================================================
    // 正常系
    // =========================================================================

    public function test_existing_email_returns_exists_true(): void
    {
        $response = $this->postJson(route('admin.login.check-identifier'), [
            'login' => 'admin@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['exists', 'has_passkey']);
        $response->assertJson(['exists' => true]);
    }

    public function test_existing_account_name_returns_exists_true(): void
    {
        $response = $this->postJson(route('admin.login.check-identifier'), [
            'login' => 'testadmin',
        ]);

        $response->assertOk();
        $response->assertJson(['exists' => true]);
    }

    public function test_nonexistent_email_returns_exists_false(): void
    {
        $response = $this->postJson(route('admin.login.check-identifier'), [
            'login' => 'nonexistent@example.com',
        ]);

        $response->assertOk();
        $response->assertJson(['exists' => false, 'has_passkey' => false]);
    }

    public function test_nonexistent_account_name_returns_exists_false(): void
    {
        $response = $this->postJson(route('admin.login.check-identifier'), [
            'login' => 'nobody',
        ]);

        $response->assertOk();
        $response->assertJson(['exists' => false]);
    }

    // =========================================================================
    // ユーザー列挙防止の検証
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Group('security-audit')]
    public function test_response_is_uniform_regardless_of_user_existence(): void
    {
        $responseExisting = $this->postJson(route('admin.login.check-identifier'), [
            'login' => 'admin@example.com',
        ]);

        $responseNonExisting = $this->postJson(route('admin.login.check-identifier'), [
            'login' => 'nonexistent@example.com',
        ]);

        $responseExisting->assertOk();
        $responseNonExisting->assertOk();

        $existingKeys = array_keys($responseExisting->json());
        $nonExistingKeys = array_keys($responseNonExisting->json());
        $this->assertEquals($existingKeys, $nonExistingKeys);
    }

    public function test_response_does_not_leak_user_details(): void
    {
        $response = $this->postJson(route('admin.login.check-identifier'), [
            'login' => 'admin@example.com',
        ]);

        $json = $response->json();

        $this->assertArrayNotHasKey('email', $json);
        $this->assertArrayNotHasKey('name', $json);
        $this->assertArrayNotHasKey('display_name', $json);
        $this->assertArrayNotHasKey('account_name', $json);
        $this->assertArrayNotHasKey('id', $json);
        $this->assertArrayNotHasKey('role', $json);
    }

    // =========================================================================
    // バリデーション
    // =========================================================================

    public function test_empty_login_is_rejected(): void
    {
        $response = $this->postJson(route('admin.login.check-identifier'), [
            'login' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_missing_login_field_is_rejected(): void
    {
        $response = $this->postJson(route('admin.login.check-identifier'), []);

        $response->assertStatus(422);
    }

    public function test_login_exceeding_max_length_is_rejected(): void
    {
        $response = $this->postJson(route('admin.login.check-identifier'), [
            'login' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // HTTP メソッド制限
    // =========================================================================

    public function test_get_method_is_rejected(): void
    {
        $response = $this->getJson(route('admin.login.check-identifier'));

        $response->assertStatus(405);
    }

    // =========================================================================
    // CAPTCHA 検証済みフラグ
    // =========================================================================

    public function test_captcha_verified_flag_is_set_in_session(): void
    {
        $this->postJson(route('admin.login.check-identifier'), [
            'login' => 'admin@example.com',
        ]);

        // The identifier is hashed into the session key (see LoginIdentifierCheckTrait).
        $this->assertNotNull(session('captcha_verified_'.hash('sha256', 'admin@example.com')));
    }
}
