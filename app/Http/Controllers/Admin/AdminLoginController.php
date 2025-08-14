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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Member;
use App\Models\MemberSetting;
use Illuminate\Support\Facades\Hash;
use App\Services\AdminTwoFactorService;
use App\Services\AdminLoginNotificationService;
use App\Services\AdminLoginLockoutService;
use Illuminate\Support\Facades\Log;



class AdminLoginController extends AdminController
{

    //初期設定を行う
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Display the login view.
     */
    public function create()
    {
        $member = Auth::guard('member')->user();
        if ($member) {
            return redirect()->route('admin.dashboard');
        }

        // パスワードリセット機能の有効/無効設定を取得
        $this->viewParams['passwordResetEnabled'] = (bool) MemberSetting::getValue('password_reset_enabled', true);

        return view('admin::login', $this->viewParams);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(AdminLoginRequest $request)
    {
        $lockoutService = app(AdminLoginLockoutService::class);
        $email = $request->email;

        // ロックアウト状態をチェック
        if ($lockoutService->isLockedOut($email)) {
            $remainingMinutes = $lockoutService->getLockoutRemainingMinutes($email);
            return back()->withErrors([
                'email' => __('auth.lockout', ['minutes' => $remainingMinutes]),
            ]);
        }

        // IPアドレスベースのロックアウトもチェック
        if ($lockoutService->isIpLockedOut($request->ip())) {
            return back()->withErrors([
                'email' => __('auth.ip_lockout'),
            ]);
        }

        $member = Member::where('email', $email)->first();

        if (!$member || !Hash::check($request->password, $member->password)) {
            // 失敗したログインを記録
            $lockoutInfo = $lockoutService->handleFailedLogin($request, $email);
            
            $errorMessage = __('auth.failed');
            if ($lockoutInfo['locked_out']) {
                $errorMessage = __('auth.lockout', ['minutes' => $lockoutInfo['lockout_minutes']]);
            } elseif ($lockoutInfo['remaining_attempts'] > 0) {
                $errorMessage = __('auth.failed_with_attempts', ['attempts' => $lockoutInfo['remaining_attempts']]);
            }

            return back()->withErrors([
                'email' => $errorMessage,
            ]);
        }
        // 2FA 判定（有効な場合だけ進める）
        $twoFactor = app(AdminTwoFactorService::class);
        if ($twoFactor->has($member)) {
            session([
                'login.id' => $member->getAuthIdentifier(),
                'login.remember' => $request->boolean('remember'),
            ]);

            $twoFactor->generate($member); // ← ここでコード生成 + メール送信

            return redirect()->route('admin.two-factor.login'); // ← 入力画面へ遷移
        } else {
            // 成功したログインを記録（失敗記録をクリア）
            $lockoutService->handleSuccessfulLogin($email);

            // ログイン環境を記録、通知
            app(AdminLoginNotificationService::class)->handle($member, $request);

            // 2FA不要なら即ログイン
            Auth::guard('member')->login($member, $request->boolean('remember'));
            $request->session()->regenerate(true);
            return redirect()->intended(route('admin.dashboard'));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return to_route('admin.login');
    }

    public function showTwoFactorForm()
    {
        if (!session()->has('login.id')) {
            return redirect()->route('admin.login');
        }

        return view('admin::two-factor-challenge');
    }

    public function confirmTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }


        $twoFactor = app(AdminTwoFactorService::class);
        if (!$twoFactor->validate($member, $request->code)) {
            return back()->withErrors(['code' => __('auth.two_factor.invalid')]);
        }

        // 成功したログインを記録（失敗記録をクリア）
        app(AdminLoginLockoutService::class)->handleSuccessfulLogin($member->email);

        // ログイン環境を記録、通知
        app(AdminLoginNotificationService::class)->handle($member, $request);

        Auth::guard('member')->login($member, session('login.remember', false));
        session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate(true);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function resendTwoFactorCode(Request $request)
    {
        if (!session()->has('login.id')) {
            return redirect()->route('admin.login');
        }

        $member = Member::find(session('login.id'));

        if (!$member) {
            return redirect()->route('admin.login');
        }

        $twoFactor = app(AdminTwoFactorService::class);
        $twoFactor->generate($member); // ← DB保存 & メール送信

        return back()->with('status', __('auth.two_factor.resend_success'));
    }
}
