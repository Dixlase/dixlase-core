<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Services\MailServerValidatorService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminProfileController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the model's route parameter name
     */
    protected function getModelRouteParameterName(): string
    {
        return 'member';
    }

    /**
     * Show the profile overview page.
     */
    public function index()
    {
        $member = Auth::guard('member')->user();

        // Pass mail server settings status
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        // Add two-factor authentication settings
        $twoFaMode = $member->two_fa_mode;
        $this->viewParams['twoFaMode'] = $twoFaMode;

        // Get passkey device list
        $twoFaPasskeyService = new TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);

        // Get recovery code information
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);

        return view('admin.profile.index', $this->viewParams);
    }

    /**
     * Email verification process (enhanced security version: verify after login)
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $emailVerificationHelper = app(\App\Helpers\EmailVerificationHelper::class);

        // Get member from ID
        $member = \App\Models\Member::findOrFail($id);

        // Verify hash
        if (! $emailVerificationHelper->verifyHash($member, $hash)) {
            return redirect()->route('admin.login')
                ->with('error', __('admin/profile/common.email_verification_invalid'));
        }

        // Check if verification is required
        $verificationStatus = $emailVerificationHelper->needsVerification($member);
        if (! $verificationStatus['needs_verification']) {
            return redirect()->route('admin.login')
                ->with('info', __('admin/profile/common.email_already_verified'));
        }

        // Check login status
        $currentUser = \Auth::guard('member')->user();

        // Process immediately if logged in and matches the target member for verification
        if ($currentUser && $currentUser->id === $member->id) {
            $result = $emailVerificationHelper->processVerificationImmediately($member, 'admin');

            return redirect($result['redirect'])->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        }

        // If not logged in or logged in as a different user
        // Save verification token information to session
        $emailVerificationHelper->storeVerificationInSession($member, $hash);

        // Select message based on context
        $messageKey = $emailVerificationHelper->getLoginRequiredMessageKey(
            $verificationStatus['is_email_change']
        );

        // Redirect to login screen
        return redirect()->route('admin.login')->with('info', __($messageKey));
    }
}
