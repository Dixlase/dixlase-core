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

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Admin\AdminLoggedInController;

use App\Enums\AppearanceMode;
use App\Enums\LoginNotificationMode;
use App\Enums\TwoFactorMode;
use App\Models\MemberSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;

class AdminProfileController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the form for editing the profile.
     */
    public function index()
    {
        // 外観モードのセッションをクリアして、保存された値に戻す
        session()->forget('appearance');
        
        // プロフィール画面だけアニメーションを有効にする
        $transition = 'transition-colors duration-300';

        // layout クラスに transition を追加
        $appearanceClass = config('admin.appearance_class');

        foreach ($appearanceClass['layout'] as $key => $value) {
            $appearanceClass['layout'][$key] = $value . ' ' . $transition;
        }
        foreach ($appearanceClass['sidebar'] as $key => $value) {
            $appearanceClass['sidebar'][$key] = $value . ' ' . $transition;
        }
        foreach ($appearanceClass['table'] as $key => $value) {
            $appearanceClass['table'][$key] = $value . ' ' . $transition;
        }
        $appearanceClass['link'] .= ' ' . $transition;
        foreach ($appearanceClass['form'] as $key => $value) {
            $appearanceClass['form'][$key] = $value . ' ' . $transition;
        }

        config(['admin.appearance_class' => $appearanceClass]);

        // ✅ 外観モードをもとにクラスを生成
        $appearance = (int) (old('appearance') ?? Auth::guard('member')->user()->appearance?->value ?? 0);

        $htmlClass = '';
        if ($appearance === 2 || ($appearance === 0 && request()->cookie('prefers_dark') === '1')) {
            $htmlClass .= 'dark ';
        }
        $htmlClass .= ''; // 必要があれば他のclassもここで
        // アニメーションを有効にするため disable-transition はつけない
        $this->viewParams['htmlClass'] = trim($htmlClass);

        // ✅ 外観モードのオプションなど他の処理（そのままでOK）
        $this->viewParams['appearanceOptions'] = collect(AppearanceMode::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();

        //外観モードの取得
        $appearanceOptions = collect(AppearanceMode::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();

        $this->viewParams['appearanceOptions'] = $appearanceOptions;

        // プロフィール画面だけアニメーションを有効にする
        $this->viewParams['transitionEnabled'] = true;

        // パスワード条件の取得
        $this->viewParams['passwordMinLength'] = (int) MemberSetting::getValue('password_min_length', 8);
        $this->viewParams['passwordRequireUppercase'] = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $this->viewParams['passwordRequireSymbol'] = (bool) MemberSetting::getValue('password_require_symbol', false);

        // ログイン通知設定の追加
        $loginNoticeGlobal = (int) MemberSetting::getValue(
            'login_notification_mode',
            LoginNotificationMode::UseProfileSetting->value
        );
        $loginNotificationMode = Auth::guard('member')->user()->login_notification_mode;

        $loginNotificationOptions = collect(LoginNotificationMode::forProfile())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();

        $this->viewParams['loginNoticeGlobal'] = $loginNoticeGlobal;
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        $this->viewParams['loginNotificationOptions'] = $loginNotificationOptions;

        // 二段階認証設定の追加
        $force2fa = MemberSetting::getValue(
            'force_2fa',
            TwoFactorMode::UseProfileSetting->value
        );
        $twoFactorMode = Auth::guard('member')->user()->two_factor_mode;

        $twoFactorOptions = collect(TwoFactorMode::forProfile())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();

        $this->viewParams['force2fa'] = $force2fa;
        $this->viewParams['twoFactorMode'] = $twoFactorMode;
        $this->viewParams['twoFactorOptions'] = $twoFactorOptions;

        return view('admin.profile.index', $this->viewParams);
    }

    public function update(Request $request)
    {
        $member = Auth::guard('member')->user();

        // 確認欄を表示するか（プロフィール画面では常に true）
        $showConfirmation = true;

        // 🔽 パスワード条件を全体設定から取得
        $minLength = (int) MemberSetting::getValue('password_min_length', 8);
        $requireUppercase = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $requireSymbol = (bool) MemberSetting::getValue('password_require_symbol', false);

        // 🔽 パスワードのルールを動的に構築
        $passwordRules = ['nullable', "min:$minLength"];

        // パスワードの確認が必要な場合
        if ($showConfirmation) {
            $passwordRules[] = 'confirmed';
        }

        // 常に小文字と数字を必須にする
        $passwordRules[] = 'regex:/[a-z]/'; // 小文字
        $passwordRules[] = 'regex:/[0-9]/'; // 数字

        // 条件に応じて大文字と記号を追加
        if ($requireUppercase) {
            $passwordRules[] = 'regex:/[A-Z]/'; // 英大文字
        }
        if ($requireSymbol) {
            $passwordRules[] = 'regex:/[!@#$%^&*(),.?":{}|<>]/'; // 記号
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'required|email|unique:members,email,' . $member->id,
            'password' => $passwordRules,
            'appearance' => ['nullable', new Enum(AppearanceMode::class)],
            'two_factor_mode' => ['nullable', new Enum(TwoFactorMode::class)],
            'login_notification_mode' => ['nullable', new Enum(LoginNotificationMode::class)],
        ]);

        $member->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'email' => $validated['email'],
            'appearance' => (int) $validated['appearance'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $member->password = Hash::make($validated['password']);
        }

        // login_notification_mode は全体設定が 0 のときだけ上書き
        $globalLogin = (int) MemberSetting::getValue('login_notification_mode', LoginNotificationMode::UseProfileSetting->value);
        if ($globalLogin === LoginNotificationMode::UseProfileSetting->value && array_key_exists('login_notification_mode', $validated)) {
            $member->login_notification_mode = (int) $validated['login_notification_mode'];
        }

        // two_factor_mode は全体設定が 0 のときだけ上書き
        $force2fa = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::UseProfileSetting->value);
        if ($force2fa === TwoFactorMode::UseProfileSetting->value && array_key_exists('two_factor_mode', $validated)) {
            $member->two_factor_mode = (int) $validated['two_factor_mode'];
        }

        $member->save();

        return redirect()->route('admin.profile')
            ->with('success', __('admin.profile.updated'));
    }
}
