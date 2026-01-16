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
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Traits\ManagesTwoFaTrait;
use App\Services\PasswordService;

class AdminProfileController extends AdminLoggedInController
{
    use ManagesTwoFaTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * モデルのルートパラメータ名を取得
     */
    protected function getModelRouteParameterName(): string
    {
        return 'member';
    }

    /**
     * Show the form for editing the profile.
     */
    public function index()
    {
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
        $twoFaForceMode = (int) MemberSetting::getValue(
            'two_fa_mode',
            AuthenticationMode::UseProfileSetting->value
        );
        $twoFaMode = $member->two_fa_mode;

        // グローバル設定で有効な二段階認証方法を取得
        $twoFaPasskeyMode = (int) MemberSetting::getValue('two_fa_passkey_mode', '2');
        // two_fa_passkey_modeが0（無効）以外ならPasskeyは有効とみなす
        $twoFaPasskeyEnabled = $twoFaPasskeyMode > 0;
        
        // パスキー設定の計算
        $twoFaPasskeyEditable = \App\Enums\PasskeyMode::isProfileEditable($twoFaPasskeyMode);
        $twoFaPasskeyForcedValue = \App\Enums\PasskeyMode::getForcedProfileValue($twoFaPasskeyMode);
        $twoFaPasskeyCurrentEnabled = $twoFaPasskeyForcedValue ?? ($member->two_fa_passkey_enabled ?? true);
        
        $this->viewParams['isPasskeyEditable'] = $twoFaPasskeyEditable;
        $this->viewParams['forcedPasskeyValue'] = $twoFaPasskeyForcedValue;
        $this->viewParams['currentPasskeyEnabled'] = $twoFaPasskeyCurrentEnabled;
        
        // 二段階認証用の追加変数
        $twoFaPasskeyGloballyEnabled = in_array(TwoFaMethod::PASSKEY->value, array_keys($twoFaEnabledMethods ?? []));
        $this->viewParams['passkeyGloballyEnabled'] = $twoFaPasskeyGloballyEnabled;
        
        // メール認証は常に有効、Passkeyは設定に応じて（連想配列形式）
        $twoFaEnabledMethods = [
            TwoFaMethod::EMAIL->value => TwoFaMethod::EMAIL->translationKey(),
        ];
        if ($twoFaPasskeyEnabled) {
            $twoFaEnabledMethods[TwoFaMethod::PASSKEY->value] = TwoFaMethod::PASSKEY->translationKey();
        }
        
        $twoFaDefaultMethod = (int) MemberSetting::getValue('two_fa_default_method', TwoFaMethod::EMAIL->value);

        // radio-card-group用の二段階認証オプション配列を生成
        // 全体設定が「プロフィール設定を反映」の場合は、無効/異なる端末時のみ/常に有効から選択可能
        $twoFaProfileOptions = [];
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value) {
            foreach (AuthenticationMode::forProfile() as $case) {
                $twoFaProfileOptions[] = [
                    'value' => (string)$case->value,
                    'label' => $case->twoFactorLabel(),
                ];
            }
        } else {
            // 従来通り（無効、有効のみ）
            $twoFaProfileOptions = [
                ['value' => '0', 'label' => __('common.two_fa_mode.options.0')],
                ['value' => '1', 'label' => __('common.two_fa_mode.options.1')],
            ];
        }

        // 現在のユーザーの認証方法を取得
        $user = Auth::guard('member')->user();
        $twoFaCurrentMethod = $user->two_fa_default_method ?? $twoFaDefaultMethod;
        
        // 現在のメソッドが有効なメソッドに含まれていない場合はデフォルトを使用
        if (!array_key_exists((int)$twoFaCurrentMethod, $twoFaEnabledMethods) && !empty($twoFaEnabledMethods)) {
            $twoFaCurrentMethod = $twoFaDefaultMethod;
            
            // デフォルトメソッドも有効でない場合は最初の有効なメソッドを使用
            if (!array_key_exists($twoFaCurrentMethod, $twoFaEnabledMethods)) {
                $twoFaCurrentMethod = array_key_first($twoFaEnabledMethods);
            }
            
            // ユーザーの設定を更新
            $user->two_fa_method = $twoFaCurrentMethod;
            $user->save();
        }

        // 全体設定が Always の場合は現在の設定を表示用として取得
        $twoFaCurrentGlobalMode = null;
        if ($twoFaForceMode === AuthenticationMode::Always->value) {
            $twoFaCurrentGlobalMode = AuthenticationMode::from($twoFaForceMode);
        }

        $this->viewParams['twoFaForceMode'] = $twoFaForceMode;
        $this->viewParams['twoFaMode'] = $twoFaMode;
        $this->viewParams['twoFaProfileOptions'] = $twoFaProfileOptions;
        $this->viewParams['twoFaEnabledMethods'] = $twoFaEnabledMethods;
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;
        $this->viewParams['twoFaCurrentMethod'] = $twoFaCurrentMethod;
        $this->viewParams['twoFaDefaultMethod'] = $twoFaDefaultMethod;
        $this->viewParams['twoFaCurrentGlobalMode'] = $twoFaCurrentGlobalMode;

        // pending_email がある場合の情報を渡す
        $this->viewParams['hasPendingEmail'] = !empty(Auth::guard('member')->user()->pending_email);
        $this->viewParams['pendingEmail'] = Auth::guard('member')->user()->pending_email;

        // メールサーバー設定状態を渡す
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        // Passkeyデバイス一覧を取得
        $twoFaPasskeyService = new TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($user);

