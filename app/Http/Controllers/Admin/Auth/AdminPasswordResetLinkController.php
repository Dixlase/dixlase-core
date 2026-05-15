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

namespace App\Http\Controllers\Admin\Auth;

use App\Models\SecuritySetting;
use App\Traits\PasswordResetTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AdminPasswordResetLinkController extends Controller
{
    use PasswordResetTrait;

    /**
     * Get the closure for retrieving settings
     */
    protected function getSettingsGetter(): callable
    {
        return fn ($key, $default = null) => SecuritySetting::get($key, $default);
    }

    /**
     * Get the name of the password broker
     */
    protected function getPasswordResetBroker(): string
    {
        return 'members';
    }

    /**
     * Get the class name of the user model
     */
    protected function getUserModelClass(): string
    {
        return \App\Models\Member::class;
    }

    /**
     * Get the view name for the password reset link request screen
     */
    protected function getForgotPasswordViewName(): string
    {
        return 'auth.forgot-password';
    }

    /**
     * Get the view name for the password reset screen
     */
    protected function getResetPasswordViewName(): string
    {
        return 'auth.reset-password';
    }

    /**
     * Get the route name for password reset processing
     */
    protected function getPasswordResetRoute(): string
    {
        return 'admin.password.store';
    }

    /**
     * Get the route name for the login screen
     */
    protected function getLoginRoute(): string
    {
        return 'admin.login';
    }

    /**
     * Get the CAPTCHA action name
     */
    protected function getCaptchaAction(): string
    {
        return 'admin_password_reset';
    }

    /**
     * Get additional data for the password reset link request screen
     */
    protected function getForgotPasswordViewData(): array
    {
        return [
            'title' => __('admin/auth.forgot_password.title'),
            'header' => __('admin/auth.forgot_password.header'),
            'description' => __('admin/auth.forgot_password.description'),
            'route' => route('admin.password.email'),
            'loginRoute' => route('admin.login'),
        ];
    }

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return $this->showForgotPasswordForm();
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        return $this->sendPasswordResetLink($request);
    }
}
