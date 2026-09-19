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

namespace Tests\Unit\Services\Captcha;

use App\Captcha\TurnstileCaptchaDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Turnstile error codes must surface as the translated messages from the
 * CAPTCHA settings dictionary, never as a raw translation key.
 */
class TurnstileErrorMessageTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private function verify(array $errorCodes): string
    {
        Http::fake([self::ENDPOINT => Http::response(['success' => false, 'error-codes' => $errorCodes])]);

        $driver = new TurnstileCaptchaDriver(['site_key' => 'test-site-key', 'secret_key' => 'test-secret-key']);
        $result = $driver->verify(Request::create('/login', 'POST', ['cf-turnstile-response' => 'token']));

        $this->assertFalse($result->isValid());

        return $result->getErrorMessage();
    }

    public function test_known_error_code_is_translated(): void
    {
        $this->assertSame(
            __('admin/settings/security/captcha.turnstile_errors.invalid-input-response'),
            $this->verify(['invalid-input-response']),
        );
        $this->assertStringNotContainsString('turnstile_errors', $this->verify(['invalid-input-secret']));
    }

    public function test_known_error_code_is_translated_in_japanese(): void
    {
        app()->setLocale('ja');

        $this->assertSame('レスポンスパラメータが無効または形式が正しくありません', $this->verify(['invalid-input-response']));
    }

    public function test_unknown_error_code_falls_back_to_the_generic_message(): void
    {
        $generic = __('admin/settings/security/captcha.turnstile_errors.unknown-error');

        $this->assertSame($generic, $this->verify(['some-new-code']));
        $this->assertSame($generic, $this->verify([]));
        $this->assertStringNotContainsString('unknown-error', $generic);
    }

    public function test_multiple_codes_are_joined(): void
    {
        $this->assertSame(
            __('admin/settings/security/captcha.turnstile_errors.timeout-or-duplicate').', '.
            __('admin/settings/security/captcha.turnstile_errors.bad-request'),
            $this->verify(['timeout-or-duplicate', 'bad-request']),
        );
    }
}
