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

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Admin\AdminLoggedInController;

use App\Enums\AppearanceMode;
use App\Enums\LoginNotificationMode;
use App\Enums\TwoFactorMode;
use App\Enums\TwoFactorMethod;
use App\Rules\NotPwnedPassword;
use App\Enums\Locale;
use App\Models\MemberSetting;
use App\Services\MailServerValidatorService;
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
        
        // プロフィール画面だけアニメーションを有効にする（統一された速度）
        $transition = 'transition-colors duration-500';

        // layout クラスに transition を追加
        $appearanceClass = config('appearance.appearance_class');

        // 各セクションが存在する場合のみ処理
        if (isset($appearanceClass['layout'])) {
            foreach ($appearanceClass['layout'] as $key => $value) {
                $appearanceClass['layout'][$key] = $value . ' ' . $transition;
            }
        }
        
        if (isset($appearanceClass['sidebar'])) {
            foreach ($appearanceClass['sidebar'] as $key => $value) {
                $appearanceClass['sidebar'][$key] = $value . ' ' . $transition;
            }
        }
        
        if (isset($appearanceClass['table'])) {
            foreach ($appearanceClass['table'] as $key => $value) {
                $appearanceClass['table'][$key] = $value . ' ' . $transition;
            }
        }
        
        if (isset($appearanceClass['link'])) {
            $appearanceClass['link'] .= ' ' . $transition;
        }
        
        if (isset($appearanceClass['form'])) {
            foreach ($appearanceClass['form'] as $key => $value) {
                $appearanceClass['form'][$key] = $value . ' ' . $transition;
            }
        }

        config(['appearance.appearance_class' => $appearanceClass]);

        // ✅ 外観モードをもとにクラスを生成
        $appearance = (int) (old('appearance') ?? Auth::guard('member')->user()->appearance?->value ?? 0);

        $htmlClass = '';
        if ($appearance === 2 || ($appearance === 0 && request()->cookie('prefers_dark') === '1')) {
            $htmlClass .= 'dark ';
        }
        $htmlClass .= ''; // 必要があれば他のclassもここで
        // アニメーションを有効にするため disable-transition はつけない
        $this->viewParams['htmlClass'] = trim($htmlClass);
        $this->viewParams['transitionEnabled'] = true;

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

        // 言語オプションの取得
        $this->viewParams['localeOptions'] = Locale::availableOptions();
        

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
        $passkeyEnabled = MemberSetting::getValue('passkey_enabled', '0') === '1';
        
        // メール認証は常に有効、Passkeyは設定に応じて
        $enabledTwoFactorMethods = [TwoFactorMethod::EMAIL->value];
        if ($passkeyEnabled) {
            $enabledTwoFactorMethods[] = TwoFactorMethod::PASSKEY->value;
        }
        
        $defaultTwoFactorMethod = (int) MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value);
        
        \Log::info('プロフィール2FA設定デバッグ', [
            'force2fa' => $force2fa,
            'passkey_enabled' => $passkeyEnabled,
            'enabled_methods' => $enabledTwoFactorMethods,
            'default_method' => $defaultTwoFactorMethod,
        ]);

        // プロフィール用の二段階認証オプション
        // 全体設定が「プロフィール設定を反映」の場合は、無効/異なる端末時のみ/常に有効から選択可能
        if ($force2fa === TwoFactorMode::UseProfileSetting->value) {
            $profileTwoFactorOptions = [
                TwoFactorMode::Disabled->value => __('common.two_factor_mode.options.' . TwoFactorMode::Disabled->value), // 無効
                TwoFactorMode::Always->value => __('common.two_factor_mode.options.' . TwoFactorMode::Always->value), // 有効
            ];
        } else {
            // 従来通り（無効、有効のみ）
            $profileTwoFactorOptions = [
                '0' => __('common.two_factor_mode.options.0'), // 無効
                '1' => __('common.two_factor_mode.options.1'), // 有効
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

        // 認証方法選択を表示するかどうか
        // プロフィール設定に従う場合、または常に有効の場合は表示
        $showMethodSelection = ($force2fa === TwoFactorMode::UseProfileSetting->value) || 
                               ($force2fa === TwoFactorMode::Always->value);
        
        // 全体設定が Always の場合は現在の設定を表示用として取得
        $currentGlobalTwoFactorMode = null;
        if ($force2fa === TwoFactorMode::Always->value) {
            $currentGlobalTwoFactorMode = TwoFactorMode::from($force2fa);
        }
        
        \Log::info('プロフィール2FA表示デバッグ', [
            'available_method_options' => $availableMethodOptions,
            'available_method_count' => count($availableMethodOptions),
            'show_method_selection' => $showMethodSelection,
            'current_method' => $currentTwoFactorMethod,
        ]);

        $this->viewParams['force2fa'] = $force2fa;
        $this->viewParams['twoFactorMode'] = $twoFactorMode;
        $this->viewParams['profileTwoFactorOptions'] = $profileTwoFactorOptions;
        $this->viewParams['availableMethodOptions'] = $availableMethodOptions;
        $this->viewParams['currentTwoFactorMethod'] = $currentTwoFactorMethod;
        $this->viewParams['defaultTwoFactorMethod'] = $defaultTwoFactorMethod;
        $this->viewParams['showMethodSelection'] = $showMethodSelection;
        $this->viewParams['currentGlobalTwoFactorMode'] = $currentGlobalTwoFactorMode;

        // pending_email がある場合の情報を渡す
        $this->viewParams['hasPendingEmail'] = !empty(Auth::guard('member')->user()->pending_email);
        $this->viewParams['pendingEmail'] = Auth::guard('member')->user()->pending_email;

        // メールサーバー設定状態を渡す
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        // Passkeyデバイス一覧を取得
        $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
        $this->viewParams['passkeyDevices'] = $passkeyService->getDevices($user);

        // 回復コード情報を取得
        $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);
        $this->viewParams['recoveryCodesCount'] = $recoveryCodeService->getRemainingCount($user);
        $this->viewParams['hasRecoveryCodes'] = $recoveryCodeService->hasRecoveryCodes($user);

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
            
            // パスワード辞書攻撃対策
            $passwordRules[] = new NotPwnedPassword();
            
            \Log::info('Password complexity rules added');
        }

        \Log::info('Final password rules', ['rules' => $passwordRules]);

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'required|string|email|max:255|unique:members,email,' . $member->id,
            'locale' => 'nullable|string|in:' . implode(',', Locale::values()),
            'password' => $passwordRules,
            'appearance' => ['nullable', new Enum(AppearanceMode::class)],
            'login_notification_mode' => ['nullable', new Enum(LoginNotificationMode::class)],
            'two_factor_mode' => ['nullable', new Enum(TwoFactorMode::class)],
            'two_factor_method' => 'nullable|integer',
        ];

        // メールアドレスが変更された場合は確認フィールドを必須に
        if ($request->input('email') !== $member->email) {
            $rules['email_confirmation'] = 'required|email|same:email';
        }

        // 二段階認証方法のバリデーション（有効な方法の中から選択されているかチェック）
        $enabledTwoFactorMethodsString = MemberSetting::getValue('enabled_two_factor_methods', '0');
        $enabledTwoFactorMethods = $enabledTwoFactorMethodsString ? array_map('intval', explode(',', $enabledTwoFactorMethodsString)) : [0];
        $force2faValue = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::UseProfileSetting->value);
        $defaultTwoFactorMethod = (int) MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value);
        
        // フィールドが表示・編集可能な場合のみ認証方法選択をバリデーション
        // UseProfileSettingの場合のみフィールドが編集可能（Alwaysの場合は表示のみまたは非表示）
        if ($force2faValue === TwoFactorMode::UseProfileSetting->value && !empty($enabledTwoFactorMethods)) {
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

        // メールアドレスの変更を検知
        $emailChanged = $member->email !== $validated['email'];
        
        // メールサーバー設定状態を確認
        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        
        // プロフィール更新
        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'locale' => $validated['locale'] ?? null,
            'appearance' => (int) $validated['appearance'] ?? null,
        ];
        
        // メールアドレス変更の処理
        if ($emailChanged) {
            if ($isMailServerTested) {
                // メールサーバー設定済み：pending_emailに一時保存
                $updateData['pending_email'] = $validated['email'];
                // email自体は変更しない（認証完了まで現在のメールアドレスを維持）
            } else {
                // メールサーバー未設定：即時反映
                $updateData['email'] = $validated['email'];
                $updateData['pending_email'] = null;
                $updateData['email_verified_at'] = now(); // 自動的に認証済み
            }
        } else {
            // メールアドレスが変更されていない場合、pending_emailをクリア
            $updateData['pending_email'] = null;
        }
        
        $member->update($updateData);
        
        // メールアドレス変更時に認証メールを送信（メールサーバー設定済みの場合のみ）
        if ($emailChanged && $isMailServerTested) {
            try {
                // 認証メールを新しいメールアドレス（pending_email）に送信
                $member->sendEmailVerificationNotification('email_change');
                \Log::info('Email verification sent', [
                    'member_id' => $member->id,
                    'pending_email' => $member->pending_email
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to send email verification', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // 言語設定が変更された場合、即座に適用
        if (isset($validated['locale']) && $validated['locale']) {
            \Illuminate\Support\Facades\App::setLocale($validated['locale']);
        }

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
        if (in_array($force2fa, [TwoFactorMode::UseProfileSetting->value, TwoFactorMode::Always->value])) {
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

        // メールアドレス変更時のメッセージ
        if ($emailChanged && $isMailServerTested) {
            // メールサーバー設定済み：認証メール送信を通知
            $message = __('admin.profile.updated_with_email_verification');
        } elseif ($emailChanged && !$isMailServerTested) {
            // メールサーバー未設定：即時反映を通知
            $message = __('admin.profile.updated_email_immediate');
        } else {
            // メールアドレス変更なし
            $message = __('admin.profile.updated');
        }

        return redirect()->route('admin.profile')
            ->with('success', $message);
    }

    /**
     * メール認証処理（セキュリティ強化版：ログイン後に認証）
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        // IDからメンバーを取得
        $member = \App\Models\Member::findOrFail($id);

        // ハッシュの検証
        if (!hash_equals((string) $hash, sha1($member->getEmailForVerification()))) {
            return redirect()->route('admin.login')
                ->with('error', __('admin.profile.email_verification_invalid'));
        }

        // 既に認証済みの場合（pending_emailがない場合）
        if ($member->hasVerifiedEmail() && !$member->pending_email) {
            return redirect()->route('admin.login')
                ->with('info', __('admin.profile.email_already_verified'));
        }

        // ログイン状態をチェック
        $currentUser = \Auth::guard('member')->user();
        
        // ログイン済みで、認証対象のメンバーと一致する場合は即座に処理
        if ($currentUser && $currentUser->id === $member->id) {
            return $this->processEmailVerificationImmediately($member, $hash);
        }

        // 未ログインまたは別のユーザーでログイン中の場合
        // 認証トークン情報をセッションに保存
        session([
            'email_verification_pending' => [
                'member_id' => $member->id,
                'hash' => $hash,
                'email' => $member->pending_email ?? $member->email,
                'is_email_change' => (bool) $member->pending_email,
                'expires_at' => now()->addMinutes(30)->timestamp,
            ]
        ]);
        
        // セッション保存を確実にする
        session()->save();

        // コンテキストに応じたメッセージを選択
        $messageKey = $member->pending_email 
            ? 'auth.verify_email_change_login_required'
            : 'auth.verify_email_login_required';

        // ログイン画面にリダイレクト
        return redirect()->route('admin.login')
            ->with('info', __($messageKey));
    }
    
    /**
     * ログイン済みの場合、即座にメール認証を処理
     */
    protected function processEmailVerificationImmediately($member, $hash)
    {
        try {
            if ($member->pending_email) {
                // メールアドレス変更の認証
                $member->email = $member->pending_email;
                $member->pending_email = null;
                $member->email_verified_at = now();
                $member->save();
                
                \Log::info('Email change verified immediately (logged in)', [
                    'member_id' => $member->id,
                    'new_email' => $member->email
                ]);
                
                return redirect()->route('admin.profile')
                    ->with('success', __('admin.profile.email_verification_success'));
            } else {
                // 新規アカウントの認証
                $member->markEmailAsVerified();
                
                \Log::info('Account verified immediately (logged in)', [
                    'member_id' => $member->id,
                    'email' => $member->email
                ]);
                
                // メールサーバー設定済みの場合のみ通知を送信
                if (\App\Services\MailServerValidatorService::isMailServerTested()) {
                    try {
                        // メンバー本人に認証完了メールを送信
                        $member->notify(new \App\Notifications\MemberVerificationCompletedNotification());
                        
                        \Log::info('Verification completed notification sent to member (immediate)', [
                            'member_id' => $member->id,
                            'email' => $member->email
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to send verification completed notification to member', [
                            'member_id' => $member->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    try {
                        // 管理者に通知
                        $adminEmail = \App\Models\BaseSetting::getValue('system_admin_email') 
                            ?? \App\Models\BaseSetting::getValue('notification_email');
                        
                        if ($adminEmail) {
                            \Illuminate\Support\Facades\Notification::route('mail', $adminEmail)
                                ->notify(new \App\Notifications\AdminMemberVerifiedNotification(
                                    $member,
                                    now()->format('Y-m-d H:i:s')
                                ));
                            
                            \Log::info('Verification notification sent to admin (immediate)', [
                                'member_id' => $member->id,
                                'admin_email' => $adminEmail
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to send verification notification to admin', [
                            'member_id' => $member->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                return redirect()->route('admin.dashboard')
                    ->with('success', __('admin.profile.account_verification_success'));
            }
        } catch (\Exception $e) {
            \Log::error('Email verification failed (immediate)', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.profile')
                ->with('error', __('auth.verification_failed'));
        }
    }

    /**
     * 生体認証の登録チャレンジを生成
     */
    public function generateBiometricChallenge(Request $request)
    {
        $member = Auth::guard('member')->user();
        
        try {
            $biometricService = app(\App\Services\BiometricAuthenticationService::class);

            // 生体認証が利用可能かチェック
            if (!$biometricService->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'HTTPS接続が必要です'
                ], 400);
            }

            // 登録チャレンジを生成
            $challenge = $biometricService->generateRegistrationChallenge($member);

            \Log::info("[Biometric Registration] チャレンジ生成: ユーザーID {$member->id}");

            return response()->json([
                'success' => true,
                'challenge' => $challenge
            ]);
        } catch (\Exception $e) {
            \Log::error("[Biometric Registration] チャレンジ生成エラー: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'チャレンジの生成に失敗しました'
            ], 500);
        }
    }

    /**
     * 生体認証を登録
     */
    public function registerBiometric(Request $request)
    {
        $member = Auth::guard('member')->user();

        $request->validate([
            'credential' => 'required|array',
            'device_name' => 'nullable|string|max:255',
        ]);

        try {
            $biometricService = app(\App\Services\BiometricAuthenticationService::class);
            $credential = $request->input('credential');
            $deviceName = $request->input('device_name');

            // 認証情報を登録
            $biometricService->registerCredential($member, $credential, $deviceName);

            \Log::info("[Biometric Registration] 登録成功: ユーザーID {$member->id}");

            return response()->json([
                'success' => true,
                'message' => '生体認証を登録しました'
            ]);
        } catch (\Exception $e) {
            \Log::error("[Biometric Registration] 登録エラー: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '生体認証の登録に失敗しました'
            ], 500);
        }
    }

    /**
     * 生体認証を削除
     */
    public function revokeBiometric(Request $request, string $credentialId)
    {
        $member = Auth::guard('member')->user();

        try {
            $biometricService = app(\App\Services\BiometricAuthenticationService::class);

            if ($biometricService->revokeCredential($member, $credentialId)) {
                \Log::info("[Biometric Registration] 削除成功: ユーザーID {$member->id}, 認証情報ID: {$credentialId}");

                return response()->json([
                    'success' => true,
                    'message' => '生体認証を削除しました'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '生体認証が見つかりません'
                ], 404);
            }
        } catch (\Exception $e) {
            \Log::error("[Biometric Registration] 削除エラー: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '生体認証の削除に失敗しました'
            ], 500);
        }
    }

    /**
     * 信頼済みデバイスを削除
     */
    public function revokeTrustedDevice(Request $request, int $deviceId)
    {
        $member = Auth::guard('member')->user();

        try {
            $deviceService = app(\App\Services\DeviceAuthenticationService::class);

            if ($deviceService->revokeDevice($member, $deviceId)) {
                \Log::info("[Device Auth] 削除成功: ユーザーID {$member->id}, デバイスID: {$deviceId}");

                return response()->json([
                    'success' => true,
                    'message' => __('admin.profile.device_deleted_successfully')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.profile.device_not_found')
                ], 404);
            }
        } catch (\Exception $e) {
            \Log::error("[Device Auth] 削除エラー: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('admin.profile.delete_device_error')
            ], 500);
        }
    }

    /**
     * すべての信頼済みデバイスを削除
     */
    public function revokeAllTrustedDevices(Request $request)
    {
        $member = Auth::guard('member')->user();

        try {
            $count = \App\Models\MembersTrustedDevice::where('member_id', $member->id)->delete();
            
            \Log::info("[Device Auth] 一括削除成功: ユーザーID {$member->id}, 削除数: {$count}");

            return response()->json([
                'success' => true,
                'message' => __('admin.profile.all_devices_deleted_successfully', ['count' => $count])
            ]);
        } catch (\Exception $e) {
            \Log::error("[Device Auth] 一括削除エラー: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('admin.profile.delete_all_devices_error')
            ], 500);
        }
    }

    /**
     * すべての生体認証を削除
     */
    public function revokeAllBiometric(Request $request)
    {
        $member = Auth::guard('member')->user();

        try {
            $count = \App\Models\MembersTwoFactorDevice::where('member_id', $member->id)->delete();
            
            \Log::info("[Biometric Auth] 一括削除成功: ユーザーID {$member->id}, 削除数: {$count}");

            return response()->json([
                'success' => true,
                'message' => __('admin.profile.all_biometric_deleted_successfully', ['count' => $count])
            ]);
        } catch (\Exception $e) {
            \Log::error("[Biometric Auth] 一括削除エラー: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('admin.profile.delete_all_biometric_error')
            ], 500);
        }
    }
}
