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

namespace App\Http\Controllers\Admin\Profile;

use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Profile\ProfileTwoFaUpdateRequest;
use App\Models\SecuritySetting;
use App\Services\MailServerValidatorService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Services\TwoFa\TwoFaStatusService;
use Illuminate\Support\Facades\Auth;

class AdminProfileTwoFaController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the two-factor authentication settings page.
     */
    public function index()
    {
        $member = Auth::guard('member')->user();

        // Pass mail server settings state
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        $this->loadTwoFactorSettings($member);

        // Check if 2FA can be enabled (on profile screen, check if mail server has been tested)
        $this->viewParams['canEnableTwoFa'] = $this->viewParams['isMailServerTested'];
        $this->viewParams['twoFaEnableBlockReasons'] = $this->viewParams['canEnableTwoFa'] ? [] : ['no_mail_server'];

        return view('admin.profile.two-fa', $this->viewParams);
    }

    /**
     * Update two-factor authentication settings.
     */
    public function update(ProfileTwoFaUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        $validated = $request->validated();

        // Get value before saving (as integer) to detect changes in two-factor authentication mode
        $twoFaOldMode = is_int($member->two_fa_mode) ? $member->two_fa_mode : $member->two_fa_mode->value;
        $beforeMode = $member->two_fa_mode;

        // two_fa_mode is only overwritten when global settings is UseProfileSetting
        $twoFaForceMode = (int) SecuritySetting::getValue('two_fa_mode', AuthenticationMode::UseProfileSetting->value);
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value && array_key_exists('two_fa_mode', $validated)) {
            $member->two_fa_mode = (int) $validated['two_fa_mode'];
        }

        $member->save();

        // Get 2FA state after saving (use value after saving)
        $member->refresh();

        // Determine using TwoFaStatusService
        $twoFaStatusService = new TwoFaStatusService();
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $twoFaPasskeyService = new TwoFaPasskeyService();

        \App\Facades\Audit::log([
            'category' => 'account',
            'action' => 'profile.two_fa_changed',
            'actor' => auth()->user(),
            'target' => auth()->user(),
            'severity' => 'notice',
            'context' => [
                'before' => $beforeMode,
                'after' => auth()->user()->fresh()->two_fa_mode,
            ],
        ]);

        $redirect = redirect()->route('admin.profile.two-fa')->with('success', __('admin/profile/common.two_fa_updated'));

        // Auto-generate if recovery codes do not exist
        if ($twoFaStatusService->shouldGenerateRecoveryCodes($member, $twoFaRecoveryCodeService)) {
            try {
                $codes = $twoFaRecoveryCodeService->generate($member);
                $redirect->with('auto_generated_recovery_codes', $codes);
            } catch (\Exception $e) {
                \Log::error('[Profile] Failed to auto-generate recovery codes', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Show promotion modal if passkey is enabled and device is not registered
        if ($twoFaStatusService->shouldPromptPasskeyRegistration($member, $twoFaPasskeyService)) {
            $redirect->with('prompt_passkey_registration', true);
        }

        return $redirect;
    }

    /**
     * Load two-factor authentication settings.
     */
    private function loadTwoFactorSettings($member)
    {
        // Add two-factor authentication settings
        $twoFaForceMode = (int) SecuritySetting::getValue(
            'two_fa_mode',
            AuthenticationMode::UseProfileSetting->value
        );
        $twoFaMode = $member->two_fa_mode;

        // Get two-factor authentication methods enabled in global settings
        // 0=disabled, 1=enabled (default: enabled)
        $twoFaPasskeyMode = (int) SecuritySetting::getValue('two_fa_passkey_mode', '1');
        $twoFaPasskeyEnabled = $twoFaPasskeyMode === 1;

        $this->viewParams['currentPasskeyEnabled'] = $twoFaPasskeyEnabled;

        // Email authentication is always enabled, Passkey depends on settings
        $twoFaEnabledMethods = [
            TwoFaMethod::EMAIL->value => TwoFaMethod::EMAIL->translationKey(),
        ];
        if ($twoFaPasskeyEnabled) {
            $twoFaEnabledMethods[TwoFaMethod::PASSKEY->value] = TwoFaMethod::PASSKEY->translationKey();
        }

        $this->viewParams['twoFaForceMode'] = $twoFaForceMode;
        $this->viewParams['twoFaMode'] = $twoFaMode;
        $this->viewParams['twoFaEnabledMethods'] = $twoFaEnabledMethods;
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;
        $this->viewParams['twoFaPasskeyGloballyEnabled'] = in_array(TwoFaMethod::PASSKEY->value, array_keys($twoFaEnabledMethods));
        $this->viewParams['isTwoFaEditable'] = $twoFaForceMode === AuthenticationMode::UseProfileSetting->value;

        // Get Passkey device list
        $twoFaPasskeyService = new TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);

        // Get recovery code information
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($member);
    }
}
