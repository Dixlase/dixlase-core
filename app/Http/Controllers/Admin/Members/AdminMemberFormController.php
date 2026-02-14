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

namespace App\Http\Controllers\Admin\Members;

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\AppearanceMode;
use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PasskeyMode;
use App\Enums\TwoFaMethod;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\AdminSettingsMemberStoreRequest;
use App\Models\Member;
use App\Services\MailServerValidatorService;
use App\Services\PasswordService;
use App\Services\TwoFa\TwoFaStatusService;

class AdminMemberFormController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(
        SecuritySettingRepositoryInterface $securitySettingRepository
    ) {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * メンバー新規作成フォーム
     */
    public function create()
    {
        $this->viewParams['member'] = null;
        $this->viewParams['roles'] = MemberRole::cases();
        $this->viewParams['roleOptions'] = MemberRole::translationOptions();
        $this->viewParams['roleAdminValue'] = MemberRole::ADMIN->value;
        $this->viewParams['roleSuperAdminValue'] = MemberRole::SUPER_ADMIN->value;
        $this->viewParams['loginNotificationUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['twoFactorUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['roleValue'] = (int) request()->old('role', MemberRole::ADMIN->value);
        $this->viewParams['appearanceOptions'] = AppearanceMode::translationOptions();
        $this->viewParams['localeOptions'] = \App\Enums\Locale::availableOptions();

        $this->viewParams['currentLoginNotification'] = (string) AuthenticationMode::Always->value;
        $this->viewParams['currentTwoFaMode'] = (string) AuthenticationMode::Always->value;

        // ステータスオプションをコンポーネント用の形式に変換
        $this->viewParams['statusOptions'] = [
            ['value' => '1', 'label' => 'components.status.active', 'icon' => 'fas fa-check-circle', 'color' => 'green'],
            ['value' => '0', 'label' => 'components.status.inactive', 'icon' => 'fas fa-times-circle', 'color' => 'gray'],
        ];

        $statusOld = request()->old('status');
        $statusValue = null;
        if (! is_null($statusOld)) {
            $statusValue = is_numeric($statusOld) ? (int) $statusOld : null;
        } else {
            $statusValue = MemberStatus::Active->value;
        }
        $this->viewParams['statusValue'] = $statusValue;
        $this->viewParams['requirePassword'] = true;
        $this->viewParams['verificationTranslationPrefix'] = 'admin/members/form';

        $this->loadMemberFormParams();

        // 新規作成時は、メールサーバーが設定されていれば2FA有効化可能
        $this->viewParams['canEnableTwoFa'] = \App\Services\MailServerValidatorService::isMailServerTested();
        $this->viewParams['twoFaEnableBlockReasons'] = $this->viewParams['canEnableTwoFa'] ? [] : ['no_mail_server'];

        return view('admin.members.create', $this->viewParams);
    }

    /**
     * メンバー保存
     */
    public function store(AdminSettingsMemberStoreRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = PasswordService::hash($validated['password']);

        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        $emailVerified = (string) $request->input('email_verified', $isMailServerTested ? '0' : '1');

        if (! $isMailServerTested) {
            $validated['email_verified_at'] = now();
        } elseif ($emailVerified === '1') {
            $validated['email_verified_at'] = now();
        } else {
            $validated['email_verified_at'] = null;
        }

        unset($validated['email_verified']);

        $member = Member::create($validated);

        if ($isMailServerTested && $emailVerified === '0') {
            try {
                $member->sendEmailVerificationNotification('create');
                $message = __('admin/members/create.messages.created_with_verification_email');
            } catch (\Exception $e) {
                \Log::error('Failed to send verification email', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
                $message = __('admin/members/create.messages.created_but_email_failed');
            }
        } else {
            $message = __('admin/members/create.messages.created');
        }

        return redirect()->route('admin.members.edit', ['member' => $member->id])->with('success', $message);
    }

    /**
     * メンバー編集フォーム
     */
    public function edit(Member $member)
    {
        $this->viewParams['member'] = $member;
        $isInitialAdmin = ($member->id === 1);
        $this->viewParams['isInitialAdmin'] = $isInitialAdmin;
        $this->viewParams['roles'] = MemberRole::cases();
        $this->viewParams['roleOptions'] = MemberRole::translationOptions();
        $this->viewParams['roleAdminValue'] = MemberRole::ADMIN->value;
        $this->viewParams['roleSuperAdminValue'] = MemberRole::SUPER_ADMIN->value;
        $this->viewParams['loginNotificationUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['twoFactorUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['roleValue'] = (int) request()->old('role', $member->role?->value ?? MemberRole::ADMIN->value);
        $this->viewParams['appearanceOptions'] = AppearanceMode::translationOptions();
        $this->viewParams['localeOptions'] = \App\Enums\Locale::availableOptions();

        $loginNotification = $member->login_notification_mode;
        if ($loginNotification instanceof AuthenticationMode) {
            $loginNotification = $loginNotification->value;
        }
        $this->viewParams['currentLoginNotification'] = (string) ($loginNotification ?? AuthenticationMode::Always->value);

        $twoFaModeValue = $member->two_fa_mode;
        if ($twoFaModeValue instanceof AuthenticationMode) {
            $twoFaModeValue = $twoFaModeValue->value;
        }
        $this->viewParams['currentTwoFaMode'] = (string) ($twoFaModeValue ?? AuthenticationMode::Always->value);

        // ステータスオプションをコンポーネント用の形式に変換
        $this->viewParams['statusOptions'] = [
            ['value' => '1', 'label' => 'components.status.active', 'icon' => 'fas fa-check-circle', 'color' => 'green'],
            ['value' => '0', 'label' => 'components.status.inactive', 'icon' => 'fas fa-times-circle', 'color' => 'gray'],
        ];

        $statusOld = request()->old('status');
        $statusValue = null;
        if (! is_null($statusOld)) {
            $statusValue = is_numeric($statusOld) ? (int) $statusOld : null;
        } else {
            $statusValue = $member->status?->value ?? MemberStatus::Active->value;
        }
        $this->viewParams['statusValue'] = $statusValue;
        $this->viewParams['requirePassword'] = false;
        $this->viewParams['verificationTranslationPrefix'] = 'admin/members/form';

        $this->loadMemberFormParams();

        // TwoFaStatusServiceを使用してパスキーモード判定
        $twoFaStatusService = new TwoFaStatusService();
        $twoFaPasskeyEnabled = $twoFaStatusService->isPasskeyEnabledGlobally();

        $twoFaPasskeyService = new \App\Services\TwoFa\TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;

        $twoFaRecoveryCodeService = new \App\Services\TwoFa\TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($member);

        // 編集時は、メールサーバーが設定されていて、かつメールアドレスが認証済みなら2FA有効化可能
        $isMailServerTested = \App\Services\MailServerValidatorService::isMailServerTested();
        $isEmailVerified = ! is_null($member->email_verified_at);
        $this->viewParams['canEnableTwoFa'] = $isMailServerTested && $isEmailVerified;

        $blockReasons = [];
        if (! $isMailServerTested) {
            $blockReasons[] = 'no_mail_server';
        }
        if (! $isEmailVerified) {
            $blockReasons[] = 'email_not_verified';
        }
        $this->viewParams['twoFaEnableBlockReasons'] = $blockReasons;

        return view('admin.members.edit', $this->viewParams);
    }

    /**
     * メンバー更新
     */
    public function update(AdminSettingsMemberStoreRequest $request, Member $member)
    {
        $validated = $request->validated();

        if (! empty($validated['password'])) {
            $validated['password'] = PasswordService::hash($validated['password']);
        } else {
            unset($validated['password']);
        }

        $member->update($validated);

        return redirect()->route('admin.members.edit', ['member' => $member->id])
            ->with('success', __('admin/members/edit.messages.updated'));
    }

    /**
     * メンバー削除
     */
    public function destroy(Member $member)
    {
        if ($member->id === 1) {
            return redirect()->route('admin.members.index')
                ->with('error', __('admin/members/index.messages.cannot_delete_initial_admin'));
        }

        $member->delete();

        return redirect()->route('admin.members.index')
            ->with('success', __('admin/members/index.messages.deleted'));
    }

    /**
     * フォーム用パラメータを読み込む
     */
    private function loadMemberFormParams(): void
    {
        // radio-card-group用のロールオプション配列を生成
        $roleCardOptions = [];
        foreach (MemberRole::cases() as $role) {
            $roleCardOptions[] = [
                'value' => $role->value,
                'label' => $role->label(),
                'icon' => 'fas fa-user-shield',
            ];
        }
        $this->viewParams['roleCardOptions'] = $roleCardOptions;

        // パスワード設定（セキュリティ設定から）
        $this->viewParams['passwordMinLength'] = (int) $this->securitySettingRepository->get('password_min_length', 8);
        $this->viewParams['passwordRequireUppercase'] = (bool) $this->securitySettingRepository->get('password_require_uppercase', true);
        $this->viewParams['passwordRequireLowercase'] = (bool) $this->securitySettingRepository->get('password_require_lowercase', true);
        $this->viewParams['passwordRequireNumber'] = (bool) $this->securitySettingRepository->get('password_require_number', true);
        $this->viewParams['passwordRequireSymbol'] = (bool) $this->securitySettingRepository->get('password_require_symbol', true);

        // ログイン通知設定（セキュリティ設定から）
        $loginNotificationMode = (int) $this->securitySettingRepository->get('login_notification_mode', AuthenticationMode::UseProfileSetting->value);
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        $loginNotificationEnum = AuthenticationMode::tryFrom($loginNotificationMode);
        $this->viewParams['loginNotificationModeLabel'] = $loginNotificationEnum ? $loginNotificationEnum->notificationLabel() : '';

        // radio-card-group用の通知設定オプション配列を生成
        $loginNotificationOptions = [];
        foreach (AuthenticationMode::forProfile() as $case) {
            $loginNotificationOptions[] = [
                'value' => (string) $case->value,
                'label' => $case->notificationLabel(),
            ];
        }
        $this->viewParams['loginNotificationModeOptions'] = $loginNotificationOptions;

        // TwoFaStatusServiceを使用して設定を取得
        $twoFaStatusService = new TwoFaStatusService();
        $twoFaForceMode = $twoFaStatusService->getGlobalTwoFaMode();
        $this->viewParams['forceTwoFa'] = $twoFaForceMode;

        // パスキーモード設定を追加
        $twoFaPasskeyMode = $twoFaStatusService->getGlobalPasskeyMode();
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;

        $twoFactorEnum = AuthenticationMode::tryFrom($twoFaForceMode);
        $this->viewParams['twoFactorModeLabel'] = $twoFactorEnum ? $twoFactorEnum->twoFactorLabel() : '';

        // radio-card-group用の二段階認証オプション配列を生成
        $twoFaModeOptions = [];
        foreach (AuthenticationMode::forProfile() as $case) {
            $twoFaModeOptions[] = [
                'value' => (string) $case->value,
                'label' => $case->twoFactorLabel(),
            ];
        }
        $this->viewParams['twoFactorModeOptions'] = $twoFaModeOptions;

        // two-fa.individual-settings コンポーネント用の事前計算値
        $this->viewParams['isTwoFaEditable'] = $twoFaForceMode === AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['isPasskeyEditable'] = PasskeyMode::isProfileEditable($twoFaPasskeyMode);
        $forcedPasskeyValue = PasskeyMode::getForcedProfileValue($twoFaPasskeyMode);
        $this->viewParams['forcedPasskeyValue'] = $forcedPasskeyValue;

        $this->viewParams['twoFaGlobalModeName'] = strtolower($twoFactorEnum?->name ?? 'disabled');

        // アイコン付きモードオプション（individual-settings用）
        $twoFaModeOptionsWithIcons = [];
        foreach (AuthenticationMode::forProfile() as $case) {
            $twoFaModeOptionsWithIcons[] = [
                'value' => (string) $case->value,
                'label' => $case->twoFactorLabel(),
                'icon' => match ($case) {
                    AuthenticationMode::Disabled => 'fas fa-ban',
                    AuthenticationMode::DifferentDevice => 'fas fa-shield-alt',
                    AuthenticationMode::Always => 'fas fa-lock',
                    default => 'fas fa-cog',
                },
            ];
        }
        $this->viewParams['twoFaModeOptionsWithIcons'] = $twoFaModeOptionsWithIcons;

        // 二段階認証方法の選択肢を作成
        $twoFaMethodOptions = [
            TwoFaMethod::EMAIL->value => TwoFaMethod::EMAIL->translationKey(),
        ];

        // 全体設定でパスキー認証が有効な場合は追加
        $twoFaPasskeyEnabled = $this->securitySettingRepository->get('two_fa_passkey_enabled', '0') === '1';
        if ($twoFaPasskeyEnabled) {
            $twoFaMethodOptions[TwoFaMethod::PASSKEY->value] = TwoFaMethod::PASSKEY->translationKey();
        }

        // デフォルトの認証方法を取得
        $twoFaDefaultMethod = (int) $this->securitySettingRepository->get('default_two_fa_method', TwoFaMethod::EMAIL->value);

        $this->viewParams['twoFaEnabledMethods'] = $twoFaMethodOptions;
        $this->viewParams['twoFaDefaultMethod'] = $twoFaDefaultMethod;
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;
        $this->viewParams['twoFaMode'] = AuthenticationMode::from($this->viewParams['forceTwoFa']);
        $this->viewParams['twoFaUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['loginNotificationUseProfileSettingValue'] = AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        $roleLabels = [];
        foreach (MemberRole::cases() as $role) {
            $roleLabels[$role->name] = $role->label();
        }
        $this->viewParams['roleLabels'] = $roleLabels;
    }
}
