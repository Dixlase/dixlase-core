<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

namespace App\Http\Controllers\Admin\Members;

use App\Actions\Member\CreateMemberAction;
use App\Actions\Member\DeleteMemberAction;
use App\Actions\Member\UpdateMemberAction;
use App\Actors\MemberActor;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\AppearanceMode;
use App\Enums\AuthenticationMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PasskeyMode;
use App\Enums\TwoFaMethod;
use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Members\AdminSettingsMemberStoreRequest;
use App\Models\Member;
use App\Services\MailServerValidatorService;
use App\Services\Member\MemberHierarchyGuard;
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
     * Member creation form
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

        // Convert status options to component format
        $this->viewParams['statusOptions'] = [
            ['value' => '1', 'label' => 'components/ui-status-badge.active', 'icon' => 'fas fa-check-circle', 'color' => 'green'],
            ['value' => '0', 'label' => 'components/ui-status-badge.inactive', 'icon' => 'fas fa-times-circle', 'color' => 'gray'],
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

        // When creating new member, 2FA can be enabled if mail server is configured
        $this->viewParams['canEnableTwoFa'] = \App\Services\MailServerValidatorService::isMailServerTested();
        $this->viewParams['twoFaEnableBlockReasons'] = $this->viewParams['canEnableTwoFa'] ? [] : ['no_mail_server'];

        return view('admin.members.create', $this->viewParams);
    }

    /**
     * Save member
     */
    public function store(AdminSettingsMemberStoreRequest $request)
    {
        $validated = $request->validated();
        $validated['email_verified'] = $request->input(
            'email_verified',
            MailServerValidatorService::isMailServerTested() ? '0' : '1'
        );

        $actor = new MemberActor(AdminHelper::getMember());
        $result = app(CreateMemberAction::class)->execute($actor, $validated);

        if ($result->metadata['email_sent'] ?? false) {
            $message = __('admin/members/create.messages.created_with_verification_email');
        } elseif ($result->metadata['email_failed'] ?? false) {
            $message = __('admin/members/create.messages.created_but_email_failed');
        } else {
            $message = __('admin/members/create.messages.created');
        }

        return redirect()->route('admin.members.edit', ['member' => $result->model->id])->with('success', $message);
    }

    /**
     * Member edit form
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

        // Convert status options to component format
        $this->viewParams['statusOptions'] = [
            ['value' => '1', 'label' => 'components/ui-status-badge.active', 'icon' => 'fas fa-check-circle', 'color' => 'green'],
            ['value' => '0', 'label' => 'components/ui-status-badge.inactive', 'icon' => 'fas fa-times-circle', 'color' => 'gray'],
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

        $this->loadMemberFormParams($member);

        // Determine passkey mode using TwoFaStatusService
        $twoFaStatusService = new TwoFaStatusService();
        $twoFaPasskeyEnabled = $twoFaStatusService->isPasskeyEnabledGlobally();

        $twoFaPasskeyService = new \App\Services\TwoFa\TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;

        $twoFaRecoveryCodeService = new \App\Services\TwoFa\TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($member);

        // When editing, 2FA can be enabled if mail server is configured and email address is verified
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
     * Update member
     */
    public function update(AdminSettingsMemberStoreRequest $request, Member $member)
    {
        $actor = new MemberActor(AdminHelper::getMember());
        (new UpdateMemberAction($member))->execute($actor, $request->validated());

        return redirect()->route('admin.members.edit', ['member' => $member->id])
            ->with('success', __('admin/members/edit.messages.updated'));
    }

    /**
     * Delete member
     */
    public function destroy(Member $member)
    {
        $actor = new MemberActor(AdminHelper::getMember());
        $result = (new DeleteMemberAction($member))->execute($actor, []);

        if (! $result->success) {
            return redirect()->route('admin.members.index')
                ->with('error', __('admin/members/index.messages.cannot_delete_initial_admin'));
        }

        return redirect()->route('admin.members.index')
            ->with('success', __('admin/members/index.messages.deleted'));
    }

    /**
     * Load parameters for form
     */
    private function loadMemberFormParams(?Member $target = null): void
    {
        // Offer only the roles the actor may grant. On an edit screen keep the
        // member's current role too, so the form still shows it correctly.
        $roles = MemberHierarchyGuard::assignableRoles(AdminHelper::getMember());
        $currentRole = $target?->getAttribute('role');
        if ($currentRole instanceof MemberRole && ! in_array($currentRole, $roles, true)) {
            $roles[] = $currentRole;
        }
        usort($roles, static fn (MemberRole $a, MemberRole $b): int => $b->value <=> $a->value);

        // Generate role options array for radio-card-group
        $roleCardOptions = [];
        foreach ($roles as $role) {
            $roleCardOptions[] = [
                'value' => $role->value,
                'label' => $role->label(),
                'icon' => 'fas fa-user-shield',
            ];
        }
        $this->viewParams['roleCardOptions'] = $roleCardOptions;

        // Password settings (from security settings)
        $this->viewParams['passwordMinLength'] = (int) $this->securitySettingRepository->get('password_min_length', 8);
        $this->viewParams['passwordRequireUppercase'] = (bool) $this->securitySettingRepository->get('password_require_uppercase', true);
        $this->viewParams['passwordRequireLowercase'] = (bool) $this->securitySettingRepository->get('password_require_lowercase', true);
        $this->viewParams['passwordRequireNumber'] = (bool) $this->securitySettingRepository->get('password_require_number', true);
        $this->viewParams['passwordRequireSymbol'] = (bool) $this->securitySettingRepository->get('password_require_symbol', true);

        // Login notification settings (from security settings)
        $loginNotificationMode = (int) $this->securitySettingRepository->get('login_notification_mode', AuthenticationMode::UseProfileSetting->value);
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        $loginNotificationEnum = AuthenticationMode::tryFrom($loginNotificationMode);
        $this->viewParams['loginNotificationModeLabel'] = $loginNotificationEnum ? $loginNotificationEnum->notificationLabel() : '';

        // Generate notification settings options array for radio-card-group
        $loginNotificationOptions = [];
        foreach (AuthenticationMode::forProfile() as $case) {
            $loginNotificationOptions[] = [
                'value' => (string) $case->value,
                'label' => $case->notificationLabel(),
            ];
        }
        $this->viewParams['loginNotificationModeOptions'] = $loginNotificationOptions;

        // Get settings using TwoFaStatusService
        $twoFaStatusService = new TwoFaStatusService();
        $twoFaForceMode = $twoFaStatusService->getGlobalTwoFaMode();
        $this->viewParams['forceTwoFa'] = $twoFaForceMode;

        // Add passkey mode settings
        $twoFaPasskeyMode = $twoFaStatusService->getGlobalPasskeyMode();
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;

        $twoFactorEnum = AuthenticationMode::tryFrom($twoFaForceMode);
        $this->viewParams['twoFactorModeLabel'] = $twoFactorEnum ? $twoFactorEnum->twoFactorLabel() : '';

        // Generate two-factor authentication options array for radio-card-group
        $twoFaModeOptions = [];
        foreach (AuthenticationMode::forProfile() as $case) {
            $twoFaModeOptions[] = [
                'value' => (string) $case->value,
                'label' => $case->twoFactorLabel(),
            ];
        }
        $this->viewParams['twoFactorModeOptions'] = $twoFaModeOptions;

        // Pre-computed values for two-fa.individual-settings component
        $this->viewParams['isTwoFaEditable'] = $twoFaForceMode === AuthenticationMode::UseProfileSetting->value;
        $this->viewParams['isPasskeyEditable'] = PasskeyMode::isProfileEditable($twoFaPasskeyMode);
        $forcedPasskeyValue = PasskeyMode::getForcedProfileValue($twoFaPasskeyMode);
        $this->viewParams['forcedPasskeyValue'] = $forcedPasskeyValue;

        $this->viewParams['twoFaGlobalModeName'] = strtolower($twoFactorEnum?->name ?? 'disabled');

        // Mode options with icons (for individual-settings)
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

        // Create two-factor authentication method options
        $twoFaMethodOptions = [
            TwoFaMethod::EMAIL->value => TwoFaMethod::EMAIL->translationKey(),
        ];

        // Add if passkey authentication is enabled in global settings
        $twoFaPasskeyEnabled = $this->securitySettingRepository->get('two_fa_passkey_enabled', '0') === '1';
        if ($twoFaPasskeyEnabled) {
            $twoFaMethodOptions[TwoFaMethod::PASSKEY->value] = TwoFaMethod::PASSKEY->translationKey();
        }

        // Get default authentication method
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
