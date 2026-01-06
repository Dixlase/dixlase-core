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
use App\Helpers\LoginHelper;
use App\Models\MemberSetting;
use App\Traits\PasswordResetTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AdminPasswordResetLinkController extends Controller
{
    use PasswordResetTrait;

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        $this->validatePasswordResetAvailability(fn($key, $default = null) => MemberSetting::getValue($key, $default));
        
        // CAPTCHA設定を取得
        $captchaPasswordResetEnabled = MemberSetting::getValue('captcha_password_reset_enabled', '0');

        // CAPTCHA設定
        $captchaEnabled = filter_var($captchaPasswordResetEnabled, FILTER_VALIDATE_BOOLEAN);

        // CAPTCHAウィジェットを生成
        $captchaWidget = null;
        if ($captchaEnabled) {
            $loginHelper = app(LoginHelper::class);
            $captchaWidget = $loginHelper->generateCaptchaWidget('admin_password_reset');
        }
        
        return view('auth.forgot_password', [
            'title' => __('admin/auth.forgot_password.title'),
            'header' => __('admin/auth.forgot_password.header'),
            'description' => __('admin/auth.forgot_password.description'),
            'route' => route('admin.password.email'),
            'loginRoute' => route('admin.login'),
            'captchaEnabled' => $captchaEnabled,
            'captchaWidget' => $captchaWidget,
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->validatePasswordResetAvailability(fn($key, $default = null) => MemberSetting::getValue($key, $default));
        
        // CAPTCHA設定を取得
        $captchaEnabled = filter_var(MemberSetting::getValue('captcha_password_reset_enabled', '0'), FILTER_VALIDATE_BOOLEAN);

        // CAPTCHAを検証
        $captchaError = $this->validateCaptcha($request, $captchaEnabled);
        if ($captchaError) {
            return back()
                ->withInput(['email' => $captchaError['email']])
                ->withErrors($captchaError['errors']);
        }

        // バリデーションルールを取得
        $request->validate($this->getPasswordResetLinkValidationRules());

        // メールアドレスに対応するメンバーを確認
        $member = \App\Models\Member::where('email', $request->email)->first();
        
        // メンバーが存在し、メール認証が未完了の場合はエラー
        if ($this->requiresEmailVerification($member)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.email_not_verified')]);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::broker('members')->sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
