<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

declare(strict_types=1);

namespace Tests\Feature\Admin\Settings\Security;

use App\Actions\Security\UpdateCaptchaSettingsAction;
use App\Actors\MemberActor;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Services\CaptchaService;
use App\Services\CaptchaTestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The stored "widget test passed" flag must only ever describe the settings
 * that actually passed the test. The browser submits the flag as a hidden
 * input, so the save has to tie it to the fingerprint remembered at test time.
 */
class CaptchaAuthTestGateTest extends TestCase
{
    use RefreshDatabase;

    private const SAVED = [
        'captcha_driver' => 'turnstile',
        'captcha_site_key' => 'saved-site-key',
        'captcha_secret_key' => 'saved-secret-key',
        'captcha_google_version' => 'v3',
        'captcha_google_min_score' => '0.5',
        'captcha_google_project_id' => '',
    ];

    private Member $admin;

    private SecuritySettingRepositoryInterface $repository;

    private CaptchaTestService $testService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Member::create([
            'account_name' => 'captchaadmin',
            'display_name' => 'Captcha Admin',
            'email' => 'captcha@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        $this->repository = app(SecuritySettingRepositoryInterface::class);
        $this->testService = app(CaptchaTestService::class);

        $this->repository->set('captcha_enabled', true);
        $this->repository->set('captcha_driver', 'turnstile');
        $this->repository->set('captcha_turnstile_site_key', self::SAVED['captcha_site_key']);
        $this->repository->set('captcha_turnstile_secret_key', self::SAVED['captcha_secret_key']);
        $this->repository->set('captcha_google_version', 'v3');
        $this->repository->set('captcha_google_min_score', '0.5');
        $this->repository->set('captcha_google_project_id', '');
        $this->repository->set('captcha_authentication_result', true);
    }

    private function save(array $overrides, bool $submittedFlag): void
    {
        $data = array_merge(self::SAVED, ['captcha_enabled' => true], $overrides);
        $data['captcha_authentication_result'] = $submittedFlag;

        $result = (new UpdateCaptchaSettingsAction(
            $this->repository,
            app(CaptchaService::class),
        ))->execute(new MemberActor($this->admin), $data);

        $this->assertTrue($result->success);
    }

    private function assertPassed(bool $expected): void
    {
        $this->assertSame(
            $expected,
            filter_var($this->repository->get('captcha_authentication_result'), FILTER_VALIDATE_BOOLEAN),
        );
    }

    public function test_changed_secret_without_a_matching_test_drops_the_passed_flag(): void
    {
        // The hidden input says "passed" but nothing was tested in this session.
        $this->save(['captcha_secret_key' => 'edited-secret-key'], true);

        $this->assertPassed(false);
        $this->assertSame('edited-secret-key', $this->repository->get('captcha_turnstile_secret_key'));
    }

    public function test_settings_that_passed_the_test_are_saved_as_passed(): void
    {
        $tested = array_merge(self::SAVED, ['captcha_secret_key' => 'tested-secret-key']);
        $this->repository->set('captcha_authentication_result', false);
        $this->testService->rememberTestedSettings($tested);

        $this->save(['captcha_secret_key' => 'tested-secret-key'], true);

        $this->assertPassed(true);
        $this->assertNull(session(CaptchaTestService::TESTED_FINGERPRINT_SESSION_KEY), 'fingerprint is consumed by the save');
    }

    public function test_value_edited_after_the_test_is_not_trusted(): void
    {
        $this->testService->rememberTestedSettings(array_merge(self::SAVED, ['captcha_secret_key' => 'tested-secret-key']));

        // Test passed with one key, then the field was edited before saving.
        $this->save(['captcha_secret_key' => 'tested-secret-kex'], true);

        $this->assertPassed(false);
    }

    public function test_min_score_change_after_the_test_is_not_trusted(): void
    {
        $this->testService->rememberTestedSettings(self::SAVED);

        $this->save(['captcha_google_min_score' => '0.9'], true);

        $this->assertPassed(false);
    }

    public function test_unchanged_settings_keep_the_stored_status(): void
    {
        $this->save([], true);
        $this->assertPassed(true);

        $this->repository->set('captcha_authentication_result', false);
        $this->save([], true);
        $this->assertPassed(false);
    }

    public function test_retest_of_unchanged_settings_restores_the_passed_flag(): void
    {
        $this->repository->set('captcha_authentication_result', false);
        $this->testService->rememberTestedSettings(self::SAVED);

        $this->save([], true);

        $this->assertPassed(true);
    }

    public function test_fingerprint_normalises_the_min_score(): void
    {
        $a = CaptchaTestService::fingerprint(array_merge(self::SAVED, ['captcha_google_min_score' => '0.5']));
        $b = CaptchaTestService::fingerprint(array_merge(self::SAVED, ['captcha_google_min_score' => 0.5]));
        $c = CaptchaTestService::fingerprint(array_merge(self::SAVED, ['captcha_google_min_score' => '0.50']));
        $d = CaptchaTestService::fingerprint(array_merge(self::SAVED, ['captcha_google_min_score' => '0.7']));

        $this->assertSame($a, $b);
        $this->assertSame($a, $c);
        $this->assertNotSame($a, $d);
    }
}
