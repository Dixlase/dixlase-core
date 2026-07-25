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

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Member;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-fa', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::authenticateUsing(function (Request $request) {
            // Get login identifier mode
            $mode = \App\Enums\LoginIdentifierMode::tryFrom(
                (int) \App\Models\SecuritySetting::getValue('login_identifier_mode', \App\Enums\LoginIdentifierMode::EmailOrAccountName->value)
            ) ?? \App\Enums\LoginIdentifierMode::EmailOrAccountName;

            $login = $request->email;
            $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

            // Find member based on LoginIdentifierMode
            $member = null;
            if ($isEmail && $mode->supportsEmail()) {
                $member = Member::where('email', $login)->first();
            } elseif (! $isEmail && $mode->supportsAccountName()) {
                $member = Member::where('account_name', $login)->first();
            }

            if ($member && Hash::check($request->password, $member->password)) {
                // Reject login if the account is not in an active state
                if (! $member->canAuthenticate()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        Fortify::username() => [__('auth.account_inactive')],
                    ]);
                }

                // Check if email is verified
                if (! $member->hasVerifiedEmail()) {
                    // Reject login if not verified
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        Fortify::username() => [__('auth.email_not_verified')],
                    ]);
                }

                return $member;
            }
        });
    }
}