        // 回復コード情報を取得
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($user);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($user);
        $this->viewParams['twoFaCanRegenerateRecoveryCodes'] = $twoFaRecoveryCodeService->canRegenerate($user);
        $this->viewParams['twoFaNextRegenerateTime'] = $twoFaRecoveryCodeService->getNextRegenerateTime($user);

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
            $member->password = PasswordService::hash($validated['password']);
        }

        // login_notification_mode は全体設定が UseProfileSetting のときだけ上書き
        $globalLogin = (int) MemberSetting::getValue('login_notification_mode', AuthenticationMode::UseProfileSetting->value);
        if ($globalLogin === AuthenticationMode::UseProfileSetting->value && array_key_exists('login_notification_mode', $validated)) {
            $member->login_notification_mode = (int) $validated['login_notification_mode'];
        }

        // 二段階認証モードの変更を検出するため、保存前の値を取得（整数値として）
        $twoFaOldMode = is_int($member->two_fa_mode) ? $member->two_fa_mode : $member->two_fa_mode->value;
        
        // two_fa_mode は全体設定が UseProfileSetting のときだけ上書き
        $twoFaForceMode = (int) MemberSetting::getValue('two_fa_mode', AuthenticationMode::UseProfileSetting->value);
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value && array_key_exists('two_fa_mode', $validated)) {
            $member->two_fa_mode = (int) $validated['two_fa_mode'];
        }

        // two_fa_passkey_enabled は全体設定の two_fa_passkey_mode が UseProfileSetting のときだけ上書き
        $twoFaPasskeyMode = (int) MemberSetting::getValue('two_fa_passkey_mode', '2');
        if ($twoFaPasskeyMode === \App\Enums\PasskeyMode::UseProfileSetting->value && array_key_exists('two_fa_passkey_enabled', $validated)) {
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
            'two_fa_mode' => $twoFaForceMode,
            'two_fa_mode_expected' => AuthenticationMode::UseProfileSetting->value,
            'has_two_fa_mode_in_validated' => array_key_exists('two_fa_mode', $validated),
            'twoFaOldMode' => $twoFaOldMode,
            'twoFaNewMode' => isset($validated['two_fa_mode']) ? (int) $validated['two_fa_mode'] : null,
        ]);
        
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value && array_key_exists('two_fa_mode', $validated)) {
            $twoFaNewMode = (int) $validated['two_fa_mode'];
            
            \Log::info('[Profile] Inside 2FA check block', [
                'twoFaOldMode' => $twoFaOldMode,
                'twoFaNewMode' => $twoFaNewMode,
                'Disabled_value' => AuthenticationMode::Disabled->value,
                'Always_value' => AuthenticationMode::Always->value,
            ]);
            
            // 無効→有効に変更された場合
            if ($twoFaOldMode === AuthenticationMode::Disabled->value && 
                $twoFaNewMode === AuthenticationMode::Always->value) {
                
                $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
                
                // 回復コードが存在しない場合は生成
                $twoFaHasRecoveryCodes = $twoFaRecoveryCodeService->hasRecoveryCodes($member);
                
                \Log::info('[Profile] Recovery codes existence check', [
                    'member_id' => $member->id,
                    'has_recovery_codes' => $twoFaHasRecoveryCodes,
                ]);
                
                if (!$twoFaHasRecoveryCodes) {
                    try {
                        $recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);
                        $codes = $recoveryCodeService->generate($member);
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
        
        return $this->generatePasskeyRegistrationOptions(
            $member,
            'admin/profile.passkey_register_options_error'
        );
    }

    /**
     * Passkeyを登録
     */
    public function passkeyRegister(Request $request)
    {
        $member = Auth::guard('member')->user();
        
        return $this->registerPasskeyForModel(
            $request,
            $member,
            'admin/profile.passkey_registered',
            'admin/profile.passkey_register_error'
        );
    }

    /**
     * Passkeyを削除
     */
    public function revokePasskey(Request $request, string $credentialId)
    {
        $member = Auth::guard('member')->user();
        
        return $this->revokePasskeyForModel(
            $request,
            $member,
            $credentialId,
            'admin/profile.passkey_deleted_all',
            'admin/profile.passkey_not_found',
            'admin/profile.passkey_deleted',
            'admin/profile.passkey_delete_error'
        );
    }

    /**
     * すべてのPasskeyを削除
     */
    public function revokeAllPasskeys(Request $request)
    {
        $member = Auth::guard('member')->user();
        $twoFaPasskeyService = new TwoFaPasskeyService();
        
        try {
            // すべてのPasskeyを取得して削除
            $credentials = $twoFaPasskeyService->getCredentials($member);
            $deletedCount = 0;
            
            foreach ($credentials as $credential) {
                if ($twoFaPasskeyService->revokeCredential($member, $credential->id)) {
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
        $recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);

        // 既に回復コードが存在する場合は再生成として扱う
        if ($recoveryCodeService->hasRecoveryCodes($member)) {
            return $this->regenerateRecoveryCodes($request);
        }

        return $this->generateRecoveryCodesForModel(
            $member,
            'admin/profile.recovery_codes_generated',
            'admin/profile.recovery_codes_generation_error'
        );
    }

    /**
     * 回復コードを再生成
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $member = Auth::guard('member')->user();
        
        return $this->regenerateRecoveryCodesForModel(
            $member,
            'admin/profile.recovery_codes_regenerated',
            'admin/profile.recovery_codes_regenerate_too_soon',
            'admin/profile.recovery_codes_generation_error'
        );
    }

    /**
     * 回復コードセッションをクリア
     */
    public function clearRecoveryCodesSession(Request $request)
    {
        return $this->clearRecoveryCodesSessionData();
    }
}
