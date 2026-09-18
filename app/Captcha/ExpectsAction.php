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

namespace App\Captcha;

/**
 * A driver whose verification takes the action the widget was rendered
 * with.
 *
 * Score-based reCAPTCHA embeds the action name in the token, and the
 * assessment can be told which action to expect; Google treats a mismatch
 * as a sign the token was obtained for one form and replayed on another.
 * CaptchaHelper::verify() knows the action but the CaptchaDriver contract
 * does not carry it, so drivers that can use it opt in through this
 * interface rather than widening the contract for every provider.
 *
 * Implementations must return a copy: the driver is bound as a singleton,
 * so state set for one verification must not leak into the next.
 */
interface ExpectsAction
{
    /**
     * Return a driver instance that will assess the token against the given
     * action (the same value handed to renderWidget()).
     */
    public function withExpectedAction(string $action): static;
}
