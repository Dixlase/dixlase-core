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

use Illuminate\Routing\Controller;
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
     * 設定取得用のクロージャを取得
     */
    protected function getSettingsGetter(): callable
    {
        return fn($key, $default = null) => MemberSetting::getValue($key, $default);
    }

    /**
     * Password brokerの名前を取得
     */
    protected function getPasswordResetBroker(): string
    {
        return 'members';
    }

    /**
     * ユーザーモデルのクラス名を取得
     */
    protected function getUserModelClass(): string
    {
        return \App\Models\Member::class;
    }

    /**
     * パスワードリセットリンク要求画面のビュー名を取得
     */
    protected function getForgotPasswordViewName(): string
    {
        return 'auth.forgot-password';
    }

    /**
     * パスワードリセット画面のビュー名を取得
     */
    protected function getResetPasswordViewName(): string
    {
        return 'auth.reset-password';
    }

    /**
     * パスワードリセット処理のルート名を取得
     */
    protected function getPasswordResetRoute(): string
    {
        return 'admin.password.store';
    }

    /**
     * ログイン画面のルート名を取得
     */
    protected function getLoginRoute(): string
    {
        return 'admin.login';
    }

    /**
     * CAPTCHAアクション名を取得
     */
    protected function getCaptchaAction(): string
    {
        return 'admin_password_reset';
    }

    /**
     * パスワードリセットリンク要求画面用の追加データを取得
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
