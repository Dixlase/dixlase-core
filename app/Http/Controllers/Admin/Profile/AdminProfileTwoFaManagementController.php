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
use App\Models\SecuritySetting;
use App\Services\MailServerValidatorService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Services\TwoFa\TwoFaStatusService;
use App\Traits\ManagesTwoFaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminProfileTwoFaManagementController extends AdminLoggedInController
{
    use ManagesTwoFaTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the two-factor authentication management page.
     */
    public function index()
    {
        $member = Auth::guard('member')->user();

        // Pass mail server settings status
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        $this->loadTwoFactorSettings($member);

        return view('admin.profile.two-fa-management', $this->viewParams);
    }

    /**
     * Generate WebAuthn challenge for Passkey registration
     */
    public function passkeyRegisterOptions(Request $request)
    {
        $member = Auth::guard('member')->user();

        return $this->generatePasskeyRegistrationOptions(
            $member,
            'admin/profile/common.passkey_register_options_error'
        );
    }

    /**
     * Register Passkey
     */
    public function passkeyRegister(Request $request)
    {
        $member = Auth::guard('member')->user();

        return $this->registerPasskeyForModel(
            $request,
            $member,
            'admin/profile/common.passkey_registered',
            'admin/profile/common.passkey_register_error'
        );
    }

    /**
     * Delete Passkey
     */
    public function revokePasskey(Request $request, string $credentialId)
    {
        $member = Auth::guard('member')->user();

        return $this->revokePasskeyForModel(
            $request,
            $member,
            $credentialId,
            'admin/profile/common.passkey_deleted_all',
            'admin/profile/common.passkey_not_found',
            'admin/profile/common.passkey_deleted',
            'admin/profile/common.passkey_delete_error'
        );
    }

    /**
     * Delete all Passkeys
     */
    public function revokeAllPasskeys(Request $request)
    {
        $member = Auth::guard('member')->user();
        $twoFaPasskeyService = new TwoFaPasskeyService();

        try {
            // Retrieve and delete all Passkeys
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
                    'message' => __('admin/profile/common.no_passkeys_to_delete'),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('admin/profile/common.all_passkeys_deleted', ['count' => $deletedCount]),
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] Bulk deletion error', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('admin/profile/common.passkey_delete_all_error'),
            ], 500);
        }
    }

    /**
     * Generate recovery codes
     */
    public function generateRecoveryCodes(Request $request)
    {
        $member = Auth::guard('member')->user();
        $recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);

        // Treat as regeneration if recovery codes already exist
        if ($recoveryCodeService->hasRecoveryCodes($member)) {
            return $this->regenerateRecoveryCodes($request);
        }

        return $this->generateRecoveryCodesForModel(
            $member,
            'admin/profile/common.recovery_codes_generated',
            'admin/profile/common.recovery_codes_generation_error'
        );
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $member = Auth::guard('member')->user();

        return $this->regenerateRecoveryCodesForModel(
            $member,
            'admin/profile/common.recovery_codes_regenerated',
            'admin/profile/common.recovery_codes_regenerate_too_soon',
            'admin/profile/common.recovery_codes_generation_error'
        );
    }

    /**
     * Clear recovery code session
     */
    public function clearRecoveryCodesSession(Request $request)
    {
        return $this->clearRecoveryCodesSessionData();
    }

    /**
     * Load two-factor authentication settings.
     */
    private function loadTwoFactorSettings($member)
    {
        // Retrieve settings using TwoFaStatusService
        $twoFaStatusService = new TwoFaStatusService();
        $twoFaForceMode = $twoFaStatusService->getGlobalTwoFaMode();
        $twoFaMode = $member->two_fa_mode;

        // Get two-factor authentication methods enabled in global settings
        $twoFaPasskeyMode = $twoFaStatusService->getGlobalPasskeyMode();
        $twoFaPasskeyEnabled = $twoFaPasskeyMode === 1;

        $this->viewParams['currentPasskeyEnabled'] = $twoFaPasskeyEnabled;

        // Email authentication is always enabled, Passkey depends on settings
        $twoFaEnabledMethods = [
            TwoFaMethod::EMAIL->value => TwoFaMethod::EMAIL->translationKey(),
        ];
        if ($twoFaPasskeyEnabled) {
            $twoFaEnabledMethods[TwoFaMethod::PASSKEY->value] = TwoFaMethod::PASSKEY->translationKey();
        }

        // Determine actual enabled/disabled state of two-factor authentication
        // Check profile settings if disabled in global settings or if global settings follow profile
        $actualTwoFaMode = $twoFaForceMode === AuthenticationMode::UseProfileSetting->value
            ? (is_int($twoFaMode) ? $twoFaMode : $twoFaMode->value)
            : $twoFaForceMode;
        $isTwoFaActuallyDisabled = $actualTwoFaMode === AuthenticationMode::Disabled->value;

        $this->viewParams['twoFaForceMode'] = $twoFaForceMode;
        $this->viewParams['twoFaMode'] = $twoFaMode;
        $this->viewParams['isTwoFaActuallyDisabled'] = $isTwoFaActuallyDisabled;
        $this->viewParams['twoFaEnabledMethods'] = $twoFaEnabledMethods;
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;

        // Get Passkey device list
        $twoFaPasskeyService = new TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);

        // Get maximum number of Passkey devices that can be registered
        $twoFaPasskeyMaxDevices = (int) SecuritySetting::getValue('two_fa_passkey_max_devices', '5');
        $currentDeviceCount = count($this->viewParams['twoFaPasskeyDevices']);
        $canRegisterMoreDevices = $currentDeviceCount < $twoFaPasskeyMaxDevices;

        $this->viewParams['twoFaPasskeyMaxDevices'] = $twoFaPasskeyMaxDevices;
        $this->viewParams['twoFaPasskeyCurrentCount'] = $currentDeviceCount;
        $this->viewParams['canRegisterMorePasskeys'] = $canRegisterMoreDevices;

        // Get recovery code information
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($member);
    }
}
