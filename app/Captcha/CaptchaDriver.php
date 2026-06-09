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

    /**
     * Get the CSP directives required to load this captcha driver.
     *
     * Returned only when the driver is the active driver and captcha is enabled.
     * Each driver declares the minimum set of external origins it loads from.
     *
     * @return array<string, array<string>> Map of directive name => list of allowed sources
     */
    public function cspDirectives(): array;
}
