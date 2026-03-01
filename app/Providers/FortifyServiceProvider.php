<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
            // ログイン識別子モードを取得
            $mode = \App\Enums\LoginIdentifierMode::tryFrom(
                (int) \App\Models\SecuritySetting::getValue('login_identifier_mode', \App\Enums\LoginIdentifierMode::EmailOrAccountName->value)
            ) ?? \App\Enums\LoginIdentifierMode::EmailOrAccountName;

            $login = $request->email;
            $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

            // LoginIdentifierModeに基づいてメンバーを検索
            $member = null;
            if ($isEmail && $mode->supportsEmail()) {
                $member = Member::where('email', $login)->first();
            } elseif (! $isEmail && $mode->supportsAccountName()) {
                $member = Member::where('account_name', $login)->first();
            }

            if ($member && Hash::check($request->password, $member->password)) {
                // メール認証済みかチェック
                if (! $member->hasVerifiedEmail()) {
                    // 未認証の場合はログインを拒否
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        Fortify::username() => [__('auth.email_not_verified')],
                    ]);
                }

                return $member;
            }
        });
    }
}
