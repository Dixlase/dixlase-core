<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Traits;

use App\Captcha\CaptchaDriver;
use App\Captcha\CaptchaResult;
use App\Helpers\CaptchaHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Server-side CAPTCHA verification for form requests.
 *
 * A request that uses this trait gets two things:
 *
 *  - getCaptchaRules(): the active driver's token field rule(s), to merge
 *    into rules(). That rule only checks that a token was posted.
 *  - Automatic verification: once the rules pass, withValidator() checks the
 *    posted token against the provider (CaptchaHelper::verify) and reports a
 *    failure on the token field. Nothing else has to be called. Before this
 *    hook existed the presence rule was the only check a request got unless
 *    it called verifyCaptcha() itself, so any non-empty string passed.
 *
 * Override captchaFormKey() (or set a `$captchaFormKey` property) to name the
 * form's `captcha.forms` key: verification is then gated on that form's
 * setting, and drivers that assess the token against the action it was
 * rendered with (reCAPTCHA Enterprise) get the key to compare. Without a key
 * the token is verified whenever CAPTCHA is on at all.
 *
 * A request that defines its own withValidator() replaces this hook (PHP
 * lets the class win over the trait); call verifyCaptchaAfterRules() from it.
 */
trait VerifiesCaptcha
{
    /**
     * The `captcha.forms` key of this form, or null when the request is not tied to one.
     */
    protected function captchaFormKey(): ?string
    {
        return property_exists($this, 'captchaFormKey') ? $this->captchaFormKey : null;
    }

    /**
     * FormRequest hook: verify the token with the provider after the rules pass.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->verifyCaptchaAfterRules($validator);
        });
    }

    /**
     * Verify the posted token and add a failure to the token field(s).
     */
    protected function verifyCaptchaAfterRules(Validator $validator): void
    {
        $fields = array_keys($this->getCaptchaRules());

        if ($fields === [] || ! CaptchaHelper::shouldShowCaptcha($this->captchaFormKey())) {
            return;
        }

        // A missing token is already reported by the presence rule; do not
        // spend a provider round-trip (or a second message) on it.
        foreach ($fields as $field) {
            if ($validator->errors()->has($field)) {
                return;
            }
        }

        $result = $this->captchaResultFor($this, $this->captchaFormKey());

        if ($result === null || $result->isValid()) {
            return;
        }

        foreach ($fields as $field) {
            $validator->errors()->add($field, $result->getErrorMessage());
        }
    }

    /**
     * Explicit verification for callers outside the FormRequest lifecycle.
     *
     * @throws ValidationException when the token is rejected
     */
    protected function verifyCaptcha(Request $request, ?string $formType = null): void
    {
        $result = $this->captchaResultFor($request, $formType ?? $this->captchaFormKey());

        if ($result === null || $result->isValid()) {
            return;
        }

        $fields = array_keys($this->getCaptchaRules()) ?: ['captcha'];

        throw ValidationException::withMessages(
            array_fill_keys($fields, $result->getErrorMessage())
        );
    }

    /**
     * Run the provider check, or return null when CAPTCHA does not apply.
     */
    protected function captchaResultFor(Request $request, ?string $formKey): ?CaptchaResult
    {
        if (! CaptchaHelper::shouldShowCaptcha($formKey)) {
            return null;
        }

        if ($formKey !== null) {
            return CaptchaHelper::verify($request, $formKey);
        }

        try {
            return app(CaptchaDriver::class)->verify($request);
        } catch (\Throwable $e) {
            Log::error('Failed to verify CAPTCHA', ['error' => $e->getMessage()]);

            return new CaptchaResult(false, null, null, ['captcha' => __('auth.captcha_verification_failed')]);
        }
    }

    /**
     * The active driver's token field rule(s).
     */
    protected function getCaptchaRules(): array
    {
        return app(CaptchaDriver::class)->rules();
    }
}
