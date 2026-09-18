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

namespace App\Captcha\Concerns;

/**
 * Normalises the action name handed to grecaptcha.execute().
 *
 * Google only accepts A-Za-z/_ in a reCAPTCHA action name and rejects the
 * whole call otherwise — "Invalid action name, may only include A-Za-z/_" —
 * which leaves the hidden g-recaptcha-response field empty, so the server then
 * rejects a perfectly legitimate submission with "reCAPTCHA response is
 * required". Dixlase derives the action from the form key, and a plugin form
 * key is a slug: DixlaseInquiry uses `dixlase-inquiry.inquiry_contact`, whose
 * `-` and `.` are both illegal here. Core form keys happened to contain only
 * legal characters, which is why admin login worked while the inquiry form did
 * not.
 *
 * Rather than constrain form keys — they identify settings elsewhere —
 * translate them at the boundary. Turnstile has no such restriction, so this
 * lives with the Google drivers.
 */
trait SanitizesRecaptchaAction
{
    /**
     * Return an action name that Google accepts.
     *
     * Every illegal character becomes `_`, runs of `_` collapse into one, and
     * an action that sanitises to nothing falls back to `submit` — the same
     * default the drivers use when no action is supplied.
     */
    protected function sanitizeAction(string $action): string
    {
        $sanitized = preg_replace('/[^A-Za-z\/_]/', '_', $action) ?? '';
        $sanitized = preg_replace('/_+/', '_', $sanitized) ?? '';
        $sanitized = trim($sanitized, '_');

        return $sanitized !== '' ? $sanitized : 'submit';
    }
}
