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

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\Member;
use App\Traits\ManagesAccountTrait;
use App\Traits\ManagesTwoFaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminMemberSecurityController extends AdminLoggedInController
{
    use ManagesAccountTrait, ManagesTwoFaTrait;

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
     * メンバー強制ログアウト
     */
    public function forceLogout(Member $member)
    {
        return $this->forceLogoutModel(
            $member,
            'admin/members/edit.messages.force_logout_success',
            'admin.members.edit'
        );
    }

    /**
     * Two-FAロックアウト解除
     */
    public function unlockTwoFa(Member $member)
    {
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
     * 認証メール送信
     */
    public function sendVerificationEmail(Member $member)
    {
        return $this->sendVerificationEmailToModel(
            $member,
            'admin/members/edit.messages.verification_email_sent',
            'admin/members/edit.messages.verification_email_failed',
            'admin.members.edit'
        );
    }

    /**
     * Passkey削除
     */
    public function revokePasskey(Request $request, Member $member, string $credentialId)
    {
        return $this->revokePasskeyForModel(
            $request,
            $member,
            $credentialId,
            'admin/members/form.passkey_all_deleted',
            'admin/profile.passkey_not_found',
            'admin/profile.passkey_deleted',
            'admin/profile.passkey_delete_error'
        );
    }

    /**
     * 全Passkey削除
     */
    public function revokeAllPasskeys(Request $request, Member $member)
    {
        return $this->revokePasskey($request, $member, 'all');
    }

    /**
     * 回復コード削除
     */
    public function revokeRecoveryCodes(Request $request, Member $member)
    {
        return $this->revokeRecoveryCodesForModel(
            $request,
            $member,
            'admin/members/form.recovery_codes_deleted',
            'admin/members/form.recovery_codes_delete_error'
        );
    }

    /**
     * 全メンバー強制ログアウト
     */
    public function forceLogoutAll()
    {
        $currentUserId = Auth::guard('member')->id();
        $sessionTable = config('session.table', 'sessions');
        
        if ($sessionTable && DB::getSchemaBuilder()->hasTable($sessionTable)) {
            $deletedCount = DB::table($sessionTable)
                ->where('user_id', '!=', $currentUserId)
                ->whereNotNull('user_id')
                ->delete();
            
            return redirect()->route('admin.members.index')
                ->with('success', __('admin/members/force_logout_all_success', ['count' => $deletedCount]));
        }

        return redirect()->route('admin.members.index')
            ->with('error', __('admin/members/force_logout_all_error'));
    }
}
