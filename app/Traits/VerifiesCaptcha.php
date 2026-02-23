<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Traits;

use App\Captcha\CaptchaDriver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
trait VerifiesCaptcha
{
    /**
     * Verify captcha for the given request
     *
     * @param  string|null  $formType  Optional form type for specific captcha settings
     *
     * @throws ValidationException
     */
    protected function verifyCaptcha(Request $request, ?string $formType = null): void
    {
        $captcha = app(CaptchaDriver::class);

        // Check if captcha is enabled for this form type
        if ($formType && ! config("captcha.forms.{$formType}", true)) {
            return;
        }

        // Skip verification if captcha is not enabled
        if (! $captcha->isEnabled()) {
            return;
        }

        $result = $captcha->verify($request);

        if (! $result->isSuccess()) {
            throw ValidationException::withMessages($result->getErrors());
        }
    }

    /**
     * Get captcha validation rules
     */
    protected function getCaptchaRules(): array
    {
        $captcha = app(CaptchaDriver::class);

        return $captcha->rules();
    }
}
