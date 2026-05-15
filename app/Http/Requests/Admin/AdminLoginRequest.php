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

namespace App\Http\Requests\Admin;

use App\Services\AdminLoginLockoutService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminLoginRequest extends FormRequest
{
    protected AdminLoginLockoutService $lockoutService;

    public function __construct()
    {
        parent::__construct();
        $this->lockoutService = new AdminLoginLockoutService();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(string $guard = 'member'): void
    {
        $login = $this->input('login');
        $ipAddress = $this->ip();

        // Lockout check
        $this->ensureIsNotLockedOut($login, $ipAddress);

        // Authentication attempt
        if (! Auth::guard($guard)->attempt($this->only('login', 'password'))) {
            // Process on failure
            $lockoutInfo = $this->lockoutService->handleFailedLogin($this, $login, \App\Models\MemberLoginAttempt::FAILURE_INVALID_PASSWORD);

            if ($lockoutInfo['is_locked_out'] || $lockoutInfo['is_ip_locked_out']) {
                throw ValidationException::withMessages([
                    'login' => $this->lockoutService->generateLockoutMessage($lockoutInfo),
                ]);
            }

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        // Process on success
        $this->lockoutService->handleSuccessfulLogin($login);
    }

    /**
     * Ensure the login request is not locked out.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotLockedOut(string $login, string $ipAddress): void
    {
        // User-based lockout check
        if ($this->lockoutService->isLockedOut($login)) {
            $remainingMinutes = $this->lockoutService->getLockoutRemainingMinutes($login);
            event(new Lockout($this));

            throw ValidationException::withMessages([
                'login' => trans('auth.throttle', [
                    'seconds' => ($remainingMinutes ?? 30) * 60,
                    'minutes' => $remainingMinutes ?? 30,
                ]),
            ]);
        }

        // IP-based lockout check
        if ($this->lockoutService->isIpLockedOut($ipAddress)) {
            event(new Lockout($this));

            throw ValidationException::withMessages([
                'login' => trans('auth.throttle', [
                    'seconds' => 1800,
                    'minutes' => 30,
                ]),
            ]);
        }
    }
}
