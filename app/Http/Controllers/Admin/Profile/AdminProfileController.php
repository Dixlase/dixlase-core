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
use App\Http\Requests\Admin\Profile\ProfileUpdateRequest;

use App\Enums\AppearanceMode;
use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
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
        // デバッグ: セッションの状態を確認
        \Log::info('[Profile Index] Session check', [
            'has_auto_generated_recovery_codes' => session()->has('auto_generated_recovery_codes'),
            'auto_generated_recovery_codes' => session('auto_generated_recovery_codes'),
            'all_session_keys' => array_keys(session()->all()),
        ]);
        
        // 回復コードセッションは一度表示したらクリア（モーダルを閉じた後は表示しない）
        // ただし、このリクエストでは表示するため、ビューに渡した後にクリア
        
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
            AuthenticationMode::UseProfileSetting->value
        );
        $loginNotificationMode = Auth::guard('member')->user()->login_notification_mode;

        // radio-card-group用のログイン通知オプション配列を生成
        $loginNotificationOptions = [];
        foreach (AuthenticationMode::forProfile() as $case) {
            $loginNotificationOptions[] = [
                'value' => (string)$case->value,
                'label' => $case->notificationLabel(),
            ];
        }

        $this->viewParams['loginNoticeGlobal'] = $loginNoticeGlobal;
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        $this->viewParams['loginNotificationOptions'] = $loginNotificationOptions;
        
        // ログイン通知モードの値を計算
        $member = Auth::guard('member')->user();
        $loginNotificationModeValue = is_int($member->login_notification_mode) 
            ? $member->login_notification_mode 
            : ($member->login_notification_mode?->value ?? 1);
        $this->viewParams['loginNotificationModeValue'] = $loginNotificationModeValue;

        // 二段階認証設定の追加
        $forceTwoFa = (int) MemberSetting::getValue(
            'two_fa_force_mode',
            AuthenticationMode::UseProfileSetting->value
        );
        $twoFaMode = $member->two_fa_mode;

        // グローバル設定で有効な二段階認証方法を取得
        $passkeyMode = (int) MemberSetting::getValue('two_fa_passkey_mode', '2');
        $passkeyEnabled = MemberSetting::getValue('two_fa_passkey_enabled', '0') === '1';
        
        // パスキー設定の計算
        $isPasskeyEditable = \App\Enums\PasskeyMode::isProfileEditable($passkeyMode);
        $forcedPasskeyValue = \App\Enums\PasskeyMode::getForcedProfileValue($passkeyMode);
        $currentPasskeyEnabled = $forcedPasskeyValue ?? ($member->two_fa_passkey_enabled ?? true);
        
        $this->viewParams['isPasskeyEditable'] = $isPasskeyEditable;
        $this->viewParams['forcedPasskeyValue'] = $forcedPasskeyValue;
        $this->viewParams['currentPasskeyEnabled'] = $currentPasskeyEnabled;
        
        // 二段階認証用の追加変数
        $passkeyGloballyEnabled = in_array(TwoFaMethod::PASSKEY->value, array_keys($enabledTwoFaMethods ?? []));
        $this->viewParams['passkeyGloballyEnabled'] = $passkeyGloballyEnabled;
        
        // メール認証は常に有効、Passkeyは設定に応じて（連想配列形式）
        $enabledTwoFaMethods = [
            TwoFaMethod::EMAIL->value => TwoFaMethod::EMAIL->translationKey(),
        ];
        if ($passkeyEnabled) {
            $enabledTwoFaMethods[TwoFaMethod::PASSKEY->value] = TwoFaMethod::PASSKEY->translationKey();
        }
        
        $defaultTwoFaMethod = (int) MemberSetting::getValue('two_fa_default_method', TwoFaMethod::EMAIL->value);

        // radio-card-group用の二段階認証オプション配列を生成
        // 全体設定が「プロフィール設定を反映」の場合は、無効/異なる端末時のみ/常に有効から選択可能
        $profileTwoFaOptions = [];
        if ($forceTwoFa === AuthenticationMode::UseProfileSetting->value) {
            foreach (AuthenticationMode::forProfile() as $case) {
                $profileTwoFaOptions[] = [
                    'value' => (string)$case->value,
                    'label' => $case->twoFactorLabel(),
                ];
            }
        } else {
            // 従来通り（無効、有効のみ）
            $profileTwoFaOptions = [
                ['value' => '0', 'label' => __('common.two_fa_mode.options.0')],
                ['value' => '1', 'label' => __('common.two_fa_mode.options.1')],
            ];
        }

        // 現在のユーザーの認証方法を取得
        $user = Auth::guard('member')->user();
        $currentTwoFaMethod = $user->two_fa_default_method ?? $defaultTwoFaMethod;
        
        // 現在のメソッドが有効なメソッドに含まれていない場合はデフォルトを使用
        if (!array_key_exists((int)$currentTwoFaMethod, $enabledTwoFaMethods) && !empty($enabledTwoFaMethods)) {
            $currentTwoFaMethod = $defaultTwoFaMethod;
            
            // デフォルトメソッドも有効でない場合は最初の有効なメソッドを使用
            if (!array_key_exists($currentTwoFaMethod, $enabledTwoFaMethods)) {
                $currentTwoFaMethod = array_key_first($enabledTwoFaMethods);
            }
            
            // ユーザーの設定を更新
            $user->two_fa_method = $currentTwoFaMethod;
            $user->save();
        }

        // 全体設定が Always の場合は現在の設定を表示用として取得
        $currentGlobalTwoFaMode = null;
        if ($forceTwoFa === AuthenticationMode::Always->value) {
            $currentGlobalTwoFaMode = AuthenticationMode::from($forceTwoFa);
        }

        $this->viewParams['forceTwoFa'] = $forceTwoFa;
        $this->viewParams['twoFaMode'] = $twoFaMode;
        $this->viewParams['profileTwoFaOptions'] = $profileTwoFaOptions;
        $this->viewParams['enabledTwoFaMethods'] = $enabledTwoFaMethods;
        $this->viewParams['passkeyMode'] = $passkeyMode;
        $this->viewParams['passkeyEnabled'] = $passkeyEnabled;
        $this->viewParams['currentTwoFaMethod'] = $currentTwoFaMethod;
        $this->viewParams['defaultTwoFaMethod'] = $defaultTwoFaMethod;
        $this->viewParams['currentGlobalTwoFaMode'] = $currentGlobalTwoFaMode;

        // pending_email がある場合の情報を渡す
        $this->viewParams['hasPendingEmail'] = !empty(Auth::guard('member')->user()->pending_email);
        $this->viewParams['pendingEmail'] = Auth::guard('member')->user()->pending_email;

        // メールサーバー設定状態を渡す
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        // Passkeyデバイス一覧を取得
        $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
        $this->viewParams['passkeyDevices'] = $passkeyService->getDevices($user);
        $this->viewParams['passkeyEnabled'] = $passkeyEnabled;

        // 回復コード情報を取得
        $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);
        $this->viewParams['recoveryCodesCount'] = $recoveryCodeService->getRemainingCount($user);
        $this->viewParams['hasRecoveryCodes'] = $recoveryCodeService->hasRecoveryCodes($user);
        $this->viewParams['canRegenerateRecoveryCodes'] = $recoveryCodeService->canRegenerate($user);
        $this->viewParams['nextRegenerateTime'] = $recoveryCodeService->getNextRegenerateTime($user);

        return view('admin.profile.index', $this->viewParams);
    }

    public function update(ProfileUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        
        // バリデーション済みデータを取得
        $validated = $request->validated();

        // メールアドレスの変更を検知
        $emailChanged = $member->email !== $validated['email'];
        
        // メールサーバー設定状態を確認
        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        
        // プロフィール更新
        $updateData = [
            'account_name' => $validated['account_name'],
            'display_name' => $validated['display_name'] ?? null,
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

        // login_notification_mode は全体設定が UseProfileSetting のときだけ上書き
        $globalLogin = (int) MemberSetting::getValue('login_notification_mode', AuthenticationMode::UseProfileSetting->value);
        if ($globalLogin === AuthenticationMode::UseProfileSetting->value && array_key_exists('login_notification_mode', $validated)) {
            $member->login_notification_mode = (int) $validated['login_notification_mode'];
        }

        // 二段階認証モードの変更を検出するため、保存前の値を取得（整数値として）
        $oldTwoFaMode = is_int($member->two_fa_mode) ? $member->two_fa_mode : $member->two_fa_mode->value;
        
        // two_fa_mode は全体設定が UseProfileSetting のときだけ上書き
        $forceTwoFa = (int) MemberSetting::getValue('two_fa_force_mode', AuthenticationMode::UseProfileSetting->value);
        if ($forceTwoFa === AuthenticationMode::UseProfileSetting->value && array_key_exists('two_fa_mode', $validated)) {
            $member->two_fa_mode = (int) $validated['two_fa_mode'];
        }

        // two_fa_passkey_enabled は全体設定の two_fa_passkey_mode が UseProfileSetting のときだけ上書き
        $passkeyMode = (int) MemberSetting::getValue('two_fa_passkey_mode', '2');
        if ($passkeyMode === \App\Enums\PasskeyMode::UseProfileSetting->value && array_key_exists('two_fa_passkey_enabled', $validated)) {
            $member->two_fa_passkey_enabled = (bool) $validated['two_fa_passkey_enabled'];
        }

        // two_fa_default_method の処理
        if (array_key_exists('two_fa_default_method', $validated)) {
            $member->two_fa_default_method = (int) $validated['two_fa_default_method'];
        }

        $member->save();

        // 二段階認証が有効化された場合、回復コードを自動生成
        $shouldGenerateRecoveryCodes = false;
        
        \Log::info('[Profile] Recovery code generation check', [
            'forceTwoFa' => $forceTwoFa,
            'forceTwoFa_expected' => AuthenticationMode::UseProfileSetting->value,
            'has_two_fa_mode_in_validated' => array_key_exists('two_fa_mode', $validated),
            'oldTwoFaMode' => $oldTwoFaMode,
            'newTwoFaMode' => isset($validated['two_fa_mode']) ? (int) $validated['two_fa_mode'] : null,
        ]);
        
        if ($forceTwoFa === AuthenticationMode::UseProfileSetting->value && array_key_exists('two_fa_mode', $validated)) {
            $newTwoFaMode = (int) $validated['two_fa_mode'];
            
            \Log::info('[Profile] Inside 2FA check block', [
                'oldTwoFaMode' => $oldTwoFaMode,
                'newTwoFaMode' => $newTwoFaMode,
                'Disabled_value' => AuthenticationMode::Disabled->value,
                'Always_value' => AuthenticationMode::Always->value,
            ]);
            
            // 無効→有効に変更された場合
            if ($oldTwoFaMode === AuthenticationMode::Disabled->value && 
                $newTwoFaMode === AuthenticationMode::Always->value) {
                
                $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);
                
                // 回復コードが存在しない場合は生成
                $hasRecoveryCodes = $recoveryCodeService->hasRecoveryCodes($member);
                
                \Log::info('[Profile] Recovery codes existence check', [
                    'member_id' => $member->id,
                    'has_recovery_codes' => $hasRecoveryCodes,
                ]);
                
                if (!$hasRecoveryCodes) {
                    try {
                        $twoFactorHelper = app(\App\Helpers\TwoFaHelper::class);
                        $codes = $twoFactorHelper->generateRecoveryCodes($member, true);
                        $shouldGenerateRecoveryCodes = true;
                        
                        \Log::info('[Profile] Recovery codes auto-generated on 2FA activation', [
                            'member_id' => $member->id,
                            'codes_count' => count($codes)
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('[Profile] Failed to auto-generate recovery codes', [
                            'member_id' => $member->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    \Log::info('[Profile] Recovery codes already exist on 2FA activation', [
                        'member_id' => $member->id,
                    ]);
                    
                    // 2FA有効化時は既存の回復コードがあればそのまま使用
                    // 間隔チェックは行わない（手動再生成時のみチェック）
                }
            }
        }

        // メールアドレス変更時のメッセージ
        if ($emailChanged && $isMailServerTested) {
            // メールサーバー設定済み：認証メール送信を通知
            $message = __('admin/profile.updated_with_email_verification');
        } elseif ($emailChanged && !$isMailServerTested) {
            // メールサーバー未設定：即時反映を通知
            $message = __('admin/profile.updated_email_immediate');
        } else {
            // メールアドレス変更なし
            $message = __('admin/profile.updated');
        }

        $redirect = redirect()->route('admin.profile')->with('success', $message);
        
        // 回復コードが生成された場合はセッションに保存
        if ($shouldGenerateRecoveryCodes && isset($codes)) {
            \Log::info('[Profile Update] Setting auto_generated_recovery_codes in session', [
                'codes_count' => count($codes),
                'member_id' => $member->id,
            ]);
            $redirect->with('auto_generated_recovery_codes', $codes);
        } else {
            \Log::info('[Profile Update] NOT setting auto_generated_recovery_codes', [
                'shouldGenerateRecoveryCodes' => $shouldGenerateRecoveryCodes,
                'codes_isset' => isset($codes),
            ]);
        }

        return $redirect;
    }

    /**
     * メール認証処理（セキュリティ強化版：ログイン後に認証）
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $emailVerificationHelper = app(\App\Helpers\EmailVerificationHelper::class);
        
        // IDからメンバーを取得
        $member = \App\Models\Member::findOrFail($id);

        // ハッシュの検証
        if (!$emailVerificationHelper->verifyHash($member, $hash)) {
            return redirect()->route('admin.login')
                ->with('error', __('admin/profile.email_verification_invalid'));
        }

        // 認証が必要かチェック
        $verificationStatus = $emailVerificationHelper->needsVerification($member);
        if (!$verificationStatus['needs_verification']) {
            return redirect()->route('admin.login')
                ->with('info', __('admin/profile.email_already_verified'));
        }

        // ログイン状態をチェック
        $currentUser = \Auth::guard('member')->user();
        
        // ログイン済みで、認証対象のメンバーと一致する場合は即座に処理
        if ($currentUser && $currentUser->id === $member->id) {
            $result = $emailVerificationHelper->processVerificationImmediately($member, 'admin');
            return redirect($result['redirect'])->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        }

        // 未ログインまたは別のユーザーでログイン中の場合
        // 認証トークン情報をセッションに保存
        $emailVerificationHelper->storeVerificationInSession($member, $hash);

        // コンテキストに応じたメッセージを選択
        $messageKey = $emailVerificationHelper->getLoginRequiredMessageKey(
            $verificationStatus['is_email_change']
        );

        // ログイン画面にリダイレクト
        return redirect()->route('admin.login')->with('info', __($messageKey));
    }

    /**
     * Passkey登録用のWebAuthnチャレンジを生成
     */
    public function passkeyRegisterOptions(Request $request)
    {
        $member = Auth::guard('member')->user();
        $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
        
        try {
            // WebAuthn登録チャレンジを生成
            $options = $passkeyService->generateRegistrationChallenge($member);
            
            return response()->json([
                'success' => true,
                'options' => $options
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] 登録チャレンジ生成エラー', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('admin/profile.passkey_register_options_error')
            ], 500);
        }
    }

    /**
     * Passkeyを登録
     */
    public function passkeyRegister(Request $request)
    {
        $member = Auth::guard('member')->user();

        $request->validate([
            'credential' => 'required|array',
            'credential.id' => 'required|string',
            'credential.rawId' => 'required|string',
            'credential.response' => 'required|array',
            'credential.type' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
        
        try {
            // WebAuthn認証情報を登録
            $credential = $passkeyService->registerCredential(
                $member,
                $request->input('credential'),
                $request->input('device_name')
            );

            return response()->json([
                'success' => true,
                'message' => __('admin/profile.passkey_registered'),
                'credential' => [
                    'id' => $credential->id,
                    'name' => $credential->name,
                    'created_at' => $credential->created_at->format('Y-m-d H:i')
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] 登録エラー', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('admin/profile.passkey_register_error')
            ], 500);
        }
    }

    /**
     * Passkeyを削除
     */
    public function revokePasskey(Request $request, string $credentialId)
    {
        \Log::info('[Passkey Delete] Controller method called', [
            'credential_id' => $credentialId,
            'request_method' => $request->method(),
            'request_path' => $request->path(),
        ]);
        
        $member = Auth::guard('member')->user();
        \Log::info('[Passkey Delete] Member authenticated', [
            'member_id' => $member->id,
            'display_name' => $member->display_name ?? $member->account_name,
        ]);
        
        $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
        
        try {
            // 一括削除の場合
            if ($credentialId === 'all') {
                \Log::info('[Passkey Delete] Deleting all passkeys');
                $deletedCount = $passkeyService->revokeAllCredentials($member);
                \Log::info('[Passkey Delete] All passkeys deleted', ['count' => $deletedCount]);
                
                return response()->json([
                    'success' => true,
                    'message' => __('admin/profile.passkey_deleted_all', ['count' => $deletedCount])
                ]);
            }
            
            // 個別削除の場合
            \Log::info('[Passkey Delete] Calling revokeCredential service');
            $deleted = $passkeyService->revokeCredential($member, $credentialId);
            \Log::info('[Passkey Delete] Service returned', ['deleted' => $deleted]);
            
            if (!$deleted) {
                \Log::warning('[Passkey Delete] Credential not found');
                return response()->json([
                    'success' => false,
                    'message' => __('admin/profile.passkey_not_found')
                ], 404);
            }

            \Log::info('[Passkey Delete] Successfully deleted');
            return response()->json([
                'success' => true,
                'message' => __('admin/profile.passkey_deleted')
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey Delete] Exception caught', [
                'member_id' => $member->id,
                'credential_id' => $credentialId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('admin/profile.passkey_delete_error')
            ], 500);
        }
    }

    /**
     * 信頼済みデバイスを削除
     */
    public function revokeTrustedDevice(Request $request, int $deviceId)
    {
        $member = Auth::guard('member')->user();
        $twoFactorHelper = app(\App\Helpers\TwoFaHelper::class);
        
        $result = $twoFactorHelper->revokeTrustedDevice($member, $deviceId);
        
        $statusCode = $result['success'] ? 200 : (
            str_contains($result['message'], 'not_found') ? 404 : 500
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ], $statusCode);
    }

    /**
     * すべての信頼済みデバイスを削除
     */
    public function revokeAllTrustedDevices(Request $request)
    {
        $member = Auth::guard('member')->user();
        $twoFactorHelper = app(\App\Helpers\TwoFaHelper::class);
        
        $result = $twoFactorHelper->revokeAllTrustedDevices($member);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message']
        ], $result['success'] ? 200 : 500);
    }

    /**
     * すべてのPasskeyを削除
     */
    public function revokeAllPasskeys(Request $request)
    {
        $member = Auth::guard('member')->user();
        $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
        
        try {
            // すべてのPasskeyを取得して削除
            $credentials = $passkeyService->getCredentials($member);
            $deletedCount = 0;
            
            foreach ($credentials as $credential) {
                if ($passkeyService->revokeCredential($member, $credential->id)) {
                    $deletedCount++;
                }
            }
            
            if ($deletedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('admin/profile.no_passkeys_to_delete')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('admin/profile.all_passkeys_deleted', ['count' => $deletedCount])
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] 一括削除エラー', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('admin/profile.passkey_delete_all_error')
            ], 500);
        }
    }

    /**
     * 回復コードを生成
     */
    public function generateRecoveryCodes(Request $request)
    {
        $member = Auth::guard('member')->user();
        $twoFactorHelper = app(\App\Helpers\TwoFaHelper::class);
        $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);

        // 既に回復コードが存在する場合は再生成として扱う
        if ($recoveryCodeService->hasRecoveryCodes($member)) {
            return $this->regenerateRecoveryCodes($request);
        }

        try {
            // 回復コードを生成（初回生成）
            $codes = $twoFactorHelper->generateRecoveryCodes($member, false);

            return response()->json([
                'success' => true,
                'codes' => $codes,
                'message' => __('admin/profile.recovery_codes_generated')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('admin/profile.recovery_codes_generation_error')
            ], 500);
        }
    }

    /**
     * 回復コードを再生成
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $member = Auth::guard('member')->user();
        $twoFactorHelper = app(\App\Helpers\TwoFaHelper::class);

        $result = $twoFactorHelper->regenerateRecoveryCodes($member);

        if (!$result['success']) {
            $statusCode = isset($result['next_time']) ? 429 : 500;
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'codes' => $result['codes'],
            'message' => $result['message']
        ]);
    }

    /**
     * 回復コードセッションをクリア
     */
    public function clearRecoveryCodesSession(Request $request)
    {
        session()->forget('auto_generated_recovery_codes');
        
        \Log::info('[Profile] Recovery codes session cleared', [
            'member_id' => Auth::guard('member')->user()->id,
        ]);

        return response()->json([
            'success' => true
        ]);
    }
}
