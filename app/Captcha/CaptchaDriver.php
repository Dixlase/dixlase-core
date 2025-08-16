<?php

/**
 * This file is part of MySoftware.
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

namespace App\Captcha;

use Illuminate\Http\Request;

interface CaptchaDriver
{
    /**
     * Render the captcha script tags
     */
    public function renderScript(): string;

    /**
     * Render the captcha widget
     */
    public function renderWidget(array $options = []): string;

    /**
     * Verify the captcha response
     */
    public function verify(Request $request): CaptchaResult;

    /**
     * Get validation rules for forms using this captcha
     */
    public function rules(): array;

    /**
     * Check if the captcha is enabled
     */
    public function isEnabled(): bool;
}
