<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Rules;

use App\Traits\PwnedPasswordTrait;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Password breach check validation rule
 *
 * Check if password is in breach database using Have I Been Pwned API
 */
class NotPwnedPassword implements ValidationRule
{
    use PwnedPasswordTrait;

    private string $settingKey;

    private bool $skipOnApiError;

    /**
     * Constructor
     *
     * @param  string  $settingKey  Settings key (default: 'pwned_password_check_enabled')
     * @param  bool  $skipOnApiError  Whether to skip validation on API error (default: true)
     */
    public function __construct(string $settingKey = 'pwned_password_check_enabled', bool $skipOnApiError = true)
    {
        $this->settingKey = $settingKey;
        $this->skipOnApiError = $skipOnApiError;
    }

    /**
     * Execute validation
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Skip if dictionary attack protection is disabled
        if (! $this->isPwnedPasswordCheckEnabled($this->settingKey)) {
            return;
        }

        // Skip if password is not a string
        if (! is_string($value)) {
            return;
        }

        $safetyCheck = $this->validatePasswordSafety($value, $this->settingKey);

        // On API error
        if ($safetyCheck['pwned_info']['error']) {
            if (! $this->skipOnApiError) {
                $fail(__('validation.pwned_password_api_error'));
            }

            return;
        }

        // If password is breached
        if (! $safetyCheck['is_safe']) {
            $fail($safetyCheck['message']);
        }
    }

    /**
     * Static factory method
     *
     * @param  string  $settingKey  Settings key
     * @param  bool  $skipOnApiError  Whether to skip on API error
     */
    public static function using(string $settingKey = 'pwned_password_check_enabled', bool $skipOnApiError = true): static
    {
        return new static($settingKey, $skipOnApiError);
    }
}
