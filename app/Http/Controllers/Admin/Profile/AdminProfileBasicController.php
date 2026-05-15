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

use App\Enums\Locale;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Profile\ProfileBasicUpdateRequest;
use App\Services\MailServerValidatorService;
use Illuminate\Support\Facades\Auth;

class AdminProfileBasicController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the basic information edit page.
     */
    public function index()
    {
        $member = Auth::guard('member')->user();

        // Get language options
        $this->viewParams['localeOptions'] = Locale::availableOptions();

        // Pass information if pending_email exists
        $this->viewParams['hasPendingEmail'] = ! empty($member->pending_email);
        $this->viewParams['pendingEmail'] = $member->pending_email;

        // Pass mail server settings status
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        return view('admin.profile.basic', $this->viewParams);
    }

    /**
     * Update basic information.
     */
    public function update(ProfileBasicUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        $validated = $request->validated();

        // Detect email address change
        $emailChanged = $member->email !== $validated['email'];

        // Check mail server settings status
        $isMailServerTested = MailServerValidatorService::isMailServerTested();

        $before = $member->only(['account_name', 'display_name', 'description', 'locale', 'email']);

        // Update profile
        $updateData = [
            'account_name' => $validated['account_name'],
            'display_name' => $validated['display_name'] ?? null,
            'description' => $validated['description'] ?? '',
            'locale' => $validated['locale'] ?? null,
        ];

        // Process email address change
        if ($emailChanged) {
            if ($isMailServerTested) {
                $updateData['pending_email'] = $validated['email'];
            } else {
                $updateData['email'] = $validated['email'];
                $updateData['pending_email'] = null;
                $updateData['email_verified_at'] = now();
            }
        } else {
            $updateData['pending_email'] = null;
        }

        $member->update($updateData);

        // Send verification email when email address is changed
        if ($emailChanged && $isMailServerTested) {
            try {
                $member->sendEmailVerificationNotification('email_change');
            } catch (\Exception $e) {
                \Log::error('Failed to send email verification', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Apply immediately if language settings are changed
        if (isset($validated['locale']) && $validated['locale']) {
            \Illuminate\Support\Facades\App::setLocale($validated['locale']);
        }

        // Message
        if ($emailChanged && $isMailServerTested) {
            $message = __('admin/profile/common.updated_with_email_verification');
        } elseif ($emailChanged && ! $isMailServerTested) {
            $message = __('admin/profile/common.updated_email_immediate');
        } else {
            $message = __('admin/profile/common.updated');
        }

        $after = auth()->user()->fresh()->only(['account_name', 'display_name', 'description', 'locale', 'email']);
        \App\Facades\Audit::log([
            'category' => 'account',
            'action' => 'profile.updated',
            'actor' => auth()->user(),
            'target' => auth()->user(),
            'context' => [
                'diff' => \App\Facades\Audit::diff($before, $after),
            ],
        ]);

        return redirect()->route('admin.profile.basic')->with('success', $message);
    }
}
