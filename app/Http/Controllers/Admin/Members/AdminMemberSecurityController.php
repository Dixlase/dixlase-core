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

use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\Member;
use App\Services\Member\MemberHierarchyGuard;
use App\Traits\ManagesAccountTrait;
use App\Traits\ManagesTwoFaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMemberSecurityController extends AdminLoggedInController
{
    use ManagesAccountTrait, ManagesTwoFaTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get model route parameter name
     */
    protected function getModelRouteParameterName(): string
    {
        return 'member';
    }

    /**
     * Force member logout
     */
    public function forceLogout(Member $member)
    {
        $this->assertCanManage($member);

        return $this->forceLogoutModel(
            $member,
            'admin/members/edit.messages.force_logout_success',
            'admin.members.edit'
        );
    }

    /**
     * Unlock Two-FA lockout
     */
    public function unlockTwoFa(Member $member)
    {
        $this->assertCanManage($member);

        return $this->unlockTwoFaForModel(
            $member,
            \App\Models\MemberTwoFaAttempt::class,
            \App\Models\MemberLoginAttempt::class,
            'member_id',
            'admin/members/edit.messages.unlock_lockout_success',
            'admin.members.edit'
        );
    }

    /**
     * Send verification email
     */
    public function sendVerificationEmail(Member $member)
    {
        $this->assertCanManage($member);

        return $this->sendVerificationEmailToModel(
            $member,
            'admin/members/edit.messages.verification_email_sent',
            'admin/members/edit.messages.verification_email_failed',
            'admin.members.edit'
        );
    }

    /**
     * Delete passkey
     */
    public function revokePasskey(Request $request, Member $member, string $credentialId)
    {
        $this->assertCanManage($member);

        return $this->revokePasskeyForModel(
            $request,
            $member,
            $credentialId,
            'admin/members/form.passkey_all_deleted',
            'admin/profile/common.passkey_not_found',
            'admin/profile/common.passkey_deleted',
            'admin/profile/common.passkey_delete_error'
        );
    }

    /**
     * Delete all passkeys
     */
    public function revokeAllPasskeys(Request $request, Member $member)
    {
        return $this->revokePasskey($request, $member, 'all');
    }

    /**
     * Delete recovery code
     */
    public function revokeRecoveryCodes(Request $request, Member $member)
    {
        $this->assertCanManage($member);

        return $this->revokeRecoveryCodesForModel(
            $request,
            $member,
            'admin/members/form.recovery_codes_deleted',
            'admin/members/form.recovery_codes_delete_error'
        );
    }

    /**
     * Force logout all members
     */
    public function forceLogoutAll()
    {
        $actor = AdminHelper::getMember();

        // Members' sessions live in members_sessions keyed by member_id. The
        // previous query targeted the framework `sessions` table on user_id, so
        // it logged out nobody. An actor also only logs out members at or below
        // their own rank.
        if (! DB::getSchemaBuilder()->hasTable('members_sessions')) {
            return redirect()->route('admin.members.index')
                ->with('error', __('admin/members/force_logout_all_error'));
        }

        $deletedCount = DB::table('members_sessions')
            ->whereNotNull('member_id')
            ->where('member_id', '!=', $actor->id)
            ->whereIn('member_id', Member::query()
                ->where('role', '<=', MemberHierarchyGuard::rank($actor))
                ->select('id'))
            ->delete();

        return redirect()->route('admin.members.index')
            ->with('success', __('admin/members/force_logout_all_success', ['count' => $deletedCount]));
    }

    /**
     * Refuse security operations on a member ranked above the actor.
     */
    private function assertCanManage(Member $member): void
    {
        MemberHierarchyGuard::assertCanManage(AdminHelper::getMember(), $member);
    }
}
