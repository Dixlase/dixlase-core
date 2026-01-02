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

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\MemberSetting;
use App\Traits\PasswordResetTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AdminNewPasswordController extends Controller
{
    use PasswordResetTrait;

    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        $settingsGetter = fn($key, $default = null) => MemberSetting::getValue($key, $default);
        $this->validatePasswordResetAvailability($settingsGetter);
        
        // パスワード設定を取得
        $passwordSettings = $this->getPasswordSettings($settingsGetter);
        
        return view('auth.reset-password', [
            'title' => __('admin/auth.reset_password.title'),
            'header' => __('admin/auth.reset_password.header'),
            'description' => __('admin/auth.reset_password.description'),
            'route' => route('admin.password.store'),
            'token' => $request->route('token'),
            'email' => $request->email,
            'emailLabel' => __('admin/auth.reset_password.email'),
            'passwordLabel' => __('admin/auth.reset_password.password'),
            'submitText' => __('admin/auth.reset_password.reset_password_button'),
            'passwordMinLength' => $passwordSettings['min_length'],
            'passwordRequireUppercase' => $passwordSettings['require_uppercase'],
            'passwordRequireSymbol' => $passwordSettings['require_symbol'],
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $settingsGetter = fn($key, $default = null) => MemberSetting::getValue($key, $default);
        
        // パスワード設定を取得してバリデーション
        $passwordSettings = $this->getPasswordSettings($settingsGetter);
        $request->validate($this->getPasswordResetValidationRules($passwordSettings));

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::broker('members')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $this->performPasswordReset($user, $request->password);
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('success', __($status))
            : back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
