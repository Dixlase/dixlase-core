<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Traits;

use App\Helpers\IdentifierCheckHelper;
use Illuminate\Http\Request;

/**
 * Common trait for login identifier verification
 *
 * Check existence of email address or account name
 * Security measures: rate limiting, timing attack protection, audit logging
 */
trait LoginIdentifierCheckTrait
{
    /**
     * Get CAPTCHA action name (implemented by subclass)
     *
     * @return string CAPTCHA action name (e.g., 'admin_login', 'user_login')
     */
    abstract protected function getCaptchaAction(): string;

    /**
     * Get settings model class name (implemented by subclass)
     *
     * @return string Settings model class name
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * Get user model class name (implemented by subclass)
     *
     * @return string User model class name
     */
    abstract protected function getUserModelClass(): string;

    /**
     * Get context (implemented by subclass)
     *
     * @return string Context ('admin' or 'user')
     */
    abstract protected function getContext(): string;

    /**
     * Identifier verification process
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $login = $request->input('login');
        $ipAddress = $request->ip();

        // CAPTCHA verification
        $captchaAction = $this->getCaptchaAction();
        $captchaResult = \App\Helpers\CaptchaHelper::verify($request, $captchaAction);

        if ($captchaResult && ! $captchaResult->isValid()) {
            return response()->json([
                'redirect' => true,
                'message' => $captchaResult->getErrorMessage(),
                'errors' => ['captcha' => [$captchaResult->getErrorMessage()]],
            ], 422);
        }

        // Store CAPTCHA verified flag in session (valid for 5 minutes)
        session()->put('captcha_verified_'.$login, time());

        // Get lockout settings
        $settingModelClass = $this->getSettingModelClass();
        $settings = IdentifierCheckHelper::getLockoutSettings($settingModelClass);

        try {
            // Execute identifier verification (with rate limiting)
            $userModelClass = $this->getUserModelClass();
            $context = $this->getContext();

            $result = IdentifierCheckHelper::checkWithRateLimit(
                $login,
                $ipAddress,
                $userModelClass,
                $settings,
                $context
            );

            return response()->json([
                'exists' => $result['exists'],
                'has_passkey' => $result['has_passkey'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Get error message
            $errors = $e->errors();
            $errorMessage = $errors['login'][0] ?? $e->getMessage();

            // Store error message in session and return redirect instruction
            session()->flash('error', $errorMessage);

            return response()->json([
                'redirect' => true,
                'message' => $errorMessage,
            ], 422);
        }
    }
}
