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
use App\Enums\TwoFactorMethod;
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
        $force2fa = (int) MemberSetting::getValue(
            'force_2fa',
            TwoFactorMode::UseProfileSetting->value
        );
        $twoFactorMode = Auth::guard('member')->user()->two_factor_mode;

        // グローバル設定で有効な二段階認証方法を取得
        $enabledTwoFactorMethodsString = MemberSetting::getValue('enabled_two_factor_methods', '0');
        $enabledTwoFactorMethods = $enabledTwoFactorMethodsString ? array_map('intval', explode(',', $enabledTwoFactorMethodsString)) : [0];
        $defaultTwoFactorMethod = (int) MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value);

        // プロフィール用の二段階認証オプション
        // 全体設定が「プロフィール設定を反映」の場合は、無効/異なる端末時のみ/常に有効から選択可能
        if ($force2fa === TwoFactorMode::UseProfileSetting->value) {
            $profileTwoFactorOptions = [
                TwoFactorMode::Disabled->value => __('admin.profile.two_factor_mode_options.' . TwoFactorMode::Disabled->value), // 無効
                TwoFactorMode::OnlyNewDevice->value => __('admin.profile.two_factor_mode_options.' . TwoFactorMode::OnlyNewDevice->value), // 異なる端末/IP時のみ有効
                TwoFactorMode::Always->value => __('admin.profile.two_factor_mode_options.' . TwoFactorMode::Always->value), // 常に有効
            ];
        } else {
            // 従来通り（無効、有効のみ）
            $profileTwoFactorOptions = [
                '0' => __('admin.profile.two_factor_mode_options.0'), // 無効
                '1' => __('admin.profile.two_factor_mode_options.1'), // 有効
            ];
        }

        // 有効な認証方法のオプションを生成（プロフィール設定用）
        $availableMethodOptions = [];
        foreach ($enabledTwoFactorMethods as $methodValue) {
            try {
                $method = TwoFactorMethod::from((int) $methodValue);
                $availableMethodOptions[$method->value] = $method->label();
            } catch (\ValueError $e) {
                // 無効なメソッド値はスキップ
                continue;
            }
        }

        // 現在のユーザーの認証方法を取得
        $user = Auth::guard('member')->user();
        $currentTwoFactorMethod = $user->two_factor_method ?? $defaultTwoFactorMethod;
        
        // 現在のメソッドが有効なメソッドに含まれていない場合はデフォルトを使用
        if (!in_array((int)$currentTwoFactorMethod, $enabledTwoFactorMethods, true) && !empty($enabledTwoFactorMethods)) {
            $currentTwoFactorMethod = $defaultTwoFactorMethod;
            
            // デフォルトメソッドも有効でない場合は最初の有効なメソッドを使用
            if (!in_array($currentTwoFactorMethod, $enabledTwoFactorMethods, true)) {
                $currentTwoFactorMethod = $enabledTwoFactorMethods[0];
            }
            
            // ユーザーの設定を更新
            $user->two_factor_method = $currentTwoFactorMethod;
            $user->save();
        }

        // 認証方法選択を表示するかどうか（プロフィール設定を反映の場合、または複数の認証方法が有効な場合）
        $showMethodSelection = ($force2fa === TwoFactorMode::UseProfileSetting->value || count($availableMethodOptions) > 1);
        
        // 全体設定が OnlyNewDevice または Always の場合は現在の設定を表示用として取得
        $currentGlobalTwoFactorMode = null;
        if (in_array($force2fa, [TwoFactorMode::OnlyNewDevice->value, TwoFactorMode::Always->value], true)) {
            $currentGlobalTwoFactorMode = TwoFactorMode::from($force2fa);
        }

        $this->viewParams['force2fa'] = $force2fa;
        $this->viewParams['twoFactorMode'] = $twoFactorMode;
        $this->viewParams['profileTwoFactorOptions'] = $profileTwoFactorOptions;
        $this->viewParams['availableMethodOptions'] = $availableMethodOptions;
        $this->viewParams['currentTwoFactorMethod'] = $currentTwoFactorMethod;
        $this->viewParams['defaultTwoFactorMethod'] = $defaultTwoFactorMethod;
        $this->viewParams['showMethodSelection'] = $showMethodSelection;
        $this->viewParams['currentGlobalTwoFactorMode'] = $currentGlobalTwoFactorMode;

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

        // デバッグ用ログ出力
        \Log::info('Profile Update Debug', [
            'password_filled' => $request->filled('password'),
            'password_value' => $request->input('password') ? '[HIDDEN]' : 'null/empty',
            'password_confirmation_filled' => $request->filled('password_confirmation'),
            'password_confirmation_value' => $request->input('password_confirmation') ? '[HIDDEN]' : 'null/empty',
            'all_inputs' => array_keys($request->all())
        ]);

        // 🔽 パスワードのルールを動的に構築
        $passwordRules = ['nullable', "min:$minLength"];

        // パスワードが入力されている場合のみ確認を必須にする
        if ($showConfirmation && $request->filled('password') && $request->filled('password_confirmation')) {
            $passwordRules[] = 'confirmed';
            \Log::info('Password confirmation rule added');
        }

        // パスワードが入力されている場合のみ複雑性チェックを適用
        if ($request->filled('password')) {
            // 常に小文字と数字を必須にする
            $passwordRules[] = 'regex:/[a-z]/'; // 小文字
            $passwordRules[] = 'regex:/[0-9]/'; // 数字

            // 条件に応じて大文字と記号を追加
            if ($requireUppercase) {
                $passwordRules[] = 'regex:/[A-Z]/'; // 大文字
            }
            if ($requireSymbol) {
                $passwordRules[] = 'regex:/[!@#$%^&*(),.?":{}|<>]/'; // 記号
            }
            \Log::info('Password complexity rules added');
        }

        \Log::info('Final password rules', ['rules' => $passwordRules]);

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'required|string|email|max:255|unique:members,email,' . $member->id,
            'password' => $passwordRules,
            'appearance' => ['nullable', new Enum(AppearanceMode::class)],
            'login_notification_mode' => ['nullable', new Enum(LoginNotificationMode::class)],
            'two_factor_mode' => ['nullable', new Enum(TwoFactorMode::class)],
            'two_factor_method' => 'nullable|integer',
        ];

        // 二段階認証方法のバリデーション（有効な方法の中から選択されているかチェック）
        $enabledTwoFactorMethodsString = MemberSetting::getValue('enabled_two_factor_methods', '0');
        $enabledTwoFactorMethods = $enabledTwoFactorMethodsString ? array_map('intval', explode(',', $enabledTwoFactorMethodsString)) : [0];
        $force2faValue = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::UseProfileSetting->value);
        $defaultTwoFactorMethod = (int) MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value);
        
        // グローバル設定が有効な場合のみ認証方法選択をバリデーション
        if (in_array($force2faValue, [TwoFactorMode::UseProfileSetting->value, TwoFactorMode::OnlyNewDevice->value, TwoFactorMode::Always->value]) && !empty($enabledTwoFactorMethods)) {
            // 有効な認証方法が1つだけの場合はその方法を強制
            if (count($enabledTwoFactorMethods) === 1) {
                $rules['two_factor_method'] = 'required|integer|in:' . $enabledTwoFactorMethods[0];
            } else {
                $rules['two_factor_method'] = 'required|integer|in:' . implode(',', $enabledTwoFactorMethods);
            }
        }

        try {
            $validated = $request->validate($rules);
            \Log::info('Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', [
                'errors' => $e->errors(),
                'rules_applied' => $rules
            ]);
            throw $e;
        }

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

        // two_factor_mode は全体設定が UseProfileSetting のときだけ上書き
        $force2fa = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::UseProfileSetting->value);
        if ($force2fa === TwoFactorMode::UseProfileSetting->value && array_key_exists('two_factor_mode', $validated)) {
            $member->two_factor_mode = (int) $validated['two_factor_mode'];
        }

        // two_factor_method の処理
        if (in_array($force2fa, [TwoFactorMode::UseProfileSetting->value, TwoFactorMode::OnlyNewDevice->value, TwoFactorMode::Always->value])) {
            // 有効な認証方法を再度取得
            $enabledTwoFactorMethodsString = MemberSetting::getValue('enabled_two_factor_methods', '0');
            $enabledTwoFactorMethods = $enabledTwoFactorMethodsString ? array_map('intval', explode(',', $enabledTwoFactorMethodsString)) : [0];
            $defaultTwoFactorMethod = (int) MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value);
            
            // 送信された値が有効な方法かチェック
            $selectedMethod = isset($validated['two_factor_method']) ? (int)$validated['two_factor_method'] : $defaultTwoFactorMethod;
            
            // 選択された方法が有効でない場合はデフォルトの方法を使用
            if (!in_array($selectedMethod, $enabledTwoFactorMethods, true)) {
                $selectedMethod = $defaultTwoFactorMethod;
                
                // デフォルトの方法も有効でない場合は最初の有効な方法を使用
                if (!in_array($selectedMethod, $enabledTwoFactorMethods, true) && !empty($enabledTwoFactorMethods)) {
                    $selectedMethod = $enabledTwoFactorMethods[0];
                }
            }
            
            $member->two_factor_method = $selectedMethod;
        }

        $member->save();

        return redirect()->route('admin.profile')
            ->with('success', __('admin.profile.updated'));
    }
}
